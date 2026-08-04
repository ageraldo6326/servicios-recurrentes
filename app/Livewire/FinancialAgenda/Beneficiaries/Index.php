<?php

namespace App\Livewire\FinancialAgenda\Beneficiaries;

use App\Models\Beneficiary;
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
        $this->authorize('viewAny', Beneficiary::class);

        $beneficiaries = Beneficiary::query()
            ->withCount('financialCommitments')
            ->when($this->search !== '', function ($query): void {
                $term = "%{$this->search}%";
                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', $term)
                        ->orWhere('type', 'like', $term)
                        ->orWhere('observations', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.financial-agenda.beneficiaries.index', compact('beneficiaries'));
    }
}
