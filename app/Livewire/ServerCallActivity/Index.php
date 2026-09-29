<?php

declare(strict_types=1);

namespace App\Livewire\ServerCallActivity;

use App\Enums\CallActivityState;
use App\Enums\ContractedServiceStatus;
use App\Models\CompanySetting;
use App\Models\ContractedService;
use App\Services\CallActivity\CallActivityStateResolver;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
final class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $platform = 'all';

    public string $state = 'all';

    public string $sort = 'outbound';

    public ?int $historyServiceId = null;

    public string $displayTimezone = 'America/Santo_Domingo';

    public function mount(): void
    {
        $this->displayTimezone = CompanySetting::configuredTimezone();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPlatform(): void
    {
        $this->resetPage();
    }

    public function updatedState(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'platform', 'state', 'sort']);
        $this->resetPage();
    }

    public function openHistory(int $serviceId): void
    {
        ContractedService::query()
            ->where('call_monitoring_enabled', true)
            ->where('status', ContractedServiceStatus::Active->value)
            ->findOrFail($serviceId);

        $this->historyServiceId = $serviceId;
    }

    public function closeHistory(): void
    {
        $this->historyServiceId = null;
    }

    public function localDate(?CarbonInterface $date): string
    {
        if ($date === null) {
            return '—';
        }

        return $date->copy()->setTimezone($this->displayTimezone)->format('d/m/Y H:i');
    }

    public function elapsed(?CarbonInterface $date): string
    {
        return $date?->copy()->locale('es')->diffForHumans() ?? 'Nunca';
    }

    public function render(CallActivityStateResolver $stateResolver): View
    {
        $services = ContractedService::query()
            ->with(['client:id,name', 'catalogService:id,name', 'callActivity'])
            ->where('call_monitoring_enabled', true)
            ->where('status', ContractedServiceStatus::Active->value)
            ->when($this->search !== '', function ($query): void {
                $search = trim($this->search);
                $query->where(function ($query) use ($search): void {
                    $query->where('ip', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($client) => $client->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('catalogService', fn ($catalogService) => $catalogService->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($this->platform !== 'all', fn ($query) => $query->where('call_monitoring_platform', $this->platform))
            ->get();

        $states = $services->mapWithKeys(fn (ContractedService $service): array => [
            $service->id => $stateResolver->resolve($service),
        ]);

        $summary = collect(CallActivityState::cases())->mapWithKeys(fn (CallActivityState $state): array => [
            $state->value => $states->filter(fn (CallActivityState $current): bool => $current === $state)->count(),
        ]);

        $filteredServices = $services
            ->when($this->state !== 'all', fn (Collection $items): Collection => $items->filter(
                fn (ContractedService $service): bool => $states->get($service->id)?->value === $this->state
            ))
            ->sortBy(fn (ContractedService $service): int => (int) match ($this->sort) {
                'report' => $service->callActivity?->last_reported_at?->getTimestamp() ?? 0,
                default => $service->callActivity?->last_outbound_at?->getTimestamp() ?? 0,
            })
            ->values();

        $historyService = $this->historyServiceId === null
            ? null
            : ContractedService::query()
                ->with(['client:id,name', 'catalogService:id,name'])
                ->where('call_monitoring_enabled', true)
                ->where('status', ContractedServiceStatus::Active->value)
                ->find($this->historyServiceId);
        $historyReports = $historyService?->callActivityReports()
            ->latest('received_at')
            ->limit(20)
            ->get() ?? collect();

        return view('livewire.server-call-activity.index', [
            'services' => $this->paginateCollection($filteredServices),
            'states' => $states,
            'summary' => $summary,
            'historyService' => $historyService,
            'historyReports' => $historyReports,
            'timezone' => $this->displayTimezone,
        ]);
    }

    /**
     * @param  Collection<int, ContractedService>  $items
     */
    private function paginateCollection(Collection $items): LengthAwarePaginatorContract
    {
        $perPage = 12;
        $page = max(1, $this->getPage());

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }
}
