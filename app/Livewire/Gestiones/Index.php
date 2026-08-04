<?php

namespace App\Livewire\Gestiones;

use App\Models\Gestion;
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
        $gestions = Gestion::query()
            ->with(['client', 'contractedService.catalogService'])
            ->when($this->search !== '', function ($query): void {
                $term = "%{$this->search}%";
                $query->where(function ($query) use ($term): void {
                    $query->where('type', 'like', $term)
                        ->orWhere('result', 'like', $term)
                        ->orWhereHas('client', fn ($client) => $client->where('name', 'like', $term));
                });
            })
            ->latest('occurred_at')
            ->paginate(20);

        return view('livewire.gestiones.index', compact('gestions'));
    }
}
