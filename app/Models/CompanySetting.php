<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_name', 'website', 'email', 'phone', 'address', 'city',
        'province', 'postal_code', 'country', 'tax_id', 'logo_path',
        'timezone',
    ];

    public static function configuredTimezone(): string
    {
        $timezone = static::query()->value('timezone');

        return is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : 'America/Santo_Domingo';
    }
}
