<?php

namespace App\Http\Controllers;

use App\Enums\CommercialQuoteStatus;
use App\Models\CommercialInvoice;
use App\Models\CommercialQuote;

class CommercialDashboardController extends Controller
{
    public function __invoke()
    {
        $month = now()->startOfMonth();
        $invoices = CommercialInvoice::with('client', 'items')->where('issue_date', '>=', $month)->get();
        return view('commercial.dashboard', ['invoicesThisMonth' => $invoices->count(), 'billedThisMonth' => $invoices->sum->total, 'openQuotes' => CommercialQuote::whereIn('status', [CommercialQuoteStatus::Draft->value, CommercialQuoteStatus::Sent->value, CommercialQuoteStatus::Viewed->value])->count(), 'acceptedQuotes' => CommercialQuote::where('status', CommercialQuoteStatus::Accepted->value)->count(), 'latestQuotes' => CommercialQuote::with('client')->latest()->take(5)->get(), 'latestInvoices' => CommercialInvoice::with('client')->latest()->take(5)->get()]);
    }
}
