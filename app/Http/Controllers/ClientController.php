<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $clients = Client::withCount('contractedServices')->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))->orderBy('name')->paginate(15)->withQueryString();

        return view('clients.index', compact('clients', 'search'));
    }

    public function create()
    {
        return view('clients.form', ['client' => new Client]);
    }

    public function store(Request $request)
    {
        $client = Client::create($this->validated($request));

        return redirect()->route('clients.index')->with('success', 'Cliente creado.');
    }

    public function edit(Client $client)
    {
        return view('clients.form', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $client->update($this->validated($request));

        return redirect()->route('clients.index')->with('success', 'Cliente actualizado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:255'], 'phone' => ['required', 'string', 'max:50'], 'contact_name' => ['nullable', 'string', 'max:255'], 'contact_position' => ['nullable', 'string', 'max:255'], 'commercial_email' => ['nullable', 'email', 'max:255'], 'commercial_phone' => ['nullable', 'string', 'max:50'], 'commercial_address' => ['nullable', 'string', 'max:255'], 'city' => ['nullable', 'string', 'max:100'], 'province' => ['nullable', 'string', 'max:100'], 'country' => ['nullable', 'string', 'max:100'], 'tax_id' => ['nullable', 'string', 'max:100'], 'payment_terms' => ['nullable', 'string', 'max:100'], 'preferred_currency' => ['nullable', 'string', 'size:3'], 'commercial_notes' => ['nullable', 'string']]);
    }
}
