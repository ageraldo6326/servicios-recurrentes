<?php

namespace App\Http\Controllers;

use App\Enums\FinancialCommitmentFrequency;
use App\Http\Requests\FinancialCommitmentRequest;
use App\Models\Beneficiary;
use App\Models\FinancialCommitment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FinancialCommitmentController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', FinancialCommitment::class);

        return view('financial-agenda.commitments.form', [
            'commitment' => new FinancialCommitment([
                'frequency' => FinancialCommitmentFrequency::Monthly,
                'is_active' => true,
            ]),
            'beneficiaries' => Beneficiary::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(FinancialCommitmentRequest $request): RedirectResponse
    {
        Gate::authorize('create', FinancialCommitment::class);
        FinancialCommitment::query()->create($request->validated());

        return redirect()->route('financial-agenda.commitments.index')
            ->with('success', 'Compromiso financiero creado.');
    }

    public function edit(FinancialCommitment $commitment): View
    {
        Gate::authorize('update', $commitment);

        return view('financial-agenda.commitments.form', [
            'commitment' => $commitment,
            'beneficiaries' => Beneficiary::query()->orderBy('name')->get(),
        ]);
    }

    public function update(FinancialCommitmentRequest $request, FinancialCommitment $commitment): RedirectResponse
    {
        Gate::authorize('update', $commitment);
        $commitment->update($request->validated());

        return redirect()->route('financial-agenda.commitments.index')
            ->with('success', 'Compromiso financiero actualizado.');
    }
}
