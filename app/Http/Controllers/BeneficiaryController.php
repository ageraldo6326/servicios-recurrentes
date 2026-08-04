<?php

namespace App\Http\Controllers;

use App\Http\Requests\BeneficiaryRequest;
use App\Models\Beneficiary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BeneficiaryController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', Beneficiary::class);

        return view('financial-agenda.beneficiaries.form', [
            'beneficiary' => new Beneficiary(['is_active' => true]),
        ]);
    }

    public function store(BeneficiaryRequest $request): RedirectResponse
    {
        Gate::authorize('create', Beneficiary::class);
        Beneficiary::query()->create($request->validated());

        return redirect()->route('financial-agenda.beneficiaries.index')
            ->with('success', 'Beneficiario creado.');
    }

    public function edit(Beneficiary $beneficiary): View
    {
        Gate::authorize('update', $beneficiary);

        return view('financial-agenda.beneficiaries.form', compact('beneficiary'));
    }

    public function update(BeneficiaryRequest $request, Beneficiary $beneficiary): RedirectResponse
    {
        Gate::authorize('update', $beneficiary);
        $beneficiary->update($request->validated());

        return redirect()->route('financial-agenda.beneficiaries.index')
            ->with('success', 'Beneficiario actualizado.');
    }
}
