<?php

namespace App\Http\Controllers;

use App\Enums\CommercialQuoteStatus;
use App\Http\Requests\CommercialAnalyticsFilterRequest;
use App\Http\Requests\CommercialDocumentRequest;
use App\Models\Client;
use App\Models\CommercialInvoice;
use App\Models\CommercialQuote;
use App\Models\CompanySetting;
use App\Services\CommercialAnalyticsService;
use App\Services\CommercialDocumentService;
use Illuminate\Support\Facades\Mail;

class CommercialQuoteController extends Controller
{
    public function index(CommercialAnalyticsFilterRequest $request, CommercialAnalyticsService $analytics)
    {
        $filters = $request->validated();
        ['from' => $from, 'to' => $to] = $analytics->period($filters['date_from'] ?? null, $filters['date_to'] ?? null);
        $dateColumn = match ($filters['date_field'] ?? 'issue') {
            'due' => 'due_date',
            'converted' => 'converted_at',
            default => 'issue_date',
        };

        $quotes = CommercialQuote::with('client', 'items')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('number', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($clientQuery) => $clientQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($query) => $query->whereDate($dateColumn, '>=', $from->toDateString()))
            ->when($filters['date_to'] ?? null, fn ($query) => $query->whereDate($dateColumn, '<=', $to->toDateString()))
            ->latest('issue_date')
            ->paginate(15)
            ->withQueryString();

        return view('commercial.quotes.index', [
            'quotes' => $quotes,
            'quoteSummary' => $analytics->quoteSummary($from, $to),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function create()
    {
        return view('commercial.form', ['type' => 'quote', 'document' => new CommercialQuote(['number' => app(CommercialDocumentService::class)->nextNumber('quote'), 'issue_date' => now(), 'status' => CommercialQuoteStatus::Draft]), 'clients' => Client::orderBy('name')->get(), 'items' => [[]]]);
    }

    public function store(CommercialDocumentRequest $request, CommercialDocumentService $service)
    {
        $document = $service->save(new CommercialQuote, $request->safe()->except('items'), $request->validated('items'), auth()->id());

        return redirect()->route('commercial.quotes.edit', $document)->with('success', 'Cotización guardada.');
    }

    public function edit(CommercialQuote $quote)
    {
        return view('commercial.form', ['type' => 'quote', 'document' => $quote->load('items'), 'clients' => Client::orderBy('name')->get(), 'items' => $quote->items->toArray() ?: [[]]]);
    }

    public function update(CommercialDocumentRequest $request, CommercialQuote $quote, CommercialDocumentService $service)
    {
        $service->save($quote, $request->safe()->except('items'), $request->validated('items'), auth()->id());

        return back()->with('success', 'Cotización actualizada.');
    }

    public function duplicate(CommercialQuote $quote, CommercialDocumentService $service)
    {
        $copy = $quote->load('items')->replicate(['number', 'status', 'converted_at']);
        $copy->number = $service->nextNumber('quote');
        $copy->status = CommercialQuoteStatus::Draft;
        $copy->created_by = auth()->id();
        $copy->save();
        foreach ($quote->items as $item) {
            $copy->items()->create($item->only(['concept', 'description', 'quantity', 'unit', 'unit_price', 'discount', 'tax_rate', 'total']));
        }

        return redirect()->route('commercial.quotes.edit', $copy)->with('success', 'Cotización duplicada.');
    }

    public function convert(CommercialQuote $quote, CommercialDocumentService $service)
    {
        abort_unless($quote->status !== CommercialQuoteStatus::Converted, 422, 'La cotización ya fue convertida.');
        $invoice = $service->save(new CommercialInvoice, ['client_id' => $quote->client_id, 'quote_id' => $quote->id, 'created_by' => auth()->id(), 'number' => $service->nextNumber('invoice'), 'issue_date' => now()->toDateString(), 'due_date' => $quote->due_date, 'currency' => $quote->currency, 'discount' => $quote->discount, 'status' => 'draft', 'notes' => $quote->notes, 'terms' => $quote->terms, 'comments' => $quote->comments], $quote->items->toArray(), auth()->id());
        $quote->update(['status' => CommercialQuoteStatus::Converted, 'converted_at' => now(), 'updated_by' => auth()->id()]);

        return redirect()->route('commercial.invoices.edit', $invoice)->with('success', 'Factura creada desde la cotización.');
    }

    public function pdf(CommercialQuote $quote)
    {
        return view('commercial.pdf', ['document' => $quote->load('client', 'items'), 'type' => 'Cotización', 'company' => CompanySetting::first()]);
    }

    public function email(CommercialQuote $quote)
    {
        abort_unless($quote->client->commercial_email, 422, 'El cliente no tiene correo comercial.');
        Mail::raw("Adjuntamos la cotización {$quote->number}.", fn ($message) => $message->to($quote->client->commercial_email)->subject("Cotización {$quote->number}"));

        return back()->with('success', 'Cotización enviada por correo.');
    }
}
