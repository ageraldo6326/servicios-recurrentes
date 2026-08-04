<?php

namespace App\Livewire\Clients;

use App\Models\Client;
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
        $clients = Client::query()
            ->withCount('contractedServices')
            ->withSum(['contractedServices as active_income' => fn ($query) => $query->where('status', 'active')], 'price')
            ->withSum(['contractedServices as active_cost' => fn ($query) => $query->where('status', 'active')], 'cost')
            ->when($this->search !== '', function ($query): void {
                $term = "%{$this->search}%";
                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', $term)->orWhere('phone', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.clients.index', compact('clients'));
    }
}
