<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class NoteAttachment extends Model
{
    use SoftDeletes;

    protected $fillable = ['note_page_id', 'user_id', 'disk', 'path', 'original_name', 'stored_name', 'mime_type', 'extension', 'size_bytes', 'checksum'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(NotePage::class, 'note_page_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
