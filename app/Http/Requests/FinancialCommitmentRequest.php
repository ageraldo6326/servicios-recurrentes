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
            'cutoff_day' => ['nullable', 'integer', 'between:1,31', Rule::requiredIf(fn (): bool => $this->boolean('has_cutoff'))],
            'due_day' => ['required', 'integer', 'between:1,31'],
            'is_active' => ['boolean'],
            'observations' => ['nullable', 'string'],
        ];
    }
}
