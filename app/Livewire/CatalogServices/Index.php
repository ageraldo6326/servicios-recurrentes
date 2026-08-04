<?php

namespace App\Livewire\CatalogServices;

use App\Models\CatalogService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->reset('search');
        $this->resetPage();
    }

    public function render(): View
    {
        $services = CatalogService::query()
            ->withCount('contractedServices')
            ->withSum(['contractedServices as active_income' => fn ($query) => $query->where('status', 'active')], 'price')
            ->withSum(['contractedServices as active_cost' => fn ($query) => $query->where('status', 'active')], 'cost')
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.catalog-services.index', compact('services'));
    }
}
