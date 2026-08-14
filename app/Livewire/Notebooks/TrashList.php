<?php

declare(strict_types=1);

namespace App\Livewire\Notebooks;

use App\Models\Notebook;
use App\Models\NotebookSection;
use App\Models\NotePage;
use App\Services\Notebooks\NotebookService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

final class TrashList extends Component
{
    public string $notice = '';

    public function restoreNotebook(int $id, NotebookService $service): void
    {
        $notebook = Notebook::onlyTrashed()->where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('restore', $notebook);
        $service->restoreNotebook($notebook);
        $this->notice = 'Cuaderno restaurado.';
    }

    public function restoreSection(int $id, NotebookService $service): void
    {
        $section = NotebookSection::onlyTrashed()->whereHas('notebook', fn (Builder $query) => $query->withTrashed()->where('user_id', auth()->id()))->findOrFail($id);
        $this->authorize('restore', $section);
        $service->restoreSection($section);
        $this->notice = 'Sección restaurada con sus páginas.';
    }

    public function restorePage(int $id, NotebookService $service): void
    {
        $page = NotePage::onlyTrashed()->whereHas('section.notebook', fn (Builder $query) => $query->withTrashed()->where('user_id', auth()->id()))->findOrFail($id);
        $this->authorize('restore', $page);
        $service->restorePage($page);
        $this->notice = 'Página restaurada.';
    }

    public function render(): View
    {
        $userId = (int) auth()->id();

        return view('livewire.notebooks.trash-list', [
            'notebooks' => Notebook::onlyTrashed()->where('user_id', $userId)->latest('deleted_at')->paginate(10, ['*'], 'notebookTrashPage'),
            'sections' => NotebookSection::onlyTrashed()->whereHas('notebook', fn (Builder $query) => $query->withTrashed()->where('user_id', $userId))->with(['notebook' => fn ($query) => $query->withTrashed()->select(['id', 'name'])])->latest('deleted_at')->paginate(10, ['*'], 'sectionTrashPage'),
            'pages' => NotePage::onlyTrashed()->whereHas('section.notebook', fn (Builder $query) => $query->withTrashed()->where('user_id', $userId))->with(['section' => fn ($query) => $query->withTrashed()->select(['id', 'notebook_id', 'name']), 'section.notebook' => fn ($query) => $query->withTrashed()->select(['id', 'name'])])->latest('deleted_at')->paginate(10, ['*'], 'pageTrashPage'),
        ]);
    }
}
