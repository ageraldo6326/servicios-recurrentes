<?php

namespace App\Http\Controllers;

use App\Enums\ProviderInvoiceStatus;
use App\Models\Provider;
use App\Models\ProviderInvoice;
use Illuminate\Http\Request;

class ProviderInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $invoices = ProviderInvoice::with('provider')->when($search !== '', fn ($query) => $query->where('status', 'like', "%{$search}%")->orWhereHas('provider', fn ($provider) => $provider->where('name', 'like', "%{$search}%")))->orderBy('due_date')->paginate(20)->withQueryString();

        return view('provider-invoices.index', compact('invoices', 'search'));
    }

    public function create()
    {
        return view('provider-invoices.form', ['invoice' => new ProviderInvoice(['status' => ProviderInvoiceStatus::Pending->value, 'currency' => 'USD']), 'providers' => Provider::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        ProviderInvoice::create($request->validate(['provider_id' => ['required', 'exists:providers,id'], 'amount' => ['required', 'numeric', 'min:0'], 'currency' => ['required', 'string', 'size:3'], 'due_date' => ['required', 'date'], 'status' => ['required', 'in:pending,partial,paid'], 'observations' => ['nullable', 'string']]));

        return redirect()->route('provider-invoices.index')->with('success', 'Factura de proveedor registrada.');
    }
}
