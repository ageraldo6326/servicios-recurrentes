<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\NotePageConflictException;
use App\Livewire\Notebooks\Workspace;
use App\Models\Notebook;
use App\Models\NotebookSection;
use App\Models\NotePage;
use App\Models\User;
use App\Services\Notebooks\NotebookService;
use App\Services\Notebooks\NotePageEditorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class NotebooksModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_navigate_and_reorder_their_notebook_hierarchy(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Workspace::class)
            ->set('notebookName', 'Operaciones')
            ->set('notebookDescription', 'Notas del equipo')
            ->call('createNotebook')
            ->set('sectionName', 'Clientes')
            ->call('createSection')
            ->call('createPage')
            ->assertSee('Página sin título');

        $notebook = Notebook::query()->where('user_id', $user->id)->firstOrFail();
        $section = $notebook->sections()->firstOrFail();
        $page = $section->pages()->firstOrFail();

        Livewire::actingAs($user)->test(Workspace::class)
            ->call('createSubpage', $page->id)
            ->call('movePageOrder', $page->id, 1)
            ->assertSet('sectionId', (string) $section->id);

        $this->assertSame($page->id, $section->pages()->whereNull('parent_id')->firstOrFail()->id);
        $this->assertSame($page->id, $section->pages()->whereNotNull('parent_id')->firstOrFail()->parent_id);
    }

    public function test_content_is_sanitized_and_saved_as_searchable_text(): void
    {
        $user = User::factory()->create();
        $page = $this->pageFor($user);

        Livewire::actingAs($user)->test(Workspace::class, ['page' => $page->id])
            ->call('saveEditor', $page->id, 'Reunión', '<h1>Plan</h1><script>alert(1)</script><p onclick="bad()">Cliente A</p><a href="javascript:alert(1)">mal</a>', 1);

        $page->refresh();
        $this->assertSame('Reunión', $page->title);
        $this->assertStringNotContainsString('<script', $page->html());
        $this->assertStringNotContainsString('onclick', $page->html());
        $this->assertStringNotContainsString('javascript:', $page->html());
        $this->assertStringContainsString('Cliente A', (string) $page->searchable_text);
    }

    public function test_optimistic_concurrency_prevents_overwriting_a_newer_page(): void
    {
        $user = User::factory()->create();
        $page = $this->pageFor($user);
        $editor = app(NotePageEditorService::class);

        $editor->save($page, $user, 'Primera', '<p>Uno</p>', 1);

        $this->expectException(NotePageConflictException::class);
        $editor->save($page->fresh(), $user, 'Segunda', '<p>Dos</p>', 1);
    }

    public function test_versions_are_restorable_without_deleting_later_history(): void
    {
        $user = User::factory()->create();
        $page = $this->pageFor($user);
        $editor = app(NotePageEditorService::class);
        Carbon::setTestNow('2026-08-14 10:00:00');
        $page = $editor->save($page, $user, 'Primera', '<p>Uno</p>', 1);
        Carbon::setTestNow('2026-08-14 10:06:00');
        $page = $editor->save($page, $user, 'Segunda', '<p>Dos</p>', $page->content_version);
        $oldVersion = $page->versions()->firstOrFail();

        $restored = $editor->restore($page, $oldVersion, $user);

        $this->assertSame('Primera', $restored->title);
        $this->assertSame(2, $restored->versions()->count());
        Carbon::setTestNow();
    }

    public function test_soft_delete_and_restore_keep_the_hierarchy_available(): void
    {
        $user = User::factory()->create();
        $notebook = Notebook::query()->create(['user_id' => $user->id, 'name' => 'Privado']);
        $section = $notebook->sections()->create(['name' => 'General']);
        $page = $section->pages()->create(['created_by' => $user->id, 'updated_by' => $user->id]);
        $service = app(NotebookService::class);

        $service->softDeleteNotebook($notebook);
        $this->assertSoftDeleted('notebooks', ['id' => $notebook->id]);
        $this->assertSoftDeleted('notebook_sections', ['id' => $section->id]);
        $this->assertSoftDeleted('note_pages', ['id' => $page->id]);

        $service->restoreNotebook($notebook->fresh());
        $this->assertDatabaseHas('notebooks', ['id' => $notebook->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('notebook_sections', ['id' => $section->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('note_pages', ['id' => $page->id, 'deleted_at' => null]);
    }

    public function test_users_cannot_open_other_users_pages_or_download_their_files(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $page = $this->pageFor($owner);
        Storage::disk('local')->put('notebooks/private.pdf', 'private');
        $attachment = $page->attachments()->create(['user_id' => $owner->id, 'disk' => 'local', 'path' => 'notebooks/private.pdf', 'original_name' => 'private.pdf', 'stored_name' => 'private.pdf', 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size_bytes' => 7]);

        $this->actingAs($intruder)->get(route('notebooks.index', ['page' => $page->id]))->assertNotFound();
        $this->actingAs($intruder)->get(route('notebooks.attachments.show', $attachment))->assertForbidden();
    }

    public function test_owner_can_upload_allowed_attachment_and_disallowed_type_is_rejected(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $page = $this->pageFor($user);

        Livewire::actingAs($user)->test(Workspace::class, ['pageId' => (string) $page->id])
            ->set('attachmentUpload', UploadedFile::fake()->create('evidencia.pdf', 12, 'application/pdf'));

        $this->assertSame(1, $page->attachments()->count());

        Livewire::actingAs($user)->test(Workspace::class, ['pageId' => (string) $page->id])
            ->set('attachmentUpload', UploadedFile::fake()->create('riesgo.exe', 12, 'application/octet-stream'))
            ->assertHasErrors('attachmentUpload');
    }

    private function pageFor(User $user): NotePage
    {
        $notebook = Notebook::query()->create(['user_id' => $user->id, 'name' => 'Cuaderno']);
        $section = NotebookSection::query()->create(['notebook_id' => $notebook->id, 'name' => 'General']);

        return NotePage::query()->create(['notebook_section_id' => $section->id, 'created_by' => $user->id, 'updated_by' => $user->id]);
    }
}
