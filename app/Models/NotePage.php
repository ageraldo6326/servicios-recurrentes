<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class NotePage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['notebook_section_id', 'parent_id', 'created_by', 'updated_by', 'title', 'content_json', 'searchable_text', 'position', 'is_favorite', 'content_version', 'last_edited_at'];

    protected function casts(): array
    {
        return ['content_json' => 'array', 'is_favorite' => 'boolean', 'last_edited_at' => 'immutable_datetime'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(NotebookSection::class, 'notebook_section_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(NotePageVersion::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(NoteAttachment::class);
    }

    public function displayTitle(): string
    {
        return trim((string) $this->title) !== '' ? $this->title : 'Página sin título';
    }

    public function html(): string
    {
        return (string) data_get($this->content_json, 'html', '');
    }
}
