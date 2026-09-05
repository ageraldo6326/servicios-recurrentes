<x-app-layout>
    @php
        $toneClasses = match ($status['tone']) {
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200',
            'amber' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200',
            'red' => 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200',
            default => 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900/30 dark:text-slate-200',
        };
        $timezone = \App\Models\CompanySetting::configuredTimezone();
    @endphp

    <div x-data="databaseBackupPage()" class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-brand">Sistema</p>
                <h1 class="text-2xl font-black tracking-tight text-ink sm:text-4xl">Respaldo de base de datos</h1>
                <p class="mt-2 max-w-3xl text-sm text-muted">Genere un archivo SQL comprimido de la estructura y datos de la base de datos. Los adjuntos y archivos del cuaderno no se incluyen.</p>
            </div>
            <button type="button" class="button" @click="confirmOpen = true" :disabled="processing || {{ $isProcessing ? 'true' : 'false' }}">
                <span>⇩</span><span class="ml-2">Generar y descargar respaldo</span>
            </button>
        </div>

        @if ($status['label'] === 'Pendiente' || $status['label'] === 'Nunca realizado')
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100" role="status">
                @if ($status['last_run'])
                    El respaldo de la base de datos está pendiente. El último respaldo fue realizado hace {{ abs($status['days_until_due']) }} {{ abs($status['days_until_due']) === 1 ? 'día' : 'días' }}.
                @else
                    El respaldo de la base de datos aún no se ha realizado. Se recomienda crear uno ahora.
                @endif
            </div>
        @endif

        @if ($isProcessing)
            <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-900 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-100" role="status">
                <span class="mr-2 inline-block animate-spin">◌</span>Hay un respaldo en proceso. Espere a que termine antes de iniciar otro.
            </div>
        @endif

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="card">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-muted">Estado actual</p>
                <span class="mt-3 inline-flex rounded-full border px-3 py-1.5 text-sm font-black {{ $toneClasses }}">{{ $status['label'] }}</span>
            </article>
            <article class="card">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-muted">Último respaldo</p>
                <p class="mt-3 text-lg font-black text-ink">{{ $status['last_run']?->completed_at?->setTimezone($timezone)->format('d/m/Y H:i') ?? 'Nunca realizado' }}</p>
                <p class="mt-1 text-xs text-muted">{{ $status['last_run']?->user?->name ? 'Generado por '.$status['last_run']->user->name : 'Sin respaldo registrado' }}</p>
            </article>
            <article class="card">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-muted">Próximo recomendado</p>
                <p class="mt-3 text-lg font-black text-ink">{{ $status['next_recommended_at']?->setTimezone($timezone)->format('d/m/Y H:i') ?? 'Ahora' }}</p>
                <p class="mt-1 text-xs text-muted">
                    @if ($status['days_until_due'] === null)
                        Realice el primer respaldo.
                    @elseif ($status['days_until_due'] < 0)
                        {{ abs($status['days_until_due']) }} {{ abs($status['days_until_due']) === 1 ? 'día de atraso' : 'días de atraso' }}.
                    @else
                        {{ $status['days_until_due'] }} {{ $status['days_until_due'] === 1 ? 'día restante' : 'días restantes' }}.
                    @endif
                </p>
            </article>
            <article class="card">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-muted">Intervalo actual</p>
                <p class="mt-3 text-3xl font-black text-ink">{{ $status['interval_days'] }} <span class="text-base text-muted">días</span></p>
                <p class="mt-1 text-xs text-muted">El conteo comienza tras un respaldo completado.</p>
            </article>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(22rem,.55fr)]">
            <section class="surface overflow-hidden">
                <div class="border-b border-line px-5 py-4 sm:px-6">
                    <h2 class="section-title">Historial de ejecuciones</h2>
                    <p class="mt-1 text-sm text-muted">Se conserva el resultado de cada intento, no el contenido del respaldo.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="table min-w-[760px]">
                        <thead><tr><th>Inicio</th><th>Usuario</th><th>Estado</th><th>Archivo</th><th>Duración</th><th>Detalle</th></tr></thead>
                        <tbody>
                            @forelse ($runs as $run)
                                @php($runTone = match ($run->status) { \App\Enums\DatabaseBackupRunStatus::Completed => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200', \App\Enums\DatabaseBackupRunStatus::Failed => 'bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-200', default => 'bg-sky-100 text-sky-800 dark:bg-sky-950/50 dark:text-sky-200' })
                                <tr>
                                    <td class="whitespace-nowrap text-sm text-ink">{{ $run->started_at->setTimezone($timezone)->format('d/m/Y H:i') }}</td>
                                    <td>{{ $run->user->name }}</td>
                                    <td><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $runTone }}">{{ match ($run->status) { \App\Enums\DatabaseBackupRunStatus::Completed => 'Completado', \App\Enums\DatabaseBackupRunStatus::Failed => 'Fallido', default => 'Procesando' } }}</span></td>
                                    <td class="max-w-52 truncate font-mono text-xs text-ink">{{ $run->file_name ?? '—' }}@if($run->file_size_bytes) <span class="font-sans text-muted">({{ number_format($run->file_size_bytes / 1024, 1) }} KB)</span>@endif</td>
                                    <td>{{ $run->duration_seconds === null ? '—' : $run->duration_seconds.' s' }}</td>
                                    <td class="max-w-64 text-xs text-muted">{{ $run->error_message ?? 'Respaldo generado correctamente.' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-10 text-center text-sm text-muted">Aún no hay intentos de respaldo registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel h-fit">
                <h2 class="section-title">Configurar recordatorio</h2>
                <p class="mt-1 text-sm text-muted">Defina cada cuántos días se recomienda generar un nuevo respaldo.</p>
                <form method="POST" action="{{ route('database-backups.update') }}" class="mt-5">
                    @csrf
                    @method('PUT')
                    <label for="reminder_interval_days" class="text-sm font-bold text-ink">Intervalo en días</label>
                    <input id="reminder_interval_days" name="reminder_interval_days" type="number" min="1" max="365" required value="{{ old('reminder_interval_days', $setting->reminder_interval_days) }}" class="input">
                    @error('reminder_interval_days')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    <button class="button-secondary mt-4 w-full" type="submit">Guardar configuración</button>
                </form>
            </section>
        </div>

        <div x-cloak x-show="confirmOpen" x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-ink/50 p-4" @keydown.escape.window="!processing && (confirmOpen = false)">
            <div class="w-full max-w-lg rounded-2xl border border-line bg-surface p-5 shadow-2xl sm:p-6" role="dialog" aria-modal="true" aria-labelledby="backup-confirm-title">
                <h2 id="backup-confirm-title" class="text-xl font-black text-ink">Confirmar generación del respaldo</h2>
                <div class="mt-4 space-y-3 text-sm text-muted">
                    <p>Se respaldarán la estructura y los datos de la base de datos configurada.</p>
                    <p>Los adjuntos e imágenes del cuaderno no están incluidos.</p>
                    <p class="rounded-xl border border-amber-200 bg-amber-50 p-3 font-semibold text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100">El archivo contiene información sensible. Guárdelo en un lugar seguro; el proceso puede tardar según el tamaño de la base de datos.</p>
                </div>
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" class="button-secondary" @click="confirmOpen = false" :disabled="processing">Cancelar</button>
                    <button type="button" class="button" @click="generate" :disabled="processing"><span x-show="!processing">Generar respaldo</span><span x-cloak x-show="processing">Generando…</span></button>
                </div>
            </div>
        </div>

        <div x-cloak x-show="processing" class="fixed inset-0 z-[60] grid place-items-center bg-ink/60 p-4" role="status" aria-live="assertive">
            <div class="w-full max-w-md rounded-2xl border border-line bg-surface p-6 text-center shadow-2xl">
                <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-brand/15 text-3xl text-brand"><span class="animate-spin">◌</span></div>
                <h2 class="mt-4 text-xl font-black text-ink">Generando respaldo…</h2>
                <p class="mt-2 text-sm text-muted">Estamos exportando y validando el archivo comprimido. No cierre esta ventana hasta que inicie la descarga.</p>
            </div>
        </div>

        <div x-cloak x-show="message" x-transition class="fixed bottom-5 right-5 z-[70] max-w-sm rounded-xl border px-4 py-3 text-sm font-semibold shadow-lg" :class="messageError ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'" x-text="message" role="status"></div>
    </div>

    @push('scripts')
        <script>
            function databaseBackupPage() {
                return {
                    confirmOpen: false,
                    processing: false,
                    message: '',
                    messageError: false,
                    async generate() {
                        this.processing = true;
                        this.confirmOpen = false;
                        this.message = '';
                        try {
                            const response = await fetch(@js(route('database-backups.generate')), {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                            });
                            const body = await response.json();
                            if (!response.ok) {
                                const message = body.reference ? `${body.message} (Código: ${body.reference})` : body.message;
                                throw new Error(message || 'No fue posible generar el respaldo.');
                            }

                            this.processing = false;
                            this.messageError = false;
                            this.message = body.message;
                            window.location.assign(body.download_url);
                            window.setTimeout(() => window.location.reload(), 800);
                        } catch (error) {
                            this.processing = false;
                            this.messageError = true;
                            this.message = error.message || 'No fue posible generar el respaldo.';
                        }
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
