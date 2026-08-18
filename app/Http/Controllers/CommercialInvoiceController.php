<?php

namespace App\Http\Controllers;

use App\Enums\CommercialInvoiceStatus;
use App\Http\Requests\CommercialAnalyticsFilterRequest;
use App\Http\Requests\CommercialDocumentRequest;
use App\Models\Client;
use App\Models\CommercialInvoice;
use App\Models\CompanySetting;
use App\Services\CommercialAnalyticsService;
use App\Services\CommercialDocumentService;
use Illuminate\Support\Facades\Mail;

class CommercialInvoiceController extends Controller
{
    public function index(CommercialAnalyticsFilterRequest $request, CommercialAnalyticsService $analytics)
    {
        $filters = $request->validated();
        ['from' => $from, 'to' => $to] = $analytics->period($filters['date_from'] ?? null, $filters['date_to'] ?? null);
        $dateColumn = match ($filters['date_field'] ?? 'issue') {
            'due' => 'due_date',
            'paid' => 'paid_at',
            default => 'issue_date',
        };

        $invoices = CommercialInvoice::with('client', 'items')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('number', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($clientQuery) => $clientQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when(($filters['date_field'] ?? 'issue') === 'paid', function ($query) use ($from, $to): void {
                $query->where('status', CommercialInvoiceStatus::Paid)
                    ->where(function ($paymentQuery) use ($from, $to): void {
                        $paymentQuery->whereBetween('paid_at', [$from, $to])
                            ->orWhere(function ($legacyQuery) use ($from, $to): void {
                                $legacyQuery->whereNull('paid_at')
                                    ->whereDate('issue_date', '>=', $from->toDateString())
                                    ->whereDate('issue_date', '<=', $to->toDateString());
                            });
                    });
            })
            ->when(($filters['date_field'] ?? 'issue') !== 'paid' && ($filters['date_from'] ?? null), fn ($query) => $query->whereDate($dateColumn, '>=', $from->toDateString()))
            ->when(($filters['date_field'] ?? 'issue') !== 'paid' && ($filters['date_to'] ?? null), fn ($query) => $query->whereDate($dateColumn, '<=', $to->toDateString()))
            ->latest('issue_date')
            ->paginate(15)
            ->withQueryString();

        return view('commercial.invoices.index', [
            'invoices' => $invoices,
            'invoiceSummary' => $analytics->invoiceSummary($from, $to),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function create(CommercialDocumentService $service)
    {
        return view('commercial.form', ['type' => 'invoice', 'document' => new CommercialInvoice(['number' => $service->nextNumber('invoice'), 'issue_date' => now(), 'status' => CommercialInvoiceStatus::Draft]), 'clients' => Client::orderBy('name')->get(), 'items' => [[]]]);
    }

    public function store(CommercialDocumentRequest $request, CommercialDocumentService $service)
    {
        $document = $service->save(new CommercialInvoice, $request->safe()->except('items'), $request->validated('items'), auth()->id());

        return redirect()->route('commercial.invoices.edit', $document)->with('success', 'Factura guardada.');
    }

    public function edit(CommercialInvoice $invoice)
    {
        return view('commercial.form', ['type' => 'invoice', 'document' => $invoice->load('items'), 'clients' => Client::orderBy('name')->get(), 'items' => $invoice->items->toArray() ?: [[]]]);
    }

    public function update(CommercialDocumentRequest $request, CommercialInvoice $invoice, CommercialDocumentService $service)
    {
        $service->save($invoice, $request->safe()->except('items'), $request->validated('items'), auth()->id());

        return back()->with('success', 'Factura actualizada.');
    }

    public function duplicate(CommercialInvoice $invoice, CommercialDocumentService $service)
    {
        $copy = $invoice->load('items')->replicate(['number', 'status']);
        $copy->number = $service->nextNumber('invoice');
        $copy->status = CommercialInvoiceStatus::Draft;
        $copy->created_by = auth()->id();
        $copy->save();
        foreach ($invoice->items as $item) {
            $copy->items()->create($item->only(['concept', 'description', 'quantity', 'unit', 'unit_price', 'discount', 'tax_rate', 'total']));
        }

        return redirect()->route('commercial.invoices.edit', $copy)->with('success', 'Factura duplicada.');
    }

    public function pdf(CommercialInvoice $invoice)
    {
        return view('commercial.pdf', ['document' => $invoice->load('client', 'items'), 'type' => 'Factura', 'company' => CompanySetting::first()]);
    }

    public function email(CommercialInvoice $invoice)
    {
        abort_unless($invoice->client->commercial_email, 422, 'El cliente no tiene correo comercial.');
        Mail::raw("Adjuntamos la factura {$invoice->number}.", fn ($message) => $message->to($invoice->client->commercial_email)->subject("Factura {$invoice->number}"));

        return back()->with('success', 'Factura enviada por correo.');
    }
}
