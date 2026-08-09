<div wire:poll.5s="tick"
    x-data="{ target: @js($targetAt), remaining: @js($remainingSeconds), timer: null }"
    x-init="timer = setInterval(() => { if (target) remaining = Math.max(0, Math.ceil((new Date(target).getTime() - Date.now()) / 1000)); }, 1000); $watch('$wire.targetAt', value => target = value)"
    x-on:break-cycle-alert.window="const token = event.detail.token; const key = 'break-alert:' + token; if (token && localStorage.getItem(key) !== '1') { localStorage.setItem(key, '1'); if (!event.detail.kind.endsWith('visual')) window.playBreakAlarm?.(); }"
    class="contents">
    <div class="flex min-w-0 items-center gap-2">
        @if ($status === 'working' && $enabled)
            <div class="inline-flex h-10 max-w-[220px] items-center gap-2 rounded-xl border border-line bg-surface px-3 text-sm font-bold text-ink shadow-sm">
                <span class="text-brand">◷</span>
                <span class="truncate">Trabajo <span class="tabular-nums text-brand" x-text="Math.floor(remaining / 60).toString().padStart(2, '0') + ':' + (remaining % 60).toString().padStart(2, '0')">{{ gmdate('i:s', $remainingSeconds) }}</span></span>
            </div>
            <button type="button" wire:click="pauseWork" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-xl border border-line bg-surface px-3 text-xs font-bold text-muted transition hover:border-amber-400 hover:text-amber-700 sm:text-sm" aria-label="Pausar contador" title="Pausar contador"><span aria-hidden="true">⏸</span><span class="hidden sm:inline">Pausar</span></button>
        @elseif ($status === 'paused' && $enabled)
            <button type="button" wire:click="resumeWork" class="button h-10 whitespace-nowrap px-3 text-xs sm:px-4 sm:text-sm">Reanudar trabajo</button>
        @elseif ($status === 'break_pending' && $enabled)
            <div class="inline-flex h-10 items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 text-sm font-bold text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100"><span>🔔</span><span class="hidden sm:inline">Pausa pendiente</span><span class="sm:hidden">Pausa</span></div>
        @elseif ($status === 'break_active' && $enabled)
            <div class="inline-flex h-10 items-center gap-2 rounded-xl border border-brand/30 bg-brand/5 px-3 text-sm font-bold text-brand"><span>🧘</span><span class="hidden sm:inline">Pausa</span><span class="tabular-nums" x-text="Math.floor(remaining / 60).toString().padStart(2, '0') + ':' + (remaining % 60).toString().padStart(2, '0')">{{ gmdate('i:s', $remainingSeconds) }}</span></div>
        @elseif (in_array($status, ['break_completed', 'break_cancelled', 'work_pending'], true) && $enabled)
            <button type="button" x-on:click="window.enableBreakAudio?.()" wire:click="startWork" class="button h-10 whitespace-nowrap px-3 text-xs sm:px-4 sm:text-sm">Comenzar a trabajar</button>
        @elseif ($enabled)
            <button type="button" x-on:click="window.enableBreakAudio?.()" wire:click="startWork" class="button h-10 whitespace-nowrap px-3 text-xs sm:px-4 sm:text-sm">Comenzar a trabajar</button>
        @endif

        @if ($enabled && ($soundOnBreak || $soundOnReturn))
            <button type="button" x-on:click="window.enableBreakAudio?.(); $el.setAttribute('aria-label', 'Sonido activado'); $el.textContent = '🔊'" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-line bg-surface text-sm text-muted transition hover:border-brand hover:text-brand" aria-label="Activar sonido">🔈</button>
        @endif
    </div>

    @if ($status === 'break_pending' && $enabled)
        <div class="pointer-events-none fixed inset-x-3 bottom-5 z-50 flex justify-center sm:inset-x-auto sm:right-5 sm:justify-end">
            <section class="pointer-events-auto w-full max-w-md rounded-2xl border border-amber-200 bg-surface p-5 shadow-2xl dark:border-amber-800" role="alert" aria-live="assertive">
                <div class="flex items-start gap-3"><div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-amber-100 text-xl dark:bg-amber-950/60">🔔</div><div class="min-w-0"><p class="text-xs font-black uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Hora de descansar</p><h2 class="mt-1 text-xl font-black text-ink">Toma una pausa activa</h2><p class="mt-2 text-sm text-muted">Has trabajado durante {{ $workMinutes }} minutos. Tu pausa durará {{ $breakMinutes }} minutos.</p></div></div>
                @if ($exerciseName)<div class="mt-4 rounded-xl bg-surface-soft p-3"><p class="text-xs font-bold uppercase tracking-wider text-brand">Ejercicio sugerido</p><p class="mt-1 font-black text-ink">{{ $exerciseName }}</p><p class="mt-1 text-sm text-muted">{{ $exerciseDescription }}</p></div>@endif
                <div class="mt-4 flex flex-col gap-2 sm:flex-row"><button type="button" x-on:click="window.enableBreakAudio?.()" wire:click="takeBreak" class="button w-full sm:flex-1">Tomar pausa</button><button type="button" wire:click="cancelBreak" class="button-secondary w-full sm:flex-1">Omitir</button></div>
            </section>
        </div>
    @elseif ($status === 'break_active' && $enabled)
        <div class="pointer-events-none fixed inset-x-3 bottom-5 z-50 flex justify-center sm:inset-x-auto sm:right-5 sm:justify-end">
            <section class="pointer-events-auto w-full max-w-md rounded-2xl border border-brand/25 bg-surface p-5 shadow-2xl" role="status" aria-live="polite">
                <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-[0.18em] text-brand">Pausa activa</p><h2 class="mt-1 text-xl font-black text-ink">{{ $exerciseName ?: 'Movimiento libre' }}</h2></div><span class="text-2xl">🧘</span></div>
                @if ($exerciseInstructions)<p class="mt-3 text-sm text-muted">{{ $exerciseInstructions }}</p>@endif
                <div class="mt-4 text-center"><p class="text-5xl font-black tabular-nums text-brand" x-text="Math.floor(remaining / 60).toString().padStart(2, '0') + ':' + (remaining % 60).toString().padStart(2, '0')">{{ gmdate('i:s', $remainingSeconds) }}</p><div class="mt-3 h-2 overflow-hidden rounded-full bg-surface-soft"><div class="h-full rounded-full bg-brand transition-all" :style="{ width: (Math.max(0, Math.min(100, 100 - (remaining / {{ max(1, $breakMinutes * 60) }}) * 100)) + '%') }"></div></div></div>
                <p class="mt-3 text-center text-xs text-muted">El temporizador usa la hora registrada y continúa aunque cambies de módulo.</p>
            </section>
        </div>
    @elseif (in_array($status, ['break_completed', 'break_cancelled', 'work_pending'], true) && $enabled)
        <div class="pointer-events-none fixed inset-x-3 bottom-5 z-50 flex justify-center sm:inset-x-auto sm:right-5 sm:justify-end">
            <section class="pointer-events-auto w-full max-w-md rounded-2xl border border-sky-200 bg-surface p-5 shadow-2xl dark:border-sky-800" role="alert" aria-live="assertive"><div class="flex items-start gap-3"><div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-sky-100 text-xl dark:bg-sky-950/60">🔔</div><div><p class="text-xs font-black uppercase tracking-[0.18em] text-sky-700 dark:text-sky-300">Hora de volver al trabajo</p><h2 class="mt-1 text-xl font-black text-ink">Tu pausa ha terminado</h2><p class="mt-2 text-sm text-muted">Cuando estés listo, utiliza el botón superior para comenzar un nuevo período.</p></div></div></section>
        </div>
    @endif
</div>

@once
    <script>
        window.playBreakAlarm = window.playBreakAlarm || function () {
            if (!window.breakAudioContext) return;
            const oscillator = window.breakAudioContext.createOscillator();
            const gain = window.breakAudioContext.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.value = 880;
            gain.gain.setValueAtTime(0.0001, window.breakAudioContext.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.18, window.breakAudioContext.currentTime + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, window.breakAudioContext.currentTime + 0.7);
            oscillator.connect(gain).connect(window.breakAudioContext.destination);
            oscillator.start();
            oscillator.stop(window.breakAudioContext.currentTime + 0.72);
        };
        window.enableBreakAudio = window.enableBreakAudio || function () {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            window.breakAudioContext = window.breakAudioContext || new AudioContext();
            window.breakAudioContext.resume();
            window.playBreakAlarm();
        };
    </script>
@endonce
