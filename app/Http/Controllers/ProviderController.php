<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function index()
    {
        return view('providers.index', ['providers' => Provider::withCount('contractedServices')->orderBy('name')->paginate(15)]);
    }

    public function create()
    {
        return view('providers.form', ['provider' => new Provider(['accepts_partial_payments' => false])]);
    }

    public function store(Request $request)
    {
        Provider::create($request->validate(['name' => ['required', 'string', 'max:255'], 'payment_method' => ['required', 'string', 'max:255'], 'accepts_partial_payments' => ['boolean'], 'observations' => ['nullable', 'string']]));

        return redirect()->route('providers.index')->with('success', 'Proveedor creado.');
    }

    public function edit(Provider $provider)
    {
        return view('providers.form', compact('provider'));
    }

    public function update(Request $request, Provider $provider)
    {
        $provider->update($request->validate(['name' => ['required', 'string', 'max:255'], 'payment_method' => ['required', 'string', 'max:255'], 'accepts_partial_payments' => ['boolean'], 'observations' => ['nullable', 'string']]));

        return redirect()->route('providers.index')->with('success', 'Proveedor actualizado.');
    }
}
