<?php

declare(strict_types=1);

namespace App\Services\Notebooks;

use App\Models\Notebook;
use App\Models\NotebookSection;
use App\Models\NotePage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class NotebookService
{
    public function createNotebook(User $user, string $name, ?string $description = null): Notebook
    {
        return Notebook::query()->create(['user_id' => $user->id, 'name' => $name, 'description' => $description, 'position' => $this->nextPosition(Notebook::query()->where('user_id', $user->id))]);
    }

    public function createSection(Notebook $notebook, string $name): NotebookSection
    {
        return $notebook->sections()->create(['name' => $name, 'position' => $this->nextPosition($notebook->sections())]);
    }

    public function createPage(NotebookSection $section, User $user, ?NotePage $parent = null): NotePage
    {
        return $section->pages()->create(['parent_id' => $parent?->id, 'created_by' => $user->id, 'updated_by' => $user->id, 'position' => $this->nextPosition($section->pages()->where('parent_id', $parent?->id)), 'last_edited_at' => now()]);
    }

    public function softDeleteNotebook(Notebook $notebook): void
    {
        DB::transaction(function () use ($notebook): void {
            $notebook->sections()->each(fn (NotebookSection $section) => $this->softDeleteSection($section));
            $notebook->delete();
        });
    }

    public function softDeleteSection(NotebookSection $section): void
    {
        DB::transaction(function () use ($section): void {
            $section->pages()->each(fn (NotePage $page) => $page->delete());
            $section->delete();
        });
    }

    public function restoreNotebook(Notebook $notebook): void
    {
        DB::transaction(function () use ($notebook): void {
            $notebook->restore();
            $notebook->sections()->withTrashed()->each(function (NotebookSection $section): void {
                $section->restore();
                $section->pages()->withTrashed()->restore();
            });
        });
    }

    public function restoreSection(NotebookSection $section): void
    {
        DB::transaction(function () use ($section): void {
            $section->notebook()->withTrashed()->firstOrFail()->restore();
            $section->restore();
            $section->pages()->withTrashed()->restore();
        });
    }

    public function restorePage(NotePage $page): void
    {
        DB::transaction(function () use ($page): void {
            $section = $page->section()->withTrashed()->firstOrFail();
            $section->notebook()->withTrashed()->firstOrFail()->restore();
            $section->restore();
            $page->restore();
            $page->children()->withTrashed()->restore();
        });
    }

    private function nextPosition($query): int
    {
        return ((int) $query->max('position')) + 1;
    }
}
