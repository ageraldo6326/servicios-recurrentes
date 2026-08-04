<?php

namespace App\Livewire\FinancialAgenda\Commitments;

use App\Models\FinancialCommitment;
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

    #[Url]
    public string $status = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status']);
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorize('viewAny', FinancialCommitment::class);

        $commitments = FinancialCommitment::query()
            ->with('beneficiary')
            ->withCount('payments')
            ->when($this->search !== '', function ($query): void {
                $term = "%{$this->search}%";
                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', $term)
                        ->orWhere('category', 'like', $term)
                        ->orWhereHas('beneficiary', fn ($beneficiary) => $beneficiary->where('name', 'like', $term));
                });
            })
            ->when($this->status !== 'all', fn ($query) => $query->where('is_active', $this->status === 'active'))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.financial-agenda.commitments.index', compact('commitments'));
    }
}
