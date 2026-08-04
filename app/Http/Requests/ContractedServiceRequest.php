<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractedServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'catalog_service_id' => ['required', 'exists:catalog_services,id'],
            'provider_id' => ['required', 'exists:providers,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'price_currency' => ['required', 'string', 'size:3'],
            'cost' => ['required', 'numeric', 'min:0'],
            'cost_currency' => ['required', 'string', 'size:3'],
            'ip' => ['nullable', 'string', 'max:255'],
            'billing_day' => ['required', 'integer', 'between:1,31'],
            'starts_at' => ['required', 'date'],
            'observations' => ['nullable', 'string'],
        ];
    }
}
