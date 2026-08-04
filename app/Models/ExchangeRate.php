<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = ['rate', 'effective_date'];

    protected $casts = [
        'rate' => 'decimal:4',
        'effective_date' => 'date',
    ];
}
