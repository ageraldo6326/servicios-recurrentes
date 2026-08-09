<?php

namespace App\Livewire\UnplannedExpenses;

use App\Actions\DeleteUnplannedExpense;
use App\Actions\RegisterUnplannedExpense;
use App\Actions\UpdateUnplannedExpense;
use App\Enums\UnplannedExpenseContext;
use App\Enums\UnplannedExpenseStatus;
use App\Models\UnplannedExpense;
use App\Services\UnplannedExpenseDashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    use WithPagination;

    public string $name = '';

    public string $type = '';

    public string $amount = '';

    public string $place = '';

    public string $expenseDate = '';

    public string $context = 'personal';

    public string $status = 'paid';

    public string $observations = '';

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->expenseDate = now()->toDateString();
    }

    public function save(RegisterUnplannedExpense $register, UpdateUnplannedExpense $update): void
    {
        $validated = $this->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'place' => ['nullable', 'string', 'max:255'],
            'expenseDate' => ['nullable', 'date'],
            'context' => ['nullable', 'in:personal,business,both'],
            'status' => ['required', 'in:pending,paid'],
            'observations' => ['nullable', 'string', 'max:5000'],
        ], [], [
            'expenseDate' => 'fecha del gasto',
        ]);

        $attributes = [
            ...$validated,
            'expense_date' => $validated['expenseDate'] ?: null,
        ];

        if ($this->editingId !== null) {
            $expense = UnplannedExpense::query()->findOrFail($this->editingId);
            $update->execute($expense, $attributes, (int) auth()->id());
            session()->flash('success', 'Gasto hormiga actualizado correctamente.');
        } else {
            $attributes['registered_at'] = now();
            $register->execute($attributes, (int) auth()->id());
            session()->flash('success', 'Gasto hormiga registrado correctamente.');
        }

        $this->cancelEdit();
        $this->resetPage();
    }

    public function edit(int $expenseId): void
    {
        $expense = UnplannedExpense::query()->findOrFail($expenseId);

        $this->editingId = $expense->id;
        $this->name = $expense->name ?? '';
        $this->type = $expense->type ?? '';
        $this->amount = $expense->amount !== null ? (string) $expense->amount : '';
        $this->place = $expense->place ?? '';
        $this->expenseDate = $expense->expense_date?->toDateString() ?? '';
        $this->context = $expense->context?->value ?? 'personal';
        $this->status = $expense->status?->value ?? 'paid';
        $this->observations = $expense->observations ?? '';
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'name', 'type', 'amount', 'place', 'observations']);
        $this->expenseDate = now()->toDateString();
        $this->context = 'personal';
        $this->status = 'paid';
        $this->resetValidation();
    }

    public function delete(int $expenseId, DeleteUnplannedExpense $delete): void
    {
        $expense = UnplannedExpense::query()->findOrFail($expenseId);
        $delete->execute($expense, (int) auth()->id());

        if ($this->editingId === $expenseId) {
            $this->cancelEdit();
        }

        $this->resetPage();
        session()->flash('success', 'Gasto hormiga eliminado correctamente.');
    }

    public function render(UnplannedExpenseDashboardService $dashboard): View
    {
        $summary = $dashboard->summary(CarbonImmutable::today());
        $expenses = UnplannedExpense::query()
            ->latest('expense_date')
            ->latest('id')
            ->paginate(10);

        return view('livewire.unplanned-expenses.dashboard', [
            ...$summary,
            'expenses' => $expenses,
            'contexts' => UnplannedExpenseContext::cases(),
            'statuses' => UnplannedExpenseStatus::cases(),
        ]);
    }
}
