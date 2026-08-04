<?php

namespace App\Livewire\Charges;

use App\Models\Charge;
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
        $charges = Charge::query()
            ->with(['contractedService.client', 'contractedService.catalogService'])
            ->when($this->search !== '', function ($query): void {
                $term = "%{$this->search}%";
                $query->where(function ($query) use ($term): void {
                    $query->whereHas('contractedService.client', fn ($client) => $client->where('name', 'like', $term))
                        ->orWhereHas('contractedService.catalogService', fn ($service) => $service->where('name', 'like', $term));
                });
            })
            ->orderBy('due_date')
            ->paginate(20);

        return view('livewire.charges.index', compact('charges'));
    }
}
