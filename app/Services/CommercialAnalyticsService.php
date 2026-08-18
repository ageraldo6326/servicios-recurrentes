<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CommercialInvoiceStatus;
use App\Enums\CommercialQuoteStatus;
use App\Models\CommercialInvoice;
use App\Models\CommercialQuote;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class CommercialAnalyticsService
{
    /**
     * @return array{from: CarbonImmutable, to: CarbonImmutable}
     */
    public function period(?string $dateFrom, ?string $dateTo): array
    {
        $from = $dateFrom
            ? CarbonImmutable::parse($dateFrom)->startOfDay()
            : now()->toImmutable()->startOfMonth();
        $to = $dateTo
            ? CarbonImmutable::parse($dateTo)->endOfDay()
            : now()->toImmutable()->endOfMonth();

        return compact('from', 'to');
    }

    /**
     * @return array<string, int|array<string, float>>
     */
    public function invoiceSummary(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $issued = $this->invoiceQuery()
            ->whereDate('issue_date', '>=', $from->toDateString())
            ->whereDate('issue_date', '<=', $to->toDateString())
            ->get();

        $paid = $this->invoiceQuery()
            ->where('status', CommercialInvoiceStatus::Paid)
            ->where(function (Builder $query) use ($from, $to): void {
                $query->whereBetween('paid_at', [$from, $to])
                    ->orWhere(function (Builder $legacyQuery) use ($from, $to): void {
                        $legacyQuery->whereNull('paid_at')
                            ->whereDate('issue_date', '>=', $from->toDateString())
                            ->whereDate('issue_date', '<=', $to->toDateString());
                    });
            })
            ->get();

        $followUp = $this->invoiceQuery()
            ->whereIn('status', [CommercialInvoiceStatus::Pending->value, CommercialInvoiceStatus::Overdue->value])
            ->whereDate('due_date', '>=', $from->toDateString())
            ->whereDate('due_date', '<=', $to->toDateString())
            ->get();

        $overdue = $this->invoiceQuery()
            ->whereIn('status', [CommercialInvoiceStatus::Pending->value, CommercialInvoiceStatus::Overdue->value])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->get();

        return [
            'issued_count' => $issued->count(),
            'issued_totals' => $this->totalsByCurrency($issued),
            'paid_count' => $paid->count(),
            'paid_totals' => $this->totalsByCurrency($paid),
            'pending_count' => $followUp->count(),
            'pending_totals' => $this->totalsByCurrency($followUp),
            'overdue_count' => $overdue->count(),
            'overdue_totals' => $this->totalsByCurrency($overdue),
        ];
    }

    /**
     * @return array<string, int|array<string, float>>
     */
    public function quoteSummary(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $created = $this->quoteQuery()
            ->whereDate('issue_date', '>=', $from->toDateString())
            ->whereDate('issue_date', '<=', $to->toDateString())
            ->get();
        $open = $created->whereIn('status', [
            CommercialQuoteStatus::Draft->value,
            CommercialQuoteStatus::Sent->value,
            CommercialQuoteStatus::Viewed->value,
        ]);
        $converted = $this->quoteQuery()
            ->whereBetween('converted_at', [$from, $to])
            ->get();
        $convertedFromCreated = $created->where('status', CommercialQuoteStatus::Converted)->count();

        return [
            'created_count' => $created->count(),
            'created_totals' => $this->totalsByCurrency($created),
            'open_count' => $open->count(),
            'open_totals' => $this->totalsByCurrency($open),
            'converted_count' => $converted->count(),
            'converted_totals' => $this->totalsByCurrency($converted),
            'conversion_rate' => $created->isEmpty() ? 0 : round(($convertedFromCreated / $created->count()) * 100),
        ];
    }

    /**
     * @return Builder<CommercialInvoice>
     */
    public function invoiceQuery(): Builder
    {
        return CommercialInvoice::query()->with('items');
    }

    /**
     * @return Builder<CommercialQuote>
     */
    public function quoteQuery(): Builder
    {
        return CommercialQuote::query()->with('items');
    }

    /**
     * @param  Collection<int, CommercialInvoice|CommercialQuote>  $documents
     */
    private function totalsByCurrency(Collection $documents): array
    {
        return $documents
            ->groupBy('currency')
            ->map(fn (Collection $currencyDocuments): float => round((float) $currencyDocuments->sum(fn (CommercialInvoice|CommercialQuote $document): float => $document->total), 2))
            ->sortKeys()
            ->all();
    }
}
