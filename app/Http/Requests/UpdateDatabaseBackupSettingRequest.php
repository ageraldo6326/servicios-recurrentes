<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\DatabaseBackupSetting;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateDatabaseBackupSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', DatabaseBackupSetting::query()->firstOrNew(['id' => 1])) ?? false;
    }

    public function rules(): array
    {
        return [
            'reminder_interval_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }
}
