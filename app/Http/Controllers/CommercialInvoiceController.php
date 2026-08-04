<?php

namespace App\Http\Controllers;

use App\Enums\CommercialInvoiceStatus;
use App\Http\Requests\CommercialDocumentRequest;
use App\Models\Client;
use App\Models\CommercialInvoice;
use App\Models\CompanySetting;
use App\Services\CommercialDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CommercialInvoiceController extends Controller
{
    public function index(Request $request) { $invoices = CommercialInvoice::with('client')->when($request->search, fn ($q, $s) => $q->where('number', 'like', "%{$s}%")->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$s}%")))->latest()->paginate(15)->withQueryString(); return view('commercial.invoices.index', compact('invoices')); }
    public function create(CommercialDocumentService $service) { return view('commercial.form', ['type' => 'invoice', 'document' => new CommercialInvoice(['number' => $service->nextNumber('invoice'), 'issue_date' => now(), 'status' => CommercialInvoiceStatus::Draft]), 'clients' => Client::orderBy('name')->get(), 'items' => [[]]]); }
    public function store(CommercialDocumentRequest $request, CommercialDocumentService $service) { $document = $service->save(new CommercialInvoice, $request->safe()->except('items'), $request->validated('items'), auth()->id()); return redirect()->route('commercial.invoices.edit', $document)->with('success', 'Factura guardada.'); }
    public function edit(CommercialInvoice $invoice) { return view('commercial.form', ['type' => 'invoice', 'document' => $invoice->load('items'), 'clients' => Client::orderBy('name')->get(), 'items' => $invoice->items->toArray() ?: [[]]]); }
    public function update(CommercialDocumentRequest $request, CommercialInvoice $invoice, CommercialDocumentService $service) { $service->save($invoice, $request->safe()->except('items'), $request->validated('items'), auth()->id()); return back()->with('success', 'Factura actualizada.'); }
    public function duplicate(CommercialInvoice $invoice, CommercialDocumentService $service) { $copy = $invoice->load('items')->replicate(['number', 'status']); $copy->number = $service->nextNumber('invoice'); $copy->status = CommercialInvoiceStatus::Draft; $copy->created_by = auth()->id(); $copy->save(); foreach ($invoice->items as $item) $copy->items()->create($item->only(['concept','description','quantity','unit','unit_price','discount','tax_rate','total'])); return redirect()->route('commercial.invoices.edit', $copy)->with('success', 'Factura duplicada.'); }
    public function pdf(CommercialInvoice $invoice) { return view('commercial.pdf', ['document' => $invoice->load('client', 'items'), 'type' => 'Factura', 'company' => CompanySetting::first()]); }
    public function email(CommercialInvoice $invoice) { abort_unless($invoice->client->commercial_email, 422, 'El cliente no tiene correo comercial.'); Mail::raw("Adjuntamos la factura {$invoice->number}.", fn ($message) => $message->to($invoice->client->commercial_email)->subject("Factura {$invoice->number}")); return back()->with('success', 'Factura enviada por correo.'); }
}
