<?php

namespace App\Http\Requests;

use App\Enums\CommercialInvoiceStatus;
use App\Enums\CommercialQuoteStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommercialDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $statuses = $this->routeIs('commercial.quotes.*')
            ? array_column(CommercialQuoteStatus::cases(), 'value')
            : array_column(CommercialInvoiceStatus::cases(), 'value');

        if (! $this->routeIs('commercial.quotes.*') && $this->route('invoice')?->status !== CommercialInvoiceStatus::Partial) {
            $statuses = array_values(array_filter($statuses, fn (string $status): bool => $status !== CommercialInvoiceStatus::Partial->value));
        }

        return [
            'client_id' => ['required', 'exists:clients,id'], 'number' => ['required', 'string', 'max:50'],
            'issue_date' => ['required', 'date'], 'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'currency' => ['required', 'string', 'size:3'], 'discount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in($statuses)],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'], 'terms' => ['nullable', 'string'], 'comments' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'], 'items.*.concept' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'], 'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', 'string', 'max:30'], 'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'], 'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
