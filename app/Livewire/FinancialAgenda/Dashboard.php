<?php

namespace App\Livewire\FinancialAgenda;

use App\Actions\RegisterCommitmentPayment;
use App\Enums\CommitmentPaymentStatus;
use App\Enums\ContractedServiceStatus;
use App\Enums\FinancialCommitmentPriority;
use App\Models\Beneficiary;
use App\Models\CommitmentPayment;
use App\Models\ContractedService;
use App\Models\ExchangeRate;
use App\Models\FinancialCommitment;
use App\Services\CommitmentOccurrenceService;
use App\Services\FinancialCommitmentAgendaService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    use WithFileUploads;

    #[Url]
    public string $period = 'month';

    #[Url]
    public string $status = 'all';

    #[Url]
    public string $beneficiaryId = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $customStart = '';

    #[Url]
    public string $customEnd = '';

    public ?int $paymentCommitmentId = null;

    public ?int $paymentOccurrenceId = null;

    public string $paymentDate = '';

    public string $amountPaid = '';

    public string $paymentObservations = '';

    public $receipt;

    public string $successMessage = '';

    public string $exchangeRate = '';

    public string $exchangeRateDate = '';

    public string $rateMessage = '';

    public function mount(): void
    {
        $this->exchangeRateDate = CarbonImmutable::now(config('app.timezone'))->toDateString();
    }

    public function updatedPeriod(): void
    {
        if ($this->period !== 'custom') {
            $this->reset(['customStart', 'customEnd']);
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['period', 'status', 'beneficiaryId', 'category', 'customStart', 'customEnd']);
    }

    public function openPaymentForm(int $occurrenceId): void
    {
        $occurrence = CommitmentPayment::query()->with('financialCommitment')->findOrFail($occurrenceId);
        $commitment = $occurrence->financialCommitment;
        Gate::authorize('update', $commitment);

        $this->resetValidation();
        $this->paymentCommitmentId = $commitment->id;
        $this->paymentOccurrenceId = $occurrence->id;
        $this->paymentDate = CarbonImmutable::now(config('app.timezone'))->toDateString();
        $this->amountPaid = (string) ($commitment->suggested_amount ?? '');
        $this->paymentObservations = '';
        $this->receipt = null;
        $this->successMessage = '';
    }

    public function closePaymentForm(): void
    {
        $this->resetPaymentForm();
    }

    public function savePayment(RegisterCommitmentPayment $registerPayment): void
    {
        $occurrence = CommitmentPayment::query()
            ->with('financialCommitment')
            ->findOrFail($this->paymentOccurrenceId);
        $commitment = $occurrence->financialCommitment;
        Gate::authorize('update', $commitment);

        $validated = $this->validate([
            'paymentDate' => ['required', 'date'],
            'amountPaid' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'paymentObservations' => ['nullable', 'string', 'max:5000'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $receiptPath = $this->receipt?->store('commitment-receipts', 'public');
        $paidAt = CarbonImmutable::parse($validated['paymentDate'], config('app.timezone'))->startOfDay();
        $registerPayment->handleOccurrence(
            $occurrence,
            $paidAt,
            $validated['amountPaid'] ?: null,
            $validated['paymentObservations'] ?: null,
            $receiptPath,
        );

        $this->successMessage = 'Pago registrado para la obligación seleccionada.';
        $this->resetPaymentForm();
    }

    public function saveExchangeRate(): void
    {
        $validated = $this->validate([
            'exchangeRate' => ['required', 'numeric', 'min:0.0001', 'max:999999.9999'],
            'exchangeRateDate' => ['required', 'date'],
        ]);

        ExchangeRate::query()->create([
            'rate' => $validated['exchangeRate'],
            'effective_date' => $validated['exchangeRateDate'],
        ]);

        $this->rateMessage = 'Tasa del dólar guardada correctamente.';
        $this->reset(['exchangeRate']);
    }

    public function render(
        FinancialCommitmentAgendaService $agendaService,
        CommitmentOccurrenceService $occurrenceService,
    ): View {
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        [$rangeStart, $rangeEnd] = $this->selectedRange($today);

        $commitments = FinancialCommitment::query()
            ->with('beneficiary')
            ->where('is_active', true)
            ->when($this->beneficiaryId !== '', fn ($query) => $query->where('beneficiary_id', $this->beneficiaryId))
            ->when($this->category !== '', fn ($query) => $query->where('category', $this->category))
            ->orderBy('name')
            ->get()
            ->flatMap(function (FinancialCommitment $commitment) use ($agendaService, $occurrenceService, $today): array {
                return $occurrenceService->ensureForDate($commitment, $today)
                    ->map(function (CommitmentPayment $occurrence) use ($commitment, $agendaService, $today): array {
                        $occurrence->setRelation('financialCommitment', $commitment);

                        return ['commitment' => $commitment, 'agenda' => $agendaService->forOccurrence($occurrence, $today)];
                    })
                    ->all();
            })
            ->values();

        $nextOccurrenceIds = $commitments
            ->filter(fn (array $item): bool => $item['agenda']['period_start']->greaterThan($today->startOfMonth()))
            ->groupBy(fn (array $item): int => $item['commitment']->id)
            ->map(fn (Collection $items): int => $items->sortBy(fn (array $item): CarbonImmutable => $item['agenda']['period_start'])->first()['agenda']['occurrence']->id)
            ->values()
            ->all();

        $commitments = $commitments
            ->filter(fn (array $item): bool => $this->matchesFilters(
                $item['agenda'],
                $rangeStart,
                $rangeEnd,
                in_array($item['agenda']['occurrence']->id, $nextOccurrenceIds, true),
            ))
            ->sort(function (array $first, array $second): int {
                $firstGroup = $this->sortGroup($first['agenda']);
                $secondGroup = $this->sortGroup($second['agenda']);

                if ($firstGroup !== $secondGroup) {
                    return $firstGroup <=> $secondGroup;
                }

                return $first['agenda']['due_days'] <=> $second['agenda']['due_days'];
            })
            ->values();

        $categories = FinancialCommitment::query()
            ->where('is_active', true)
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $currentRate = ExchangeRate::query()
            ->whereDate('effective_date', '<=', $today->toDateString())
            ->latest('effective_date')
            ->latest('id')
            ->first();
        $monthlyBenefitUsd = $this->monthlyBenefitUsd();
        $monthlyBenefitDop = $currentRate === null ? null : $monthlyBenefitUsd * (float) $currentRate->rate;

        return view('livewire.financial-agenda.dashboard', [
            'commitments' => $commitments,
            'beneficiaries' => Beneficiary::query()->where('is_active', true)->orderBy('name')->get(),
            'categories' => $categories,
            'summary' => $this->summary($commitments, $today),
            'currentRate' => $currentRate,
            'monthlyBenefitUsd' => $monthlyBenefitUsd,
            'monthlyBenefitDop' => $monthlyBenefitDop,
            'rateHistory' => ExchangeRate::query()->latest('effective_date')->latest('id')->limit(5)->get(),
        ]);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function selectedRange(CarbonImmutable $today): array
    {
        return match ($this->period) {
            'today' => [$today, $today],
            'week' => [$today->startOfWeek(), $today->endOfWeek()],
            'custom' => $this->customRange($today),
            default => [$today->startOfMonth(), $today->endOfMonth()],
        };
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function customRange(CarbonImmutable $today): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m-d', $this->customStart ?: $today->toDateString()) ?: $today;
        $start = $start->startOfDay();
        $end = CarbonImmutable::createFromFormat('Y-m-d', $this->customEnd ?: $start->toDateString()) ?: $start;
        $end = $end->endOfDay();

        return $end->lessThan($start) ? [$end->startOfDay(), $start->endOfDay()] : [$start, $end];
    }

    private function matchesFilters(
        array $agenda,
        CarbonImmutable $rangeStart,
        CarbonImmutable $rangeEnd,
        bool $isNextOccurrence = false,
    ): bool {
        $isPaid = $agenda['is_paid'];
        $isOverdue = $agenda['status'] === CommitmentPaymentStatus::Overdue;
        $statusMatches = match ($this->status) {
            'paid' => $isPaid,
            'overdue' => $isOverdue,
            'pending' => in_array($agenda['status'], [CommitmentPaymentStatus::Pending, CommitmentPaymentStatus::PartiallyPaid], true),
            'projected' => $agenda['status'] === CommitmentPaymentStatus::Projected,
            default => true,
        };

        if (! $statusMatches) {
            return false;
        }

        if ($this->period === 'today') {
            return $isOverdue || $agenda['due_date']->isSameDay($rangeStart) || $agenda['cutoff_date']?->isSameDay($rangeStart);
        }

        if ($this->period === 'month' && $isNextOccurrence) {
            return true;
        }

        $dueInRange = $agenda['due_date']->betweenIncluded($rangeStart, $rangeEnd);
        $cutoffInRange = $agenda['cutoff_date']?->betweenIncluded($rangeStart, $rangeEnd) ?? false;

        return $isOverdue || $dueInRange || $cutoffInRange;
    }

    /** @param Collection<int, array> $commitments */
    private function summary(Collection $commitments, CarbonImmutable $today): array
    {
        $currentPeriod = $commitments->filter(
            fn (array $item): bool => $item['agenda']['period_start']->isSameDay($today->startOfMonth()),
        );

        return [
            'total' => $commitments->count(),
            'overdue' => $commitments->where('agenda.status', CommitmentPaymentStatus::Overdue)->count(),
            'today' => $commitments->whereIn('agenda.priority', [FinancialCommitmentPriority::High])->count(),
            'paid' => $commitments->where('agenda.is_paid', true)->count(),
            'total_amount' => $currentPeriod->sum(fn (array $item): float => (float) ($item['agenda']['balance'] ?? $item['agenda']['expected_amount'] ?? 0)),
        ];
    }

    private function monthlyBenefitUsd(): float
    {
        $services = ContractedService::query()->where('status', ContractedServiceStatus::Active)->get();

        return (float) $services->sum('price') - (float) $services->sum('cost');
    }

    private function sortGroup(array $agenda): int
    {
        if ($agenda['status'] === CommitmentPaymentStatus::Paid || $agenda['status'] === CommitmentPaymentStatus::Cancelled) {
            return 2;
        }

        return $agenda['status'] === CommitmentPaymentStatus::Overdue ? 0 : 1;
    }

    private function resetPaymentForm(): void
    {
        $this->reset(['paymentCommitmentId', 'paymentOccurrenceId', 'paymentDate', 'amountPaid', 'paymentObservations', 'receipt']);
        $this->resetValidation();
    }
}
