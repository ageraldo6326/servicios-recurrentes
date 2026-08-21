<div x-data="{ copiedId: null }"
    x-on:ai-analysis-open.window="$wire.openChat()"
    x-on:keydown.escape.window="if ($wire.open) $wire.close()">
    <button type="button" x-on:click="$wire.openChat()"
        class="fixed bottom-5 right-5 z-30 inline-flex min-h-11 items-center gap-2 rounded-xl bg-ink px-4 py-3 text-sm font-bold text-white shadow-2xl transition hover:bg-brand active:scale-[.98]"
        aria-label="Abrir Asesor IA">
        <span aria-hidden="true">✦</span>
        <span>Asesor IA</span>
    </button>

    @if ($open)
        <div class="fixed inset-0 z-[60]" role="dialog" aria-modal="true" aria-label="Asesor IA interno">
            <div class="absolute inset-0 bg-ink/35 backdrop-blur-sm" wire:click="close"></div>
            <aside class="absolute inset-y-0 right-0 flex w-full max-w-4xl flex-col border-l border-line bg-surface shadow-2xl">
                <header class="flex shrink-0 items-start justify-between gap-4 border-b border-line px-5 py-4 sm:px-8">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-brand">Asesor IA</p>
                        <h2 class="mt-1 text-xl font-black tracking-tight text-ink">Analiza información pegada</h2>
                        <p class="mt-1 text-sm text-muted">Comparte solo la información necesaria para tu consulta.</p>
                    </div>
                    <button type="button" wire:click="close" class="button-secondary shrink-0 px-3" aria-label="Cerrar Asesor IA">✕</button>
                </header>

                <div wire:loading.flex wire:target="analyze" class="shrink-0 items-center gap-3 border-b border-brand/20 bg-brand/10 px-5 py-3 text-sm font-bold text-brand sm:px-8" aria-live="polite">
                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-brand/25 border-t-brand" aria-hidden="true"></span>
                    El Asesor IA está analizando la información…
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-5 sm:px-8 sm:py-6">
                    <form wire:submit="analyze" class="surface p-4 sm:p-5">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="ai-analysis-type" class="text-sm font-bold text-ink">Tipo de análisis</label>
                                <select id="ai-analysis-type" wire:model="analysisType" class="input">
                                    @foreach ($analysisTypes as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                    @endforeach
                                </select>
                                @error('analysisType') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex items-end">
                                <button type="button" wire:click="newConversation" class="button-secondary w-full">Limpiar conversación</button>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="ai-analysis-content" class="text-sm font-bold text-ink">Información a analizar</label>
                            <textarea id="ai-analysis-content" wire:model="content" rows="7" class="input resize-y"
                                placeholder="Pega aquí texto, tablas, mensajes o reportes del sistema."></textarea>
                            <p class="mt-1 text-xs text-muted">No pegues contraseñas, claves API, tokens ni datos bancarios completos. Los patrones sensibles conocidos se ocultan antes del envío.</p>
                            @error('content') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="mt-4">
                            <label for="ai-analysis-question" class="text-sm font-bold text-ink">Pregunta adicional <span class="font-normal text-muted">(opcional)</span></label>
                            <textarea id="ai-analysis-question" wire:model="question" rows="3" class="input resize-y" placeholder="Ej.: ¿Qué cobros debo priorizar esta semana?"></textarea>
                            @error('question') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>

                        @if (! $privacyAccepted)
                            <label class="mt-4 flex gap-3 rounded-xl border border-brand/20 bg-brand/10 p-3 text-sm text-ink">
                                <input type="checkbox" wire:model="privacyAccepted" class="mt-0.5 rounded border-line text-brand focus:ring-brand">
                                <span>Entiendo que el contenido se enviará al proveedor de IA configurado para generar este análisis. No se guarda el texto ni las respuestas automáticamente.</span>
                            </label>
                        @endif
                        @error('privacyAccepted') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror

                        @if ($error)
                            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200" role="alert">{{ $error }}</div>
                        @endif

                        <div class="mt-4 flex items-center justify-end gap-3">
                            <button type="submit" class="button" wire:loading.attr="disabled" wire:target="analyze">
                                <span wire:loading.remove wire:target="analyze">Generar análisis</span>
                                <span wire:loading wire:target="analyze">Procesando…</span>
                            </button>
                        </div>
                    </form>

                    <div class="mt-6 space-y-4">
                        @forelse ($renderedMessages as $message)
                            <article class="surface overflow-hidden">
                                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-surface-soft px-4 py-3 sm:px-5">
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-[0.16em] text-brand">{{ $message['type'] }}</p>
                                        @if ($message['question'] !== '')
                                            <p class="mt-1 text-sm font-semibold text-ink">{{ $message['question'] }}</p>
                                        @endif
                                    </div>
                                    <button type="button" class="button-secondary min-h-9 px-3 py-1.5 text-xs"
                                        x-on:click="navigator.clipboard.writeText(@js($message['analysis'])); copiedId = '{{ $message['id'] }}'">Copiar</button>
                                </div>
                                <div class="business-coach-markdown px-4 py-5 sm:px-5">{!! $message['html'] !!}</div>
                                <p x-show="copiedId === '{{ $message['id'] }}'" x-transition class="px-4 pb-4 text-xs font-bold text-brand">Respuesta copiada.</p>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-line px-5 py-8 text-center text-sm text-muted">Tu análisis aparecerá aquí. El contenido pegado no se conserva al enviarlo.</div>
                        @endforelse
                    </div>
                </div>
            </aside>
        </div>
    @endif
</div>
