<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NotePageVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['note_page_id', 'user_id', 'title', 'content_json', 'content_version', 'created_at'];

    protected function casts(): array
    {
        return ['content_json' => 'array', 'created_at' => 'immutable_datetime'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(NotePage::class, 'note_page_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
