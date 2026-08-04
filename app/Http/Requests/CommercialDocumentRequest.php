<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommercialDocumentRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'], 'number' => ['required', 'string', 'max:50'],
            'issue_date' => ['required', 'date'], 'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'currency' => ['required', 'string', 'size:3'], 'discount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,sent,viewed,accepted,rejected,expired,converted,pending,paid,partial,overdue,cancelled'],
            'notes' => ['nullable', 'string'], 'terms' => ['nullable', 'string'], 'comments' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'], 'items.*.concept' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'], 'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', 'string', 'max:30'], 'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'], 'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
