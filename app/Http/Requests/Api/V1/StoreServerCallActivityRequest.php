<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

final class StoreServerCallActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'server_ip' => ['bail', 'required', 'string', 'ip', 'max:45'],
            'last_outbound_at' => [
                'present',
                'nullable',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null) {
                        return;
                    }

                    if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/', $value) !== 1) {
                        $fail('The last_outbound_at field must be an ISO 8601 date with an explicit timezone.');

                        return;
                    }

                    $normalizedValue = str_ends_with($value, 'Z') ? substr($value, 0, -1).'+00:00' : $value;
                    $format = str_contains($normalizedValue, '.') ? '!Y-m-d\TH:i:s.uP' : '!Y-m-d\TH:i:sP';
                    $parsedDate = \DateTimeImmutable::createFromFormat($format, $normalizedValue);
                    $dateErrors = \DateTimeImmutable::getLastErrors();

                    if ($parsedDate === false || (is_array($dateErrors) && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
                        $fail('The last_outbound_at field must be a valid date.');

                        return;
                    }

                    $reportedAt = CarbonImmutable::instance($parsedDate);

                    $maxFutureAt = CarbonImmutable::now('UTC')->addMinutes((int) config('services.call_activity.max_future_minutes', 5));

                    if ($reportedAt->utc()->gt($maxFutureAt)) {
                        $fail('The last_outbound_at field is too far in the future.');
                    }
                },
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $unexpectedFields = array_diff(array_keys($this->all()), ['server_ip', 'last_outbound_at']);

                if ($unexpectedFields !== []) {
                    $validator->errors()->add('payload', 'Only server_ip and last_outbound_at are accepted.');
                }
            },
        ];
    }

    public function normalizedServerIp(): string
    {
        return inet_ntop(inet_pton((string) $this->validated('server_ip')));
    }

    public function lastOutboundAt(): ?CarbonImmutable
    {
        $value = $this->validated('last_outbound_at');

        return is_string($value) ? CarbonImmutable::parse($value)->utc() : null;
    }

    protected function failedValidation(Validator $validator): never
    {
        Log::notice('Call activity report rejected: invalid payload.', [
            'source_ip' => $this->ip(),
            'server_ip' => is_string($this->input('server_ip')) ? $this->input('server_ip') : null,
            'validation_fields' => array_keys($validator->errors()->toArray()),
        ]);

        throw new HttpResponseException(response()->json([
            'ok' => false,
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
