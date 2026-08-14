<?php

declare(strict_types=1);

namespace App\Services\Notebooks;

use App\Exceptions\NotePageConflictException;
use App\Models\NotePage;
use App\Models\NotePageVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class NotePageEditorService
{
    public function __construct(private readonly NoteContentSanitizer $sanitizer) {}

    public function save(NotePage $page, User $user, string $title, string $html, int $expectedVersion): NotePage
    {
        return DB::transaction(function () use ($page, $user, $title, $html, $expectedVersion): NotePage {
            $page = NotePage::query()->lockForUpdate()->findOrFail($page->id);
            if ($page->content_version !== $expectedVersion) {
                throw new NotePageConflictException('La página fue modificada en otra pestaña.');
            }
            $content = $this->sanitizer->sanitize($html);
            $title = trim($title);
            $changed = $title !== (string) $page->title || $content['html'] !== $page->html();
            if (! $changed) {
                return $page;
            }
            $this->createVersionIfDue($page, $user);
            $page->update([
                'title' => $title === '' ? null : $title,
                'content_json' => ['type' => 'flow-document', 'html' => $content['html']],
                'searchable_text' => trim($title.' '.$content['text']) ?: null,
                'updated_by' => $user->id,
                'content_version' => $page->content_version + 1,
                'last_edited_at' => now(),
            ]);

            return $page->fresh();
        });
    }

    public function restore(NotePage $page, NotePageVersion $version, User $user): NotePage
    {
        return DB::transaction(function () use ($page, $version, $user): NotePage {
            $page = NotePage::query()->lockForUpdate()->findOrFail($page->id);
            NotePageVersion::query()->create(['note_page_id' => $page->id, 'user_id' => $user->id, 'title' => $page->title, 'content_json' => $page->content_json, 'content_version' => $page->content_version]);
            $html = (string) data_get($version->content_json, 'html', '');
            $content = $this->sanitizer->sanitize($html);
            $title = trim((string) $version->title);
            $page->update(['title' => $title === '' ? null : $title, 'content_json' => ['type' => 'flow-document', 'html' => $content['html']], 'searchable_text' => trim($title.' '.$content['text']) ?: null, 'updated_by' => $user->id, 'content_version' => $page->content_version + 1, 'last_edited_at' => now()]);

            return $page->fresh();
        });
    }

    private function createVersionIfDue(NotePage $page, User $user): void
    {
        if ($page->html() === '' && trim((string) $page->title) === '') {
            return;
        }
        $latest = $page->versions()->latest('id')->first();
        if ($latest?->created_at?->gt(now()->subMinutes(config('notebooks.version_interval_minutes')))) {
            return;
        }
        NotePageVersion::query()->create(['note_page_id' => $page->id, 'user_id' => $user->id, 'title' => $page->title, 'content_json' => $page->content_json, 'content_version' => $page->content_version]);
    }
}
