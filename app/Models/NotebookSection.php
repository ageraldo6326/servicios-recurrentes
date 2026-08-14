<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class NotebookSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['notebook_id', 'name', 'color', 'position', 'archived_at'];

    protected function casts(): array
    {
        return ['archived_at' => 'immutable_datetime'];
    }

    public function notebook(): BelongsTo
    {
        return $this->belongsTo(Notebook::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(NotePage::class);
    }
}
