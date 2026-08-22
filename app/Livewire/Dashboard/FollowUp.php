<?php

namespace App\Livewire\Dashboard;

use App\Enums\ChargeStatus;
use App\Enums\PaymentStatus;
use App\Models\CatalogService;
use App\Models\CompanySetting;
use App\Models\ContractedService;
use Carbon\CarbonImmutable;
use App\Models\Provider;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class FollowUp extends Component
{
    private ?CarbonImmutable $evaluationNow = null;

    private ?int $upcomingDueDays = null;

    #[Url]
    public string $search = '';

    #[Url]
    public string $provider = 'all';

    #[Url]
    public string $serviceType = 'all';

    #[Url]
    public string $status = 'active';

    #[Url]
    public string $type = 'all';

    public function updatedSearch(): void
    {
        $this->resetPageIfNeeded();
    }

    public function updatedProvider(): void
    {
        $this->resetPageIfNeeded();
    }

    public function updatedServiceType(): void
    {
        $this->resetPageIfNeeded();
    }

    public function updatedStatus(): void
    {
        $this->resetPageIfNeeded();
    }

    public function updatedType(): void
    {
        $this->resetPageIfNeeded();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'provider', 'serviceType', 'type']);
        $this->status = 'active';
    }

    public function render(): View
    {
        $services = $this->servicesForFollowUp();

        if ($this->type !== 'all') {
            $services = $services->where('follow_up_type', $this->type)->values();
        }

        $stats = [
            'pending' => $services->count(),
            'promises' => $services->whereIn('follow_up_type', ['promise_overdue', 'promise_today'])->count(),
            'overdue' => $services->where('follow_up_type', 'charge_overdue')->count(),
            'pendingPayments' => $services->filter(fn (ContractedService $service): bool => $this->hasPendingPayment($service))->count(),
            'risk' => $services->where('follow_up_type', 'cancellation_risk')->count(),
            'collectionTotal' => $services->sum(fn (ContractedService $service): float => $this->collectionAmount($service)),
            'costTotal' => $services->sum(fn (ContractedService $service): float => (float) $service->cost),
        ];

        return view('livewire.dashboard.follow-up', [
            'services' => $services,
            'stats' => $stats,
            'providers' => Provider::query()->orderBy('name')->get(['id', 'name']),
            'catalogServices' => CatalogService::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'types' => $this->types(),
        ]);
    }

    private function servicesForFollowUp(): Collection
    {
        $today = $this->evaluationNow()->startOfDay();
        $services = ContractedService::query()
            ->with([
                'client',
                'catalogService',
                'provider',
                'gestions' => fn ($query) => $query->latest('occurred_at'),
                'charges' => fn ($query) => $query->with('payments')->orderBy('due_date'),
            ])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when($this->status !== 'cancelled', fn ($query) => $query->whereDate('starts_at', '<=', $today->toDateString()))
            ->when($this->provider !== 'all', fn ($query) => $query->where('provider_id', $this->provider))
            ->when($this->serviceType !== 'all', fn ($query) => $query->where('catalog_service_id', $this->serviceType))
            ->when($this->search !== '', function ($query): void {
                $term = "%{$this->search}%";
                $query->where(function ($query) use ($term): void {
                    $query->whereHas('client', fn ($client) => $client->where('name', 'like', $term))
                        ->orWhereHas('catalogService', fn ($service) => $service->where('name', 'like', $term))
                        ->orWhere('ip', 'like', $term);
                });
            })
            ->get();

        return $services
            ->map(function (ContractedService $service): ContractedService {
                $service->setAttribute('follow_up_type', $this->followUpType($service));
                $service->setAttribute('follow_up_priority', $this->followUpPriority($service));
                $service->setAttribute('billing_date', $this->billingDate($service));
                $service->setAttribute('overdue_days', $this->overdueDays($service));
                $service->setAttribute('days_until_billing', $this->daysUntilBilling($service));

                return $service;
            })
            ->filter(fn (ContractedService $service): bool => $service->getAttribute('follow_up_type') !== null)
            ->sort(function (ContractedService $left, ContractedService $right): int {
                return [$left->follow_up_priority, -$left->created_at->timestamp, $left->client->name]
                    <=> [$right->follow_up_priority, -$right->created_at->timestamp, $right->client->name];
            })
            ->values();
    }

    private function followUpType(ContractedService $service): ?string
    {
        $now = $this->evaluationNow();
        $today = $now->startOfDay();
        $promise = $service->gestions->filter(fn ($gestion): bool => $gestion->promised_payment_date !== null)->sortByDesc('promised_payment_date')->first();
        $latestGestion = $service->gestions->first();
        $billingDate = $this->billingDate($service);
        $billingCharge = $service->charges
            ->filter(fn ($item): bool => $item->due_date?->isSameDay($billingDate))
            ->sortByDesc('created_at')
            ->first();
        $billingCyclePending = $billingCharge === null || $billingCharge->status !== ChargeStatus::Paid;

        if ($billingCyclePending && $promise?->promised_payment_date?->lt($today)) {
            return 'promise_overdue';
        }

        if ($billingCyclePending && $billingDate->lt($today)) {
            return 'charge_overdue';
        }

        if ($billingCyclePending && $promise?->promised_payment_date?->isToday()) {
            return 'promise_today';
        }

        if ($billingCyclePending && $billingDate->isToday()) {
            return 'charge_today';
        }

        if ($this->hasPendingPayment($service)) {
            return 'payment_pending';
        }

        if ($latestGestion?->next_follow_up_at?->lte($now)) {
            return 'scheduled';
        }

        $daysWithoutContact = $latestGestion?->occurred_at?->diffInDays($now) ?? $service->created_at->diffInDays($now);
        $result = str($latestGestion?->result ?? '')->lower();

        if ($result->contains(['no responde', 'no contestó', 'no contesta']) && $daysWithoutContact >= 2) {
            return 'cancellation_risk';
        }

        if ($result->contains(['no responde', 'no contestó', 'no contesta'])) {
            return 'second_contact';
        }

        if ($billingCyclePending && $billingDate->between($today->copy()->addDay(), $today->copy()->addDays($this->upcomingDueDays()))) {
            return 'upcoming';
        }

        return null;
    }

    private function followUpPriority(ContractedService $service): int
    {
        return match ($service->follow_up_type) {
            'promise_overdue' => 1,
            'charge_overdue' => 2,
            'promise_today' => 3,
            'charge_today' => 4,
            'cancellation_risk' => 5,
            'second_contact' => 6,
            'scheduled' => 7,
            'upcoming' => 8,
            'payment_pending' => 9,
            default => 99,
        };
    }

    private function hasPendingPayment(ContractedService $service): bool
    {
        return $service->charges->flatMap->payments->contains(fn ($payment): bool => $payment->status === PaymentStatus::Pending);
    }

    private function collectionAmount(ContractedService $service): float
    {
        $billingCharge = $service->charges
            ->filter(fn ($charge): bool => $charge->due_date?->isSameDay($this->billingDate($service)))
            ->sortByDesc('created_at')
            ->first();

        if ($billingCharge?->status === ChargeStatus::Paid) {
            return 0;
        }

        $pendingCharge = $service->charges
            ->filter(fn ($charge): bool => in_array($charge->status, [ChargeStatus::Pending, ChargeStatus::Partial, ChargeStatus::Overdue], true))
            ->sortBy('due_date')
            ->first();

        return (float) ($pendingCharge?->amount ?? $service->price);
    }

    private function billingDate(ContractedService $service)
    {
        $month = $this->evaluationNow()->startOfMonth();

        return $month->copy()->day(min((int) $service->billing_day, $month->daysInMonth));
    }

    private function overdueDays(ContractedService $service): int
    {
        $today = $this->evaluationNow()->startOfDay();
        $pendingCharge = $service->charges
            ->filter(fn ($charge): bool => in_array($charge->status, [ChargeStatus::Pending, ChargeStatus::Partial, ChargeStatus::Overdue], true))
            ->filter(fn ($charge): bool => $charge->due_date?->lt($today))
            ->sortBy('due_date')
            ->first();

        $dueDate = $pendingCharge?->due_date;

        if ($dueDate === null) {
            $billingDate = $this->billingDate($service);
            $billingCharge = $service->charges
                ->filter(fn ($charge): bool => $charge->due_date?->isSameDay($billingDate))
                ->sortByDesc('created_at')
                ->first();

            if (($billingCharge === null || $billingCharge->status !== ChargeStatus::Paid) && $billingDate->lt($today)) {
                $dueDate = $billingDate;
            }
        }

        return $dueDate?->diffInDays($today) ?? 0;
    }

    private function daysUntilBilling(ContractedService $service): int
    {
        return (int) $this->evaluationNow()->startOfDay()->diffInDays($this->billingDate($service));
    }

    private function types(): array
    {
        return [
            'promise_overdue' => 'Promesa vencida',
            'charge_overdue' => 'Cobro vencido',
            'promise_today' => 'Promesa para hoy',
            'charge_today' => 'Cobro de hoy',
            'cancellation_risk' => 'Riesgo de cancelación',
            'second_contact' => 'Segundo contacto',
            'scheduled' => 'Seguimiento programado',
            'upcoming' => 'Próximo vencimiento',
            'payment_pending' => 'Pago por validar',
        ];
    }

    private function evaluationNow(): CarbonImmutable
    {
        return $this->evaluationNow ??= CarbonImmutable::now(CompanySetting::configuredTimezone());
    }

    private function upcomingDueDays(): int
    {
        return $this->upcomingDueDays ??= CompanySetting::configuredUpcomingDueDays();
    }

    private function resetPageIfNeeded(): void
    {
        // The queue is intentionally recalculated without a page reload.
    }
}
