<?php

namespace App\Http\Requests;

use App\Enums\CallMonitoringPlatform;
use App\Enums\ContractedServiceStatus;
use App\Models\ContractedService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'ip' => Rule::when(
                $this->boolean('call_monitoring_enabled'),
                ['required', 'ip', 'max:45'],
                ['nullable', 'string', 'max:255'],
            ),
            'call_monitoring_platform' => [Rule::requiredIf($this->boolean('call_monitoring_enabled')), 'nullable', Rule::enum(CallMonitoringPlatform::class)],
            'call_monitoring_enabled' => ['required', 'boolean'],
            'inactivity_threshold_hours' => ['required', 'integer', 'between:1,8760'],
            'report_delay_threshold_hours' => ['required', 'integer', 'between:1,8760'],
            'billing_day' => ['required', 'integer', 'between:1,31'],
            'starts_at' => ['required', 'date'],
            'observations' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $ip = $this->input('ip');

        $this->merge([
            'call_monitoring_enabled' => $this->boolean('call_monitoring_enabled'),
            'inactivity_threshold_hours' => $this->input('inactivity_threshold_hours', config('services.call_activity.default_inactivity_threshold_hours', 48)),
            'report_delay_threshold_hours' => $this->input('report_delay_threshold_hours', config('services.call_activity.default_report_delay_threshold_hours', 36)),
            'ip' => $this->boolean('call_monitoring_enabled') && is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) !== false
                ? inet_ntop(inet_pton($ip))
                : $ip,
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->boolean('call_monitoring_enabled') || $validator->errors()->has('ip')) {
                    return;
                }

                $currentService = $this->route('contracted_service');
                $currentId = $currentService instanceof ContractedService ? $currentService->id : null;
                $duplicateExists = ContractedService::query()
                    ->where('status', ContractedServiceStatus::Active->value)
                    ->where('call_monitoring_enabled', true)
                    ->when($currentId !== null, fn ($query) => $query->whereKeyNot($currentId))
                    ->where('ip', $this->input('ip'))
                    ->exists();

                if ($duplicateExists) {
                    $validator->errors()->add('ip', 'Esta IP ya identifica otro servicio activo con monitoreo.');
                }
            },
        ];
    }
}
