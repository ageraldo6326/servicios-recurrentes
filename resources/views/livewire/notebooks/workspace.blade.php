@php
    $rootPages = $pages->whereNull('parent_id');
    $childrenByParent = $pages->whereNotNull('parent_id')->groupBy('parent_id');
    $pageTitle = fn ($page) => trim((string) $page->title) !== '' ? $page->title : 'Página sin título';
    $activeNotebook = $notebooks->firstWhere('id', (int) $notebookId);
    $activeSection = $sections->firstWhere('id', (int) $sectionId);
@endphp

<div class="space-y-5" x-data>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-xs font-black uppercase tracking-[0.2em] text-brand">Productividad personal</p>
            <h1 class="text-3xl font-black tracking-tight text-ink sm:text-4xl">Cuadernos</h1>
            <p class="mt-2 max-w-2xl text-sm text-muted">Captura, organiza y encuentra tus notas sin salir de tu contexto.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
            <input wire:model.live.debounce.500ms="query" class="input mt-0 min-w-0 sm:w-72" type="search" placeholder="Buscar en tus notas" aria-label="Buscar en tus notas">
            <button type="button" class="button" x-on:click="$dispatch('notebook-create-open')">＋ Nuevo cuaderno</button>
        </div>
    </div>

    @if ($notice)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">{{ $notice }}</div>
    @endif

    <div class="flex gap-2 overflow-x-auto pb-1" aria-label="Vistas de cuadernos">
        @foreach (['browse' => 'Cuadernos', 'recent' => 'Recientes', 'favorites' => 'Favoritas', 'trash' => 'Papelera'] as $value => $label)
            <button type="button" wire:click="showView('{{ $value }}')" class="shrink-0 rounded-xl border px-3 py-2 text-sm font-bold transition {{ $view === $value ? 'border-brand bg-brand text-white' : 'border-line bg-surface text-muted hover:border-brand hover:text-brand' }}">{{ $label }}</button>
        @endforeach
    </div>

    @if ($view === 'search')
        <section class="surface overflow-hidden">
            <div class="border-b border-line px-5 py-4"><h2 class="section-title">Resultados para “{{ $query }}”</h2></div>
            <div class="divide-y divide-line">
                @forelse ($results as $page)
                    <button type="button" wire:click="selectPage({{ $page->id }})" class="block w-full px-5 py-4 text-left transition hover:bg-surface-soft">
                        <p class="font-bold text-ink">{{ $pageTitle($page) }}</p>
                        <p class="mt-1 text-xs font-semibold text-brand">{{ $page->section->notebook->name }} · {{ $page->section->name }}</p>
                        <p class="mt-2 line-clamp-2 text-sm text-muted">{{ \Illuminate\Support\Str::limit($page->searchable_text, 180) }}</p>
                    </button>
                @empty
                    <p class="px-5 py-12 text-center text-sm text-muted">No encontramos notas con ese texto. Prueba con otra palabra.</p>
                @endforelse
            </div>
            @if ($results->hasPages())<div class="border-t border-line px-5 py-3">{{ $results->links() }}</div>@endif
        </section>
    @elseif ($view === 'recent' || $view === 'favorites')
        @php($listing = $view === 'recent' ? $recentPages : $favoritePages)
        <section class="surface overflow-hidden">
            <div class="border-b border-line px-5 py-4"><h2 class="section-title">{{ $view === 'recent' ? 'Páginas recientes' : 'Páginas favoritas' }}</h2></div>
            <div class="divide-y divide-line">
                @forelse ($listing as $page)
                    <button type="button" wire:click="selectPage({{ $page->id }})" class="flex w-full items-start justify-between gap-4 px-5 py-4 text-left transition hover:bg-surface-soft"><span><span class="block font-bold text-ink">{{ $pageTitle($page) }}</span><span class="mt-1 block text-xs font-semibold text-brand">{{ $page->section->notebook->name }} · {{ $page->section->name }}</span><span class="mt-1 block text-xs text-muted">{{ $page->last_edited_at?->timezone(\App\Models\CompanySetting::configuredTimezone())->translatedFormat('d M, H:i') ?? 'Sin edición' }}</span></span>@if($page->is_favorite)<span aria-label="Favorita" class="text-brand">★</span>@endif</button>
                @empty
                    <p class="px-5 py-12 text-center text-sm text-muted">{{ $view === 'recent' ? 'Aún no has modificado páginas.' : 'Todavía no tienes páginas favoritas.' }}</p>
                @endforelse
            </div>
            @if ($listing->hasPages())<div class="border-t border-line px-5 py-3">{{ $listing->links() }}</div>@endif
        </section>
    @elseif ($view === 'trash')
        <livewire:notebooks.trash-list />
    @else
        <div class="grid gap-4 xl:grid-cols-[minmax(190px,.7fr)_minmax(190px,.7fr)_minmax(220px,.9fr)_minmax(0,2.2fr)] xl:gap-0 xl:overflow-hidden xl:rounded-2xl xl:border xl:border-line xl:bg-surface xl:shadow-card">
            <section class="surface overflow-hidden xl:rounded-none xl:border-0 xl:border-r xl:shadow-none">
                <div class="flex items-center justify-between border-b border-line px-4 py-4"><h2 class="text-sm font-black uppercase tracking-[.14em] text-muted">Cuadernos</h2><button type="button" class="text-lg font-black text-brand" x-on:click="$dispatch('notebook-create-open')" aria-label="Crear cuaderno">＋</button></div>
                <div class="max-h-[28rem] overflow-y-auto p-2 xl:max-h-[calc(100vh-17rem)]">
                    @forelse ($notebooks as $notebook)
                        <div wire:key="notebook-{{ $notebook->id }}" class="group flex items-center gap-1 rounded-xl {{ (int) $notebookId === $notebook->id ? 'bg-brand/10' : 'hover:bg-surface-soft' }}">
                            <button type="button" wire:click="selectNotebook({{ $notebook->id }})" class="min-w-0 flex-1 px-3 py-3 text-left"><span class="block truncate text-sm font-bold text-ink">{{ $notebook->name }}</span>@if($notebook->archived_at)<span class="text-[10px] font-bold uppercase text-muted">Archivado</span>@endif</button>
                            <div class="hidden shrink-0 items-center gap-0.5 pr-1 group-hover:flex focus-within:flex"><button wire:click="moveNotebook({{ $notebook->id }}, -1)" class="rounded p-1 text-muted hover:text-brand" aria-label="Subir cuaderno">↑</button><button wire:click="moveNotebook({{ $notebook->id }}, 1)" class="rounded p-1 text-muted hover:text-brand" aria-label="Bajar cuaderno">↓</button><button wire:click="renameNotebook({{ $notebook->id }})" class="rounded p-1 text-muted hover:text-brand" aria-label="Cambiar nombre">✎</button></div>
                        </div>
                    @empty
                        <div class="px-4 py-10 text-center"><p class="text-sm text-muted">Aún no tienes cuadernos.</p><button type="button" class="mt-3 text-sm font-bold text-brand hover:underline" x-on:click="$dispatch('notebook-create-open')">Crear el primero</button></div>
                    @endforelse
                </div>
            </section>

            <section class="surface overflow-hidden xl:rounded-none xl:border-0 xl:border-r xl:shadow-none">
                <div class="flex items-center justify-between gap-2 border-b border-line px-4 py-4"><h2 class="text-sm font-black uppercase tracking-[.14em] text-muted">Secciones</h2>@if($activeNotebook)<div class="flex items-center gap-1"><button type="button" wire:click="editNotebook({{ $activeNotebook->id }})" class="rounded p-1 text-muted hover:text-brand" aria-label="Editar cuaderno">✎</button><button type="button" wire:click="toggleNotebookArchive({{ $activeNotebook->id }})" class="rounded p-1 text-muted hover:text-brand" aria-label="{{ $activeNotebook->archived_at ? 'Desarchivar' : 'Archivar' }} cuaderno">{{ $activeNotebook->archived_at ? '↺' : '⌑' }}</button><button type="button" wire:click="deleteNotebook({{ $activeNotebook->id }})" class="rounded p-1 text-muted hover:text-red-600" aria-label="Enviar cuaderno a papelera">×</button><button type="button" class="text-lg font-black text-brand" x-on:click="$dispatch('notebook-section-open')" aria-label="Crear sección">＋</button></div>@endif</div>
                <div class="max-h-[28rem] overflow-y-auto p-2 xl:max-h-[calc(100vh-17rem)]">
                    @if ($notebookId === '')<p class="px-4 py-10 text-center text-sm text-muted">Elige un cuaderno.</p>@endif
                    @forelse ($sections as $section)
                        <div wire:key="section-{{ $section->id }}" class="group flex items-center gap-1 rounded-xl {{ (int) $sectionId === $section->id ? 'bg-brand/10' : 'hover:bg-surface-soft' }}"><button type="button" wire:click="selectSection({{ $section->id }})" class="min-w-0 flex-1 px-3 py-3 text-left"><span class="block truncate text-sm font-bold text-ink">{{ $section->name }}</span>@if($section->archived_at)<span class="text-[10px] font-bold uppercase text-muted">Archivada</span>@endif</button><div class="hidden shrink-0 items-center gap-0.5 pr-1 group-hover:flex focus-within:flex"><button wire:click="moveSection({{ $section->id }}, -1)" class="rounded p-1 text-muted hover:text-brand" aria-label="Subir sección">↑</button><button wire:click="moveSection({{ $section->id }}, 1)" class="rounded p-1 text-muted hover:text-brand" aria-label="Bajar sección">↓</button><button wire:click="renameSection({{ $section->id }})" class="rounded p-1 text-muted hover:text-brand" aria-label="Cambiar nombre">✎</button></div></div>
                    @empty
                        @if($notebookId !== '')<div class="px-4 py-10 text-center"><p class="text-sm text-muted">Este cuaderno aún no tiene secciones.</p><button type="button" class="mt-3 text-sm font-bold text-brand hover:underline" x-on:click="$dispatch('notebook-section-open')">Crear sección</button></div>@endif
                    @endforelse
                </div>
            </section>

            <section class="surface overflow-hidden xl:rounded-none xl:border-0 xl:border-r xl:shadow-none">
                <div class="flex items-center justify-between gap-2 border-b border-line px-4 py-4"><h2 class="text-sm font-black uppercase tracking-[.14em] text-muted">Páginas</h2>@if($activeSection)<div class="flex items-center gap-1"><button type="button" wire:click="toggleSectionArchive({{ $activeSection->id }})" class="rounded p-1 text-muted hover:text-brand" aria-label="{{ $activeSection->archived_at ? 'Desarchivar' : 'Archivar' }} sección">{{ $activeSection->archived_at ? '↺' : '⌑' }}</button><button type="button" wire:click="deleteSection({{ $activeSection->id }})" class="rounded p-1 text-muted hover:text-red-600" aria-label="Enviar sección a papelera">×</button><button type="button" wire:click="createPage" class="text-lg font-black text-brand" aria-label="Crear página">＋</button></div>@endif</div>
                <div class="max-h-[28rem] overflow-y-auto p-2 xl:max-h-[calc(100vh-17rem)]">
                    @if($sectionId === '')<p class="px-4 py-10 text-center text-sm text-muted">Elige una sección.</p>@endif
                    @foreach ($rootPages as $page)
                        <div wire:key="page-{{ $page->id }}"><div class="group flex items-center gap-1 rounded-xl {{ (int) $pageId === $page->id ? 'bg-brand/10' : 'hover:bg-surface-soft' }}"><button type="button" wire:click="selectPage({{ $page->id }})" class="min-w-0 flex-1 px-3 py-3 text-left"><span class="block truncate text-sm font-bold text-ink">{{ $pageTitle($page) }}</span>@if($page->is_favorite)<span class="text-xs text-brand">★ Favorita</span>@endif</button><div class="hidden shrink-0 items-center gap-0.5 pr-1 group-hover:flex focus-within:flex"><button wire:click="movePageOrder({{ $page->id }}, -1)" class="rounded p-1 text-muted hover:text-brand" aria-label="Subir página">↑</button><button wire:click="movePageOrder({{ $page->id }}, 1)" class="rounded p-1 text-muted hover:text-brand" aria-label="Bajar página">↓</button><button wire:click="createSubpage({{ $page->id }})" class="rounded p-1 text-muted hover:text-brand" aria-label="Crear subpágina">↳</button></div></div>
                            @foreach ($childrenByParent->get($page->id, collect()) as $child)
                                <button type="button" wire:click="selectPage({{ $child->id }})" class="ml-4 flex w-[calc(100%-1rem)] items-center gap-2 rounded-xl px-3 py-2.5 text-left text-sm {{ (int) $pageId === $child->id ? 'bg-brand/10 font-bold text-ink' : 'text-muted hover:bg-surface-soft hover:text-ink' }}"><span aria-hidden="true">↳</span><span class="truncate">{{ $pageTitle($child) }}</span></button>
                            @endforeach
                        </div>
                    @endforeach
                    @if($sectionId !== '' && $pages->isEmpty())<div class="px-4 py-10 text-center"><p class="text-sm text-muted">No hay páginas en esta sección.</p><button type="button" wire:click="createPage" class="mt-3 text-sm font-bold text-brand hover:underline">Crear página</button></div>@endif
                </div>
            </section>

            <section class="surface min-h-[34rem] overflow-visible xl:rounded-none xl:border-0 xl:shadow-none">
                @if ($selectedPage)
                    <div wire:key="editor-{{ $selectedPage->id }}-{{ $editorRefresh }}" x-data="notebookEditor({{ $selectedPage->id }}, {{ $selectedPage->content_version }}, @js($selectedPage->title ?? ''), @js($selectedPage->html()))" x-init="init()" x-on:notebook-page-saved.window="saved($event.detail)" x-on:notebook-conflict.window="conflict($event.detail)" x-on:notebook-save-error.window="error($event.detail)" x-on:notebook-editor-focus.window="focus($event.detail.target)" x-on:notebook-image-inserted.window="insertImage($event.detail)" class="flex min-h-[34rem] flex-col">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-4 py-3 sm:px-5"><div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full" :class="status === 'error' || status === 'conflict' ? 'bg-red-500' : (status === 'saving' ? 'animate-pulse bg-amber-500' : 'bg-emerald-500')"></span><span class="text-xs font-bold text-muted" aria-live="polite" x-text="statusText"></span></div><div class="flex gap-2"><button type="button" x-on:click="save()" class="button-secondary min-h-9 px-3 text-xs">Guardar ahora</button><button type="button" wire:click="toggleFavorite({{ $selectedPage->id }})" class="button-secondary min-h-9 px-3 text-xs" aria-label="{{ $selectedPage->is_favorite ? 'Quitar de favoritas' : 'Agregar a favoritas' }}">{{ $selectedPage->is_favorite ? '★ Favorita' : '☆ Favorita' }}</button><button type="button" wire:click="deletePage({{ $selectedPage->id }})" class="button-secondary min-h-9 px-3 text-xs text-red-600">Papelera</button></div></div>
                        <div class="notebook-editor-toolbar" aria-label="Herramientas del editor"><button type="button" class="editor-tool" x-on:click="command('bold')" aria-label="Negrita"><strong>B</strong></button><button type="button" class="editor-tool italic" x-on:click="command('italic')" aria-label="Cursiva">I</button><button type="button" class="editor-tool underline" x-on:click="command('underline')" aria-label="Subrayado">U</button><button type="button" class="editor-tool line-through" x-on:click="command('strikeThrough')" aria-label="Tachado">S</button><span class="mx-1 border-l border-line"></span><button type="button" class="editor-tool" x-on:click="command('formatBlock', 'H1')">H1</button><button type="button" class="editor-tool" x-on:click="command('formatBlock', 'H2')">H2</button><button type="button" class="editor-tool" x-on:click="command('insertUnorderedList')" aria-label="Lista">• Lista</button><button type="button" class="editor-tool" x-on:click="command('insertOrderedList')" aria-label="Lista numerada">1. Lista</button><button type="button" class="editor-tool" x-on:click="checklist()">☐</button><button type="button" class="editor-tool" x-on:click="command('formatBlock', 'blockquote')" aria-label="Cita">❝</button><button type="button" class="editor-tool" x-on:click="link()">Enlace</button><button type="button" class="editor-tool" x-on:click="code()">&lt;/&gt;</button><button type="button" class="editor-tool" x-on:click="imageAlt()">Alt imagen</button><button type="button" class="editor-tool" x-on:click="command('insertHorizontalRule')" aria-label="Separador">—</button></div>
                        <div wire:ignore class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-8"><input x-ref="title" x-on:input="schedule()" class="w-full border-0 bg-transparent p-0 text-2xl font-black tracking-tight text-ink placeholder:text-muted focus:ring-0 sm:text-3xl" placeholder="Página sin título" aria-label="Título de la página"><div x-ref="body" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Contenido de la página" x-on:input="schedule()" x-on:paste="paste($event)" x-on:click="checkboxChanged($event)" class="notebook-editor mt-5 min-h-[20rem] outline-none"></div><p x-show="status === 'conflict'" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">Esta página cambió en otra pestaña. Tu contenido local sigue aquí: cópialo o <button type="button" class="font-bold underline" x-on:click="$wire.reloadEditor()">recarga la versión actual</button>.</p><p x-show="status === 'error'" class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200"><span x-text="message"></span> <button type="button" class="font-bold underline" x-on:click="save()">Reintentar</button></p></div>
                            <div class="border-t border-line px-5 py-4 sm:px-8"><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><form class="flex min-w-0 items-center gap-2"><label class="button-secondary min-h-9 cursor-pointer px-3 text-xs">Adjuntar archivo<input class="sr-only" type="file" wire:model="attachmentUpload"></label><span wire:loading wire:target="attachmentUpload" class="text-xs font-semibold text-muted">Cargando…</span>@error('attachmentUpload')<span class="text-xs font-semibold text-red-600">{{ $message }}</span>@enderror</form><select wire:model.live="moveTargetSectionId" class="input mt-0 min-h-9 max-w-xs py-1.5 text-xs" aria-label="Mover página a otra sección"><option value="">Mover a sección…</option>@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->name }}</option>@endforeach</select></div>@if($moveTargetSectionId)<button type="button" wire:click="movePage({{ $selectedPage->id }}, {{ $moveTargetSectionId }})" class="mt-2 text-xs font-bold text-brand hover:underline">Confirmar movimiento</button>@endif
                            @if($selectedPage->attachments->isNotEmpty())<div class="mt-4 flex flex-wrap gap-2">@foreach($selectedPage->attachments as $attachment)<span class="inline-flex max-w-full items-center gap-2 rounded-lg border border-line bg-surface-soft px-2.5 py-1.5 text-xs text-ink"><a href="{{ route('notebooks.attachments.show', $attachment) }}" target="_blank" class="max-w-40 truncate font-bold hover:text-brand hover:underline">{{ $attachment->original_name }}</a><button type="button" wire:click="deleteAttachment({{ $attachment->id }})" class="text-red-600" aria-label="Retirar {{ $attachment->original_name }}">×</button></span>@endforeach</div>@endif
                            @if($selectedPage->versions->isNotEmpty())<details class="mt-4"><summary class="cursor-pointer text-xs font-bold text-muted">Historial de versiones</summary><div class="mt-2 space-y-2">@foreach($selectedPage->versions as $version)<div class="flex items-center justify-between gap-3 rounded-lg bg-surface-soft px-3 py-2 text-xs"><span>Versión {{ $version->content_version }} · {{ $version->created_at->timezone(\App\Models\CompanySetting::configuredTimezone())->translatedFormat('d M, H:i') }}@if($version->user) · {{ $version->user->name }}@endif</span><button type="button" wire:click="restoreVersion({{ $version->id }})" class="font-bold text-brand hover:underline">Restaurar</button></div>@endforeach</div></details>@endif
                        </div>
                    </div>
                @else
                    <div class="grid min-h-[34rem] place-items-center px-6 text-center"><div><p class="text-4xl">✎</p><h2 class="mt-3 text-xl font-black text-ink">{{ $sectionId === '' ? 'Elige una sección' : 'Abre o crea una página' }}</h2><p class="mt-2 max-w-sm text-sm text-muted">{{ $sectionId === '' ? 'Navega por los cuadernos para encontrar tu contexto.' : 'La información se guarda automáticamente mientras escribes.' }}</p>@if($sectionId !== '')<button type="button" wire:click="createPage" class="button mt-5">＋ Nueva página</button>@endif</div></div>
                @endif
            </section>
        </div>
    @endif

    <div x-data="{ open: false }" x-on:notebook-create-open.window="open = true" x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Crear cuaderno"><div class="absolute inset-0 bg-ink/40" x-on:click="open = false"></div><form wire:submit="createNotebook" class="surface relative z-10 w-full max-w-lg p-5"><div class="flex items-center justify-between"><h2 class="section-title">Nuevo cuaderno</h2><button type="button" class="text-xl text-muted" x-on:click="open = false" aria-label="Cerrar">×</button></div><label class="mt-4 block text-sm font-bold text-ink">Nombre<input wire:model="notebookName" class="input" autofocus></label>@error('notebookName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror<label class="mt-4 block text-sm font-bold text-ink">Descripción <span class="font-normal text-muted">(opcional)</span><textarea wire:model="notebookDescription" class="input" rows="3"></textarea></label><div class="mt-5 flex justify-end gap-2"><button type="button" class="button-secondary" x-on:click="open = false">Cancelar</button><button class="button">Crear cuaderno</button></div></form></div>
    <div x-data="{ open: false }" x-on:notebook-section-open.window="open = true" x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Crear sección"><div class="absolute inset-0 bg-ink/40" x-on:click="open = false"></div><form wire:submit="createSection" class="surface relative z-10 w-full max-w-lg p-5"><div class="flex items-center justify-between"><h2 class="section-title">Nueva sección</h2><button type="button" class="text-xl text-muted" x-on:click="open = false" aria-label="Cerrar">×</button></div><label class="mt-4 block text-sm font-bold text-ink">Nombre<input wire:model="sectionName" class="input" autofocus></label>@error('sectionName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror<div class="mt-5 flex justify-end gap-2"><button type="button" class="button-secondary" x-on:click="open = false">Cancelar</button><button class="button">Crear sección</button></div></form></div>
    <div x-data="{ open: @entangle('editingNotebookId').live }" x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Editar cuaderno"><div class="absolute inset-0 bg-ink/40"></div><form wire:submit="saveNotebookDetails" class="surface relative z-10 w-full max-w-lg p-5"><h2 class="section-title">Editar cuaderno</h2><label class="mt-4 block text-sm font-bold text-ink">Nombre<input wire:model="notebookName" class="input" autofocus></label><label class="mt-4 block text-sm font-bold text-ink">Descripción <span class="font-normal text-muted">(opcional)</span><textarea wire:model="notebookDescription" class="input" rows="3"></textarea></label><div class="mt-5 flex justify-end gap-2"><button type="button" wire:click="cancelNotebookEdit" class="button-secondary">Cancelar</button><button class="button">Guardar</button></div></form></div>
    <div x-data="{ open: @entangle('renamingNotebookId').live || @entangle('renamingSectionId').live }" x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center p-4" role="dialog" aria-modal="true" aria-label="Cambiar nombre"><div class="absolute inset-0 bg-ink/40"></div><form wire:submit="saveRename" class="surface relative z-10 w-full max-w-lg p-5"><h2 class="section-title">Cambiar nombre</h2><label class="mt-4 block text-sm font-bold text-ink">Nombre<input wire:model="renameValue" class="input" autofocus></label>@error('renameValue')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror<div class="mt-5 flex justify-end gap-2"><button type="button" wire:click="cancelRename" class="button-secondary">Cancelar</button><button class="button">Guardar</button></div></form></div>
</div>
