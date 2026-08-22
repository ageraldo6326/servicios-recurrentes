<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_name', 'website', 'email', 'phone', 'address', 'city',
        'province', 'postal_code', 'country', 'tax_id', 'logo_path',
        'timezone', 'upcoming_due_days',
    ];

    public static function configuredTimezone(): string
    {
        $timezone = static::query()->value('timezone');

        return is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : 'America/Santo_Domingo';
    }

    public static function configuredUpcomingDueDays(): int
    {
        $days = static::query()->value('upcoming_due_days');

        if (! is_numeric($days)) {
            return 7;
        }

        return min(255, max(1, (int) $days));
    }
}
