<?php

declare(strict_types=1);

namespace App\Livewire\Notebooks;

use App\Exceptions\NotePageConflictException;
use App\Models\NoteAttachment;
use App\Models\Notebook;
use App\Models\NotebookSection;
use App\Models\NotePage;
use App\Services\Notebooks\NotebookService;
use App\Services\Notebooks\NotePageEditorService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
final class Workspace extends Component
{
    use WithFileUploads, WithPagination;

    #[Url(as: 'notebook')]
    public string $notebookId = '';

    #[Url(as: 'section')]
    public string $sectionId = '';

    #[Url(as: 'page')]
    public string $pageId = '';

    #[Url]
    public string $view = 'browse';

    #[Url]
    public string $query = '';

    public string $notebookName = '';

    public string $notebookDescription = '';

    public string $sectionName = '';

    public string $renameValue = '';

    public ?int $renamingNotebookId = null;

    public ?int $renamingSectionId = null;

    public ?int $editingNotebookId = null;

    public ?int $moveTargetSectionId = null;

    public $attachmentUpload = null;

    public string $notice = '';

    public function mount(): void
    {
        $this->view = in_array($this->view, ['browse', 'recent', 'favorites', 'trash', 'search'], true) ? $this->view : 'browse';
        if ($this->pageId !== '') {
            $this->selectPage((int) $this->pageId);
        }
    }

    public function selectNotebook(int $id): void
    {
        $notebook = $this->ownedNotebook($id);
        $this->authorize('view', $notebook);
        $this->notebookId = (string) $notebook->id;
        $this->sectionId = $this->pageId = '';
        $this->view = 'browse';
    }

    public function selectSection(int $id): void
    {
        $section = $this->ownedSection($id);
        $this->authorize('view', $section);
        $this->notebookId = (string) $section->notebook_id;
        $this->sectionId = (string) $section->id;
        $this->pageId = '';
        $this->view = 'browse';
    }

    public function selectPage(int $id): void
    {
        $page = $this->ownedPage($id);
        $this->authorize('view', $page);
        $page->loadMissing('section');
        $this->notebookId = (string) $page->section->notebook_id;
        $this->sectionId = (string) $page->notebook_section_id;
        $this->pageId = (string) $page->id;
        $this->view = 'browse';
    }

    public function createNotebook(NotebookService $service): void
    {
        $this->authorize('create', Notebook::class);
        $data = $this->validate(['notebookName' => ['required', 'string', 'max:160'], 'notebookDescription' => ['nullable', 'string', 'max:5000']]);
        $notebook = $service->createNotebook(auth()->user(), trim($data['notebookName']), trim($data['notebookDescription']) ?: null);
        $this->notebookId = (string) $notebook->id;
        $this->sectionId = $this->pageId = '';
        $this->reset(['notebookName', 'notebookDescription']);
        $this->notice = 'Cuaderno creado.';
    }

    public function createSection(NotebookService $service): void
    {
        $data = $this->validate(['sectionName' => ['required', 'string', 'max:160']]);
        $notebook = $this->ownedNotebook((int) $this->notebookId);
        $this->authorize('update', $notebook);
        $section = $service->createSection($notebook, trim($data['sectionName']));
        $this->sectionId = (string) $section->id;
        $this->pageId = '';
        $this->sectionName = '';
        $this->notice = 'Sección creada.';
    }

    public function createPage(NotebookService $service): void
    {
        $section = $this->ownedSection((int) $this->sectionId);
        $this->authorize('update', $section);
        $page = $service->createPage($section, auth()->user());
        $this->selectPage($page->id);
        $this->dispatch('notebook-editor-focus', target: 'title');
    }

    public function createSubpage(int $parentId, NotebookService $service): void
    {
        $parent = $this->ownedPage($parentId);
        $this->authorize('update', $parent);
        $page = $service->createPage($parent->section, auth()->user(), $parent);
        $this->selectPage($page->id);
        $this->dispatch('notebook-editor-focus', target: 'title');
    }

    public function renameNotebook(int $id): void
    {
        $notebook = $this->ownedNotebook($id);
        $this->authorize('update', $notebook);
        $this->renameValue = $notebook->name;
        $this->renamingNotebookId = $id;
        $this->renamingSectionId = null;
    }

    public function renameSection(int $id): void
    {
        $section = $this->ownedSection($id);
        $this->authorize('update', $section);
        $this->renameValue = $section->name;
        $this->renamingSectionId = $id;
        $this->renamingNotebookId = null;
    }

    public function saveRename(): void
    {
        $data = $this->validate(['renameValue' => ['required', 'string', 'max:160']]);
        if ($this->renamingNotebookId !== null) {
            $notebook = $this->ownedNotebook($this->renamingNotebookId);
            $this->authorize('update', $notebook);
            $notebook->update(['name' => trim($data['renameValue'])]);
        }
        if ($this->renamingSectionId !== null) {
            $section = $this->ownedSection($this->renamingSectionId);
            $this->authorize('update', $section);
            $section->update(['name' => trim($data['renameValue'])]);
        }
        $this->reset(['renameValue', 'renamingNotebookId', 'renamingSectionId']);
        $this->notice = 'Nombre actualizado.';
    }

    public function cancelRename(): void
    {
        $this->reset(['renameValue', 'renamingNotebookId', 'renamingSectionId']);
    }

    public function editNotebook(int $id): void
    {
        $notebook = $this->ownedNotebook($id);
        $this->authorize('update', $notebook);
        $this->editingNotebookId = $notebook->id;
        $this->notebookName = $notebook->name;
        $this->notebookDescription = (string) $notebook->description;
    }

    public function saveNotebookDetails(): void
    {
        $data = $this->validate(['notebookName' => ['required', 'string', 'max:160'], 'notebookDescription' => ['nullable', 'string', 'max:5000']]);
        $notebook = $this->ownedNotebook((int) $this->editingNotebookId);
        $this->authorize('update', $notebook);
        $notebook->update(['name' => trim($data['notebookName']), 'description' => trim($data['notebookDescription']) ?: null]);
        $this->reset(['editingNotebookId', 'notebookName', 'notebookDescription']);
        $this->notice = 'Cuaderno actualizado.';
    }

    public function cancelNotebookEdit(): void
    {
        $this->reset(['editingNotebookId', 'notebookName', 'notebookDescription']);
    }

    public function toggleNotebookArchive(int $id): void
    {
        $notebook = $this->ownedNotebook($id);
        $this->authorize('update', $notebook);
        $notebook->update(['archived_at' => $notebook->archived_at ? null : now()]);
    }

    public function toggleSectionArchive(int $id): void
    {
        $section = $this->ownedSection($id);
        $this->authorize('update', $section);
        $section->update(['archived_at' => $section->archived_at ? null : now()]);
    }

    public function deleteNotebook(int $id, NotebookService $service): void
    {
        $notebook = $this->ownedNotebook($id);
        $this->authorize('delete', $notebook);
        $service->softDeleteNotebook($notebook);
        if ((int) $this->notebookId === $id) {
            $this->notebookId = $this->sectionId = $this->pageId = '';
        }
        $this->view = 'trash';
        $this->notice = 'Cuaderno enviado a la papelera.';
    }

    public function deleteSection(int $id, NotebookService $service): void
    {
        $section = $this->ownedSection($id);
        $this->authorize('delete', $section);
        $service->softDeleteSection($section);
        if ((int) $this->sectionId === $id) {
            $this->sectionId = $this->pageId = '';
        }
        $this->notice = 'Sección y sus páginas enviadas a la papelera.';
    }

    public function deletePage(int $id): void
    {
        $page = $this->ownedPage($id);
        $this->authorize('delete', $page);
        DB::transaction(function () use ($page): void {
            $page->children()->each(fn (NotePage $child) => $child->delete());
            $page->delete();
        });
        if ((int) $this->pageId === $id) {
            $this->pageId = '';
        }
        $this->notice = 'Página enviada a la papelera.';
    }

    public function restoreNotebook(int $id, NotebookService $service): void
    {
        $notebook = $this->ownedNotebook($id, true);
        $this->authorize('restore', $notebook);
        $service->restoreNotebook($notebook);
        $this->notice = 'Cuaderno restaurado.';
    }

    public function restoreSection(int $id, NotebookService $service): void
    {
        $section = $this->ownedSection($id, true);
        $this->authorize('restore', $section);
        $service->restoreSection($section);
        $this->notice = 'Sección restaurada con sus páginas.';
    }

    public function restorePage(int $id, NotebookService $service): void
    {
        $page = $this->ownedPage($id, true);
        $this->authorize('restore', $page);
        $service->restorePage($page);
        $this->notice = 'Página restaurada.';
    }

    public function moveNotebook(int $id, int $direction): void
    {
        $this->swap(Notebook::query()->where('user_id', auth()->id()), $id, $direction);
    }

    public function moveSection(int $id, int $direction): void
    {
        $section = $this->ownedSection($id);
        $this->swap(NotebookSection::query()->where('notebook_id', $section->notebook_id), $id, $direction);
    }

    public function movePageOrder(int $id, int $direction): void
    {
        $page = $this->ownedPage($id);
        $this->swap(NotePage::query()->where('notebook_section_id', $page->notebook_section_id)->where('parent_id', $page->parent_id), $id, $direction);
    }

    public function movePage(int $id, int $targetSectionId): void
    {
        $page = $this->ownedPage($id);
        $section = $this->ownedSection($targetSectionId);
        $this->authorize('update', $page);
        $this->authorize('update', $section);
        DB::transaction(function () use ($page, $section): void {
            if ($page->parent_id === null) {
                $page->children()->update(['notebook_section_id' => $section->id]);
            }
            $page->update(['notebook_section_id' => $section->id, 'parent_id' => null, 'position' => ((int) $section->pages()->whereNull('parent_id')->max('position')) + 1]);
        });
        $this->selectPage($page->id);
        $this->notice = 'Página movida.';
    }

    public function toggleFavorite(int $id): void
    {
        $page = $this->ownedPage($id);
        $this->authorize('update', $page);
        $page->update(['is_favorite' => ! $page->is_favorite]);
    }

    public function saveEditor(int $id, string $title, string $html, int $version, NotePageEditorService $editor): void
    {
        $page = $this->ownedPage($id);
        $this->authorize('update', $page);
        try {
            $page = $editor->save($page, auth()->user(), $title, $html, $version);
            $this->dispatch('notebook-page-saved', pageId: $page->id, version: $page->content_version);
        } catch (NotePageConflictException) {
            $current = $page->fresh();
            $this->dispatch('notebook-conflict', pageId: $id, title: $current->title ?? '', html: $current->html(), version: $current->content_version);
        } catch (\Throwable $exception) {
            report($exception);
            $this->dispatch('notebook-save-error', message: 'No se pudo guardar. Tu contenido permanece en esta pantalla para que puedas reintentar.');
        }
    }

    public function restoreVersion(int $versionId, NotePageEditorService $editor): void
    {
        $page = $this->ownedPage((int) $this->pageId);
        $version = $page->versions()->whereKey($versionId)->firstOrFail();
        $this->authorize('update', $page);
        $editor->restore($page, $version, auth()->user());
        $this->notice = 'Versión restaurada. El historial posterior se conserva.';
        $this->dispatch('notebook-editor-reload');
    }

    public function uploadAttachment(): void
    {
        $page = $this->ownedPage((int) $this->pageId);
        $this->authorize('update', $page);
        $extensions = implode(',', config('notebooks.allowed_extensions'));
        $mimetypes = implode(',', config('notebooks.allowed_mimetypes'));
        $this->validate(['attachmentUpload' => ['required', 'file', 'max:'.config('notebooks.max_attachment_kilobytes'), 'extensions:'.$extensions, 'mimetypes:'.$mimetypes]]);
        $file = $this->attachmentUpload;
        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = Str::uuid()->toString().($extension === '' ? '' : '.'.$extension);
        $disk = config('notebooks.disk');
        $path = $file->storeAs('notebooks/'.auth()->id().'/'.$page->id, $storedName, $disk);
        $attachment = $page->attachments()->create(['user_id' => auth()->id(), 'disk' => $disk, 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'stored_name' => $storedName, 'mime_type' => (string) $file->getMimeType(), 'extension' => $extension ?: null, 'size_bytes' => $file->getSize(), 'checksum' => hash_file('sha256', $file->getRealPath()) ?: null]);
        $this->reset('attachmentUpload');
        if ($attachment->isImage()) {
            $this->dispatch('notebook-image-inserted', url: route('notebooks.attachments.show', $attachment), alt: $attachment->original_name);
        }
        $this->notice = 'Archivo cargado.';
    }

    public function updatedAttachmentUpload(): void
    {
        if ($this->attachmentUpload !== null) {
            $this->uploadAttachment();
        }
    }

    public function deleteAttachment(int $id): void
    {
        $attachment = NoteAttachment::query()->where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('delete', $attachment);
        $attachment->delete();
        $this->notice = 'Archivo retirado de la página.';
    }

    public function updatedQuery(): void
    {
        $this->view = $this->query === '' ? 'browse' : 'search';
        $this->resetPage('resultsPage');
    }

    public function showView(string $view): void
    {
        $this->view = in_array($view, ['browse', 'recent', 'favorites', 'trash'], true) ? $view : 'browse';
    }

    public function render(): View
    {
        $userId = (int) auth()->id();
        $notebooks = Notebook::query()->where('user_id', $userId)->select(['id', 'name', 'description', 'color', 'position', 'archived_at'])->orderBy('position')->get();
        $sections = $this->notebookId === '' ? collect() : NotebookSection::query()->where('notebook_id', $this->notebookId)->select(['id', 'notebook_id', 'name', 'color', 'position', 'archived_at'])->orderBy('position')->get();
        $pages = $this->sectionId === '' ? collect() : NotePage::query()->where('notebook_section_id', $this->sectionId)->select(['id', 'notebook_section_id', 'parent_id', 'title', 'position', 'is_favorite', 'last_edited_at', 'content_version'])->orderBy('parent_id')->orderBy('position')->get();
        $selectedPage = $this->pageId === '' ? null : $this->ownedPage((int) $this->pageId)->load(['attachments' => fn ($query) => $query->select(['id', 'note_page_id', 'original_name', 'mime_type', 'size_bytes', 'created_at'])->latest(), 'versions' => fn ($query) => $query->with('user:id,name')->latest()->limit(10)]);
        $results = $this->view === 'search' ? $this->searchResults($userId) : null;
        $recentPages = $this->view === 'recent' ? $this->pageListing($userId)->orderByDesc('last_edited_at')->paginate(10, ['*'], 'resultsPage') : null;
        $favoritePages = $this->view === 'favorites' ? $this->pageListing($userId)->where('is_favorite', true)->orderByDesc('last_edited_at')->paginate(10, ['*'], 'resultsPage') : null;

        return view('livewire.notebooks.workspace', compact('notebooks', 'sections', 'pages', 'selectedPage', 'results', 'recentPages', 'favoritePages'));
    }

    private function searchResults(int $userId)
    {
        $term = trim($this->query);

        return $this->pageListing($userId)->when($term !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($term): void {
            $like = '%'.$term.'%';
            $query->where('title', 'like', $like)->orWhere('searchable_text', 'like', $like)->orWhereHas('section', fn (Builder $section) => $section->where('name', 'like', $like)->orWhereHas('notebook', fn (Builder $notebook) => $notebook->where('name', 'like', $like)));
        }))->orderByDesc('last_edited_at')->paginate(10, ['*'], 'resultsPage');
    }

    private function pageListing(int $userId): Builder
    {
        return NotePage::query()->whereHas('section.notebook', fn (Builder $query) => $query->where('user_id', $userId))->with(['section:id,notebook_id,name', 'section.notebook:id,name'])->select(['id', 'notebook_section_id', 'title', 'searchable_text', 'is_favorite', 'last_edited_at']);
    }

    private function swap(Builder $query, int $id, int $direction): void
    {
        $items = $query->orderBy('position')->get(['id', 'position']);
        $index = $items->search(fn ($item) => $item->id === $id);
        $target = $index === false ? false : $items->get($index + $direction);
        if ($target === false || $target === null) {
            return;
        }
        $current = $items->get($index);
        DB::transaction(function () use ($current, $target): void {
            DB::table($current->getTable())->where('id', $current->id)->update(['position' => $target->position]);
            DB::table($target->getTable())->where('id', $target->id)->update(['position' => $current->position]);
        });
    }

    private function ownedNotebook(int $id, bool $trashed = false): Notebook
    {
        return Notebook::query()->when($trashed, fn (Builder $query) => $query->withTrashed())->where('user_id', auth()->id())->findOrFail($id);
    }

    private function ownedSection(int $id, bool $trashed = false): NotebookSection
    {
        return NotebookSection::query()->when($trashed, fn (Builder $query) => $query->withTrashed())->whereHas('notebook', fn (Builder $query) => $query->withTrashed()->where('user_id', auth()->id()))->findOrFail($id);
    }

    private function ownedPage(int $id, bool $trashed = false): NotePage
    {
        return NotePage::query()->when($trashed, fn (Builder $query) => $query->withTrashed())->whereHas('section.notebook', fn (Builder $query) => $query->withTrashed()->where('user_id', auth()->id()))->findOrFail($id);
    }
}
