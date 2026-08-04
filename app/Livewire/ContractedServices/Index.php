<?php

namespace App\Livewire\ContractedServices;

use App\Models\Client;
use App\Models\ContractedService;
use App\Models\Provider;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public string $provider = 'all';
    public string $billingDayFrom = 'all';
    public string $billingDayTo = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedProvider(): void
    {
        $this->resetPage();
    }

    public function updatedBillingDayFrom(): void
    {
        $this->resetPage();
    }

    public function updatedBillingDayTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'provider', 'billingDayFrom', 'billingDayTo']);
        $this->resetPage();
    }

    public function render(): View
    {
        $filteredQuery = ContractedService::query()
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->whereHas('client', fn ($client) => $client->where('name', 'like', "%{$this->search}%"))
                        ->orWhereHas('catalogService', fn ($service) => $service->where('name', 'like', "%{$this->search}%"))
                        ->orWhere('ip', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when($this->provider !== 'all', fn ($query) => $query->where('provider_id', $this->provider))
            ->when($this->billingDayFrom !== 'all', fn ($query) => $query->where('billing_day', '>=', $this->billingDayFrom))
            ->when($this->billingDayTo !== 'all', fn ($query) => $query->where('billing_day', '<=', $this->billingDayTo));

        $services = (clone $filteredQuery)
            ->with(['client', 'catalogService', 'provider'])
            ->orderByDesc('contracted_services.created_at')
            ->orderBy(Client::select('name')->whereColumn('clients.id', 'contracted_services.client_id'))
            ->paginate(12);

        $activeFilteredQuery = (clone $filteredQuery)->where('status', 'active');
        $projectedIncome = (clone $activeFilteredQuery)->sum('price');
        $projectedCosts = (clone $activeFilteredQuery)->sum('cost');
        $projectedProfit = $projectedIncome - $projectedCosts;

        return view('livewire.contracted-services.index', [
            'services' => $services,
            'providers' => Provider::query()->orderBy('name')->get(['id', 'name']),
            'activeCount' => $activeFilteredQuery->count(),
            'monthlyRevenue' => $projectedIncome,
            'monthlyCosts' => $projectedCosts,
            'monthlyProfit' => $projectedProfit,
            'projectedIncome' => $projectedIncome,
            'projectedCosts' => $projectedCosts,
            'projectedProfit' => $projectedProfit,
        ]);
    }
}
