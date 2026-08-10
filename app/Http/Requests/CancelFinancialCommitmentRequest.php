<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelFinancialCommitmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:5000'],
        ];
    }
}
