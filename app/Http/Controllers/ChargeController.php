<?php

namespace App\Http\Controllers;

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Models\ContractedService;
use Illuminate\Http\Request;

class ChargeController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $charges = Charge::with('contractedService.client', 'contractedService.catalogService')->when($search !== '', fn ($query) => $query->whereHas('contractedService.client', fn ($client) => $client->where('name', 'like', "%{$search}%"))->orWhereHas('contractedService.catalogService', fn ($service) => $service->where('name', 'like', "%{$search}%")))->orderBy('due_date')->paginate(20)->withQueryString();

        return view('charges.index', compact('charges', 'search'));
    }

    public function create()
    {
        return view('charges.form', ['charge' => new Charge(['status' => ChargeStatus::Pending->value, 'currency' => 'USD']), 'services' => ContractedService::with('client', 'catalogService')->where('status', 'active')->get()]);
    }

    public function store(Request $request)
    {
        Charge::create($request->validate(['contracted_service_id' => ['required', 'exists:contracted_services,id'], 'status' => ['required', 'in:pending,partial,paid,overdue'], 'amount' => ['required', 'numeric', 'min:0'], 'currency' => ['required', 'string', 'size:3'], 'due_date' => ['required', 'date']]));

        return redirect()->route('charges.index')->with('success', 'Cobro registrado.');
    }
}
