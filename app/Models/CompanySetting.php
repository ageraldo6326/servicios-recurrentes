<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_name', 'website', 'email', 'phone', 'address', 'city',
        'province', 'postal_code', 'country', 'tax_id', 'logo_path',
    ];
}
