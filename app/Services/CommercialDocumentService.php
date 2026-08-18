<?php

namespace App\Services;

use App\Enums\CommercialInvoiceStatus;
use App\Models\CommercialInvoice;
use App\Models\CommercialQuote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CommercialDocumentService
{
    public function save(Model $document, array $data, array $items, int $userId): Model
    {
        return DB::transaction(function () use ($document, $data, $items, $userId): Model {
            if ($document instanceof CommercialInvoice && ($data['status'] ?? null) === CommercialInvoiceStatus::Paid->value && ! $document->paid_at && empty($data['paid_at'])) {
                $data['paid_at'] = now();
            }

            $document->fill($data);
            $document->created_by ??= $userId;
            $document->updated_by = $userId;
            $document->save();
            $document->items()->delete();
            foreach ($items as $item) {
                $quantity = (float) $item['quantity'];
                $price = (float) $item['unit_price'];
                $discount = (float) ($item['discount'] ?? 0);
                $subtotal = max(0, $quantity * $price - $discount);
                $document->items()->create([...$item, 'total' => round($subtotal * (1 + ((float) ($item['tax_rate'] ?? 0) / 100)), 2)]);
            }
            $document->histories()->create(['user_id' => $userId, 'action' => $document->wasRecentlyCreated ? 'created' : 'updated', 'data' => ['number' => $document->number]]);

            return $document->load('items', 'client');
        });
    }

    public function nextNumber(string $type): string
    {
        $prefix = $type === 'quote' ? 'COT' : 'FAC';
        $model = $type === 'quote' ? CommercialQuote::class : CommercialInvoice::class;
        $last = $model::query()->latest('id')->value('number');
        $next = $last && preg_match('/(\d+)$/', $last, $match) ? ((int) $match[1]) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, now()->format('Y'), $next);
    }
}
