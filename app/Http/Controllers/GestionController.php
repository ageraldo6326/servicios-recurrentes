<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ContractedService;
use App\Models\Gestion;
use Illuminate\Http\Request;

class GestionController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $gestions = Gestion::with('client', 'contractedService.catalogService')->when($search !== '', fn ($query) => $query->where('type', 'like', "%{$search}%")->orWhere('result', 'like', "%{$search}%")->orWhereHas('client', fn ($client) => $client->where('name', 'like', "%{$search}%")))->latest('occurred_at')->paginate(20)->withQueryString();

        return view('gestions.index', compact('gestions', 'search'));
    }

    public function create()
    {
        return view('gestions.form', ['gestion' => new Gestion(['occurred_at' => now()]), 'clients' => Client::orderBy('name')->get(), 'services' => ContractedService::with('client', 'catalogService')->where('status', 'active')->get()]);
    }

    public function store(Request $request)
    {
        Gestion::create($request->validate(['client_id' => ['required', 'exists:clients,id'], 'contracted_service_id' => ['required', 'exists:contracted_services,id'], 'type' => ['required', 'string', 'max:100'], 'occurred_at' => ['required', 'date'], 'result' => ['required', 'string', 'max:255'], 'phone_used' => ['nullable', 'string', 'max:50'], 'promised_payment_date' => ['nullable', 'date'], 'next_follow_up_at' => ['nullable', 'date'], 'observations' => ['nullable', 'string']]));

        return redirect()->route('gestions.index')->with('success', 'Gestión registrada.');
    }
}
