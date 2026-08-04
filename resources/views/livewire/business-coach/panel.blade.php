<div x-data="{
    copied: false,
    requesting: false,
    progress: 0,
    progressTimer: null,
    startAnalysis(force = false) {
        this.requesting = true;
        this.progress = 8;
        clearInterval(this.progressTimer);
        this.progressTimer = setInterval(() => {
            if (this.progress < 92) this.progress += 3;
        }, 500);
        Promise.resolve(force ? this.$wire.forceAnalyze() : this.$wire.analyze()).finally(() => {
            clearInterval(this.progressTimer);
            this.progress = 100;
            setTimeout(() => { this.requesting = false; this.progress = 0; }, 350);
        });
    }
}"
    x-on:business-coach-analyze.window="$event.detail?.force === true ? startAnalysis(true) : startAnalysis()"
    x-init="document.addEventListener('livewire:navigated', () => { copied = false; $wire.resetForNavigation(window.location.pathname) })">
    <div x-show="requesting" x-transition.opacity
        class="fixed inset-x-0 top-0 z-[70] border-b border-brand/20 bg-surface/95 shadow-card backdrop-blur"
        aria-live="polite" aria-label="Procesando análisis">
        <div class="h-1 w-full bg-brand/10">
            <div class="h-1 bg-brand transition-all duration-500 ease-out" :style="`width: ${progress}%`"></div>
        </div>
        <div class="flex items-center justify-center gap-3 px-5 py-3 text-sm font-bold text-brand">
            <span class="h-4 w-4 animate-spin rounded-full border-2 border-brand/25 border-t-brand"
                aria-hidden="true"></span>
            <span>Analizando la pantalla…</span>
        </div>
    </div>

    @if ($open)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Business Coach">
            <div class="absolute inset-0 bg-ink/30" wire:click="close"></div>
            <aside class="absolute inset-y-0 right-0 flex min-h-0 w-full max-w-4xl flex-col border-l border-line bg-surface shadow-2xl">
                <div class="flex shrink-0 items-center justify-between border-b border-line px-5 py-4">
                    <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-brand">Business Coach</p><h2 class="mt-1 text-xl font-black text-ink">Análisis operativo</h2></div>
                    <button type="button" wire:click="close" class="button-secondary px-3" aria-label="Cerrar">✕</button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-6 sm:px-8">
                    @if ($error)<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">{{ $error }}</div>@endif
                    @if ($analysis)<div class="business-coach-markdown text-ink">{!! $renderedAnalysis !!}</div>@endif
                    @if (!$loading && !$analysis && !$error)<p class="muted">Presiona “Analizar” para generar recomendaciones.</p>@endif
                </div>
                @if ($analysis)
                    <div class="flex shrink-0 flex-wrap gap-2 border-t border-line px-5 py-4 sm:px-8">
                        <button type="button" class="button-secondary" x-on:click="navigator.clipboard.writeText(@js($analysis)); copied = true">Copiar</button>
                        <button type="button" wire:click="saveNote" class="button">Guardar como nota</button>
                        <button type="button" x-on:click="$dispatch('business-coach-analyze', { force: true })" class="button-secondary">Actualizar análisis</button>
                        @if ($saved)<span class="self-center text-sm font-semibold text-brand">Nota guardada.</span>@endif
                        <span x-show="copied" x-transition class="self-center text-sm font-semibold text-brand">Copiado.</span>
                    </div>
                @endif
            </aside>
        </div>
    @endif
</div>
