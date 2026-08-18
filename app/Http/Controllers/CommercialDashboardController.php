<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommercialAnalyticsFilterRequest;
use App\Models\CommercialInvoice;
use App\Models\CommercialQuote;
use App\Services\CommercialAnalyticsService;

class CommercialDashboardController extends Controller
{
    public function __invoke(CommercialAnalyticsFilterRequest $request, CommercialAnalyticsService $analytics)
    {
        $filters = $request->validated();
        ['from' => $from, 'to' => $to] = $analytics->period($filters['date_from'] ?? null, $filters['date_to'] ?? null);

        return view('commercial.dashboard', [
            'from' => $from,
            'to' => $to,
            'invoiceSummary' => $analytics->invoiceSummary($from, $to),
            'quoteSummary' => $analytics->quoteSummary($from, $to),
            'latestQuotes' => CommercialQuote::with('client', 'items')->latest()->take(5)->get(),
            'latestInvoices' => CommercialInvoice::with('client', 'items')->latest()->take(5)->get(),
            'pendingInvoices' => CommercialInvoice::with('client', 'items')
                ->whereIn('status', ['pending', 'overdue'])
                ->whereNotNull('due_date')
                ->orderBy('due_date')
                ->take(5)
                ->get(),
            'openQuotesForFollowUp' => CommercialQuote::with('client', 'items')
                ->whereIn('status', ['draft', 'sent', 'viewed'])
                ->whereNotNull('due_date')
                ->orderBy('due_date')
                ->take(5)
                ->get(),
        ]);
    }
}
