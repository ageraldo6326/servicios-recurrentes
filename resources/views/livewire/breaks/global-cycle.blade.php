<div wire:poll.5s="tick"
    x-data="{ target: @js($targetAt), remaining: @js($remainingSeconds), sessionId: @js($sessionId), customSoundUrl: @js($customSoundUrl), soundOnBreak: @js($soundOnBreak), soundOnReturn: @js($soundOnReturn), timer: null }"
    x-init="window.breakCustomSoundUrl = customSoundUrl; timer = setInterval(() => { if (target) remaining = Math.max(0, Math.ceil((new Date(target).getTime() - Date.now()) / 1000)); }, 1000); window.armBreakTimer?.(target, @js($status), sessionId, @js($status === 'break_active' ? $soundOnReturn : $soundOnBreak)); $watch('$wire.targetAt', value => { target = value; sessionId = $wire.sessionId; window.armBreakTimer?.(value, $wire.status, sessionId, $wire.status === 'break_active' ? soundOnReturn : soundOnBreak); }); $watch('$wire.status', value => window.armBreakTimer?.(target, value, sessionId, value === 'break_active' ? soundOnReturn : soundOnBreak)); $watch('$wire.customSoundUrl', value => { customSoundUrl = value; window.breakCustomSoundUrl = value; })"
    x-on:break-cycle-alert.window="window.notifyBreakAlert?.(event.detail.kind, event.detail.token)"
    class="relative flex min-w-0 items-center">
    <div class="flex min-w-0 items-center gap-2">
        @if ($status === 'working' && $enabled)
            <div class="inline-flex h-10 max-w-[220px] items-center gap-2 rounded-xl border border-line bg-surface px-3 text-sm font-bold text-ink shadow-sm">
                <span class="text-brand">◷</span>
                <span class="truncate">Trabajo <span class="tabular-nums text-brand" x-text="Math.floor(remaining / 60).toString().padStart(2, '0') + ':' + (remaining % 60).toString().padStart(2, '0')">{{ gmdate('i:s', $remainingSeconds) }}</span></span>
            </div>
        @elseif ($status === 'break_pending' && $enabled)
            <div class="inline-flex h-10 items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 text-sm font-bold text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100"><span>🔔</span><span class="hidden sm:inline">Pausa pendiente</span><span class="sm:hidden">Pausa</span></div>
        @elseif ($status === 'break_active' && $enabled)
            <div class="inline-flex h-10 items-center gap-2 rounded-xl border border-brand/30 bg-brand/5 px-3 text-sm font-bold text-brand"><span>🧘</span><span class="hidden sm:inline">Pausa</span><span class="tabular-nums" x-text="Math.floor(remaining / 60).toString().padStart(2, '0') + ':' + (remaining % 60).toString().padStart(2, '0')">{{ gmdate('i:s', $remainingSeconds) }}</span></div>
        @endif
    </div>

    @if ($enabled)
        <div class="ml-2 flex min-w-0 max-w-full items-center gap-1.5 rounded-xl border border-line bg-surface/95 p-1.5 shadow-sm backdrop-blur">
            @if ($status === 'working')
                <button type="button" wire:click="pauseWork" class="inline-flex h-9 shrink-0 items-center justify-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-2.5 text-[11px] font-black text-amber-800 transition hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200 sm:px-3" aria-label="Pausar contador" title="Pausar contador"><span aria-hidden="true">⏸</span><span>Pausar</span></button>
            @elseif ($status === 'paused')
                <button type="button" wire:click="resumeWork" class="button h-9 px-2.5 text-[11px] sm:px-3">▶ Reanudar</button>
            @elseif (in_array($status, ['break_completed', 'break_cancelled', 'work_pending'], true))
                <button type="button" x-on:click="window.enableBreakAudio?.()" wire:click="startWork" class="button h-9 px-2.5 text-[11px] sm:px-3">▶ Trabajar</button>
            @else
                <button type="button" x-on:click="window.enableBreakAudio?.()" wire:click="startWork" class="button h-9 px-2.5 text-[11px] sm:px-3">▶ Iniciar pausas</button>
            @endif
            @if ($soundOnBreak || $soundOnReturn)
                <button type="button" x-on:click="window.enableBreakAudio?.(); $el.setAttribute('aria-label', 'Sonido activado'); $el.textContent = '🔊'" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-line bg-surface text-sm text-muted transition hover:border-brand hover:text-brand" aria-label="Activar sonido de pausas" title="Activar sonido de pausas">🔈</button>
            @endif
        </div>
    @endif

    @if ($status === 'break_pending' && $enabled)
        <div class="pointer-events-none absolute left-0 top-full z-[70] mt-3 w-[min(36rem,calc(100vw-2rem))]">
            <section class="pointer-events-auto w-full overflow-hidden rounded-2xl border border-amber-200 bg-surface p-4 shadow-2xl dark:border-amber-800 sm:p-5" role="alert" aria-live="assertive">
                <div class="flex items-start gap-3"><div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-amber-100 text-xl dark:bg-amber-950/60">🔔</div><div class="min-w-0"><p class="text-xs font-black uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Hora de descansar</p><h2 class="mt-1 text-xl font-black text-ink">Toma una pausa activa</h2><p class="mt-2 text-sm text-muted">Has trabajado durante {{ $workMinutes }} minutos. Tu pausa durará {{ $breakMinutes }} minutos.</p></div></div>
                @if ($exerciseName)<div class="mt-4 rounded-xl bg-surface-soft p-3"><p class="text-xs font-bold uppercase tracking-wider text-brand">Ejercicio sugerido</p><p class="mt-1 font-black text-ink">{{ $exerciseName }}</p><p class="mt-1 text-sm text-muted">{{ $exerciseDescription }}</p></div>@endif
                <div class="mt-4 grid grid-cols-2 gap-2"><button type="button" x-on:click="window.enableBreakAudio?.(false); if (soundOnBreak) window.playBreakStartAlarm?.()" wire:click="takeBreak" class="button min-h-11 w-full whitespace-nowrap px-2 text-xs sm:text-sm">▶ Tomar pausa</button><button type="button" wire:click="cancelBreak" class="button-secondary min-h-11 w-full whitespace-nowrap px-2 text-xs sm:text-sm">Omitir</button></div>
            </section>
        </div>
    @elseif ($status === 'break_active' && $enabled)
        <div class="pointer-events-none absolute left-0 top-full z-[70] mt-3 w-[min(36rem,calc(100vw-2rem))]">
            <section class="pointer-events-auto w-full overflow-hidden rounded-2xl border border-brand/25 bg-surface p-4 shadow-2xl sm:p-5" role="status" aria-live="polite">
                <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-[0.18em] text-brand">Pausa activa</p><h2 class="mt-1 text-xl font-black text-ink">{{ $exerciseName ?: 'Movimiento libre' }}</h2></div><span class="text-2xl">🧘</span></div>
                @if ($exerciseInstructions)<p class="mt-3 text-sm text-muted">{{ $exerciseInstructions }}</p>@endif
                <div class="mt-4 text-center"><p class="text-5xl font-black tabular-nums text-brand" x-text="Math.floor(remaining / 60).toString().padStart(2, '0') + ':' + (remaining % 60).toString().padStart(2, '0')">{{ gmdate('i:s', $remainingSeconds) }}</p><div class="mt-3 h-2 overflow-hidden rounded-full bg-surface-soft"><div class="h-full rounded-full bg-brand transition-all" :style="{ width: (Math.max(0, Math.min(100, 100 - (remaining / {{ max(1, $breakMinutes * 60) }}) * 100)) + '%') }"></div></div></div>
                <p class="mt-3 text-center text-xs text-muted">El temporizador usa la hora registrada y continúa aunque cambies de módulo.</p>
                <button type="button" wire:click="cancelActiveBreak" wire:confirm="¿Cancelar esta pausa y detener el conteo?" class="button-secondary mt-4 w-full border-red-200 text-xs text-red-600 hover:border-red-400 hover:text-red-700">Cancelar pausa</button>
            </section>
        </div>
    @elseif (in_array($status, ['break_completed', 'break_cancelled', 'work_pending'], true) && $enabled)
        <div class="pointer-events-none absolute left-0 top-full z-[70] mt-3 w-[min(36rem,calc(100vw-2rem))]">
            <section class="pointer-events-auto w-full overflow-hidden rounded-2xl border border-sky-200 bg-surface p-4 shadow-2xl dark:border-sky-800 sm:p-5" role="alert" aria-live="assertive"><div class="flex items-start gap-3"><div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-sky-100 text-xl dark:bg-sky-950/60">🔔</div><div><p class="text-xs font-black uppercase tracking-[0.18em] text-sky-700 dark:text-sky-300">{{ $status === 'break_cancelled' ? 'Pausa cancelada' : 'Hora de volver al trabajo' }}</p><h2 class="mt-1 text-xl font-black text-ink">{{ $status === 'break_cancelled' ? 'La pausa fue detenida' : 'Tu pausa ha terminado' }}</h2><p class="mt-2 text-sm text-muted">Confirma para iniciar un nuevo período de trabajo y programar la próxima pausa.</p></div></div><button type="button" x-on:click="window.enableBreakAudio?.()" wire:click="startWork" class="button mt-4 min-h-11 w-full text-sm">▶ Comenzar nuevo período de trabajo</button></section>
        </div>
    @endif
</div>

@once
    <script>
        window.breakAlertChannel = window.breakAlertChannel || ('BroadcastChannel' in window ? new BroadcastChannel('service-manager-breaks') : null);
        window.breakWorker = window.breakWorker || null;
        window.breakAlarmBusy = false;

        window.playBreakAlarm = window.playBreakAlarm || function () {
            if (window.breakCustomSoundUrl) {
                const customAudio = new Audio(window.breakCustomSoundUrl);
                customAudio.volume = 1;
                customAudio.play().catch(() => {});
                return;
            }
            const context = window.breakAudioContext;
            if (!context || window.breakAlarmBusy) return;
            window.breakAlarmBusy = true;
            const now = context.currentTime;
            const master = context.createGain();
            const filter = context.createBiquadFilter();
            master.gain.setValueAtTime(0.0001, now);
            master.gain.exponentialRampToValueAtTime(0.72, now + 0.04);
            master.gain.exponentialRampToValueAtTime(0.0001, now + 0.95);
            filter.type = 'lowpass';
            filter.frequency.value = 2200;
            filter.Q.value = 1.4;
            filter.connect(master).connect(context.destination);
            [523.25, 659.25, 783.99].forEach((frequency, index) => {
                const oscillator = context.createOscillator();
                oscillator.type = 'sawtooth';
                oscillator.frequency.setValueAtTime(frequency, now);
                oscillator.detune.value = index * 3;
                oscillator.connect(filter);
                oscillator.start(now);
                oscillator.stop(now + 0.95);
            });
            window.setTimeout(() => { window.breakAlarmBusy = false; }, 1100);
        };

        window.playBreakStartAlarm = window.playBreakStartAlarm || function () {
            const context = window.breakAudioContext;
            if (!context || window.breakAlarmBusy) return;
            window.breakAlarmBusy = true;
            const now = context.currentTime;
            const master = context.createGain();
            master.gain.setValueAtTime(0.0001, now);
            master.gain.exponentialRampToValueAtTime(0.32, now + 0.08);
            master.gain.exponentialRampToValueAtTime(0.0001, now + 1.2);
            master.connect(context.destination);
            [659.25, 783.99, 1046.5].forEach((frequency, index) => {
                const oscillator = context.createOscillator();
                const note = context.createGain();
                const start = now + index * 0.18;
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(frequency, start);
                note.gain.setValueAtTime(0.0001, start);
                note.gain.exponentialRampToValueAtTime(0.55, start + 0.03);
                note.gain.exponentialRampToValueAtTime(0.0001, start + 0.38);
                oscillator.connect(note).connect(master);
                oscillator.start(start);
                oscillator.stop(start + 0.4);
            });
            window.setTimeout(() => { window.breakAlarmBusy = false; }, 1300);
        };

        window.enableBreakAudio = window.enableBreakAudio || function (playSound = true) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            window.breakAudioContext = window.breakAudioContext || new AudioContext();
            window.breakAudioContext.resume();
            if ('Notification' in window && Notification.permission === 'default') Notification.requestPermission().catch(() => {});
            if (playSound) window.playBreakAlarm();
        };

        window.notifyBreakAlert = window.notifyBreakAlert || function (kind, token) {
            if (!token) return;
            const rawToken = String(token);
            const keyParts = rawToken.split(':');
            const key = 'break-alert:' + keyParts[0] + ':' + (keyParts[1] || 'unknown').replace('-visual', '');
            if (localStorage.getItem(key) === '1') return;
            localStorage.setItem(key, '1');
            window.breakAlertChannel?.postMessage({ key });
            const isReturn = kind.includes('finished');
            if (!kind.endsWith('visual')) {
                isReturn ? window.playBreakAlarm?.() : window.playBreakStartAlarm?.();
            }
            if ('Notification' in window && Notification.permission === 'granted') {
                const notification = new Notification(isReturn ? '🔔 Hora de volver al trabajo' : '📯 Hora de hacer una pausa', { body: isReturn ? 'Tu pausa terminó. Confirma para comenzar a trabajar.' : 'Tu período de trabajo terminó. Toma una pausa activa.', tag: key, requireInteraction: true });
                notification.onclick = () => { window.focus(); notification.close(); };
            }
            const originalTitle = document.title;
            let flashes = 0;
            const titleTimer = window.setInterval(() => { document.title = flashes++ % 2 ? originalTitle : (isReturn ? '🔔 Volver al trabajo' : '📯 Hora de descansar'); if (flashes > 7) { window.clearInterval(titleTimer); document.title = originalTitle; } }, 700);
            if (navigator.vibrate) navigator.vibrate([300, 120, 300]);
        };

        window.armBreakTimer = window.armBreakTimer || function (target, status, sessionId, soundEnabled = true) {
            if (window.breakWorker) window.breakWorker.terminate();
            if (!target || !sessionId || !['working', 'break_active'].includes(status)) return;
            const source = `self.onmessage=e=>{const d=e.data;const wait=Math.max(0,new Date(d.target).getTime()-Date.now());setTimeout(()=>postMessage(d),wait)}`;
            const worker = new Worker(URL.createObjectURL(new Blob([source], { type: 'application/javascript' })));
            window.breakWorker = worker;
            worker.onmessage = event => { const kindBase = event.data.status === 'break_active' ? 'break-finished' : 'break-start'; const kind = event.data.soundEnabled ? kindBase : kindBase + '-visual'; window.notifyBreakAlert?.(kind, event.data.sessionId + ':' + kind); };
            worker.postMessage({ target, status, sessionId, soundEnabled });
        };
        window.breakAlertChannel?.addEventListener('message', event => localStorage.setItem(event.data.key, '1'));
    </script>
@endonce
