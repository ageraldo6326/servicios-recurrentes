<?php

namespace App\Http\Requests;

use App\Enums\FinancialCommitmentFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinancialCommitmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_cutoff' => $this->boolean('has_cutoff'),
            'is_credit_card' => $this->boolean('is_credit_card'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'beneficiary_id' => ['required', 'integer', 'exists:beneficiaries,id'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'frequency' => ['required', Rule::enum(FinancialCommitmentFrequency::class)],
            'suggested_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'has_cutoff' => ['boolean'],
            'is_credit_card' => ['boolean'],
            'cutoff_day' => ['nullable', 'integer', 'between:1,31', Rule::requiredIf(fn (): bool => $this->boolean('has_cutoff'))],
            'due_day' => ['required', 'integer', 'between:1,31'],
            'payment_safety_days' => ['nullable', 'integer', 'between:0,31'],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'current_balance' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'statement_balance' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'card_currency' => ['nullable', 'string', 'size:3'],
            'purchase_excellent_days' => ['nullable', 'integer', 'between:1,30'],
            'purchase_good_days' => ['nullable', 'integer', 'between:1,30'],
            'purchase_regular_days' => ['nullable', 'integer', 'between:1,30'],
            'cutoff_alert_days' => ['nullable', 'regex:/^\s*(\d{1,2}\s*,\s*)*\d{1,2}\s*$/'],
            'payment_alert_days' => ['nullable', 'regex:/^\s*(\d{1,2}\s*,\s*)*\d{1,2}\s*$/'],
            'activation_days_before_due' => ['nullable', 'integer', 'between:0,365'],
            'is_active' => ['boolean'],
            'observations' => ['nullable', 'string'],
        ];
    }
}
