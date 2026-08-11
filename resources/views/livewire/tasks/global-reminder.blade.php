<div wire:poll.15s="tick"
    x-data="{ target: @js($targetAt), taskId: @js($taskId), taskTitle: @js($taskTitle), token: @js($token), visible: false, worker: null }"
    x-init="window.armTaskReminder?.(target, taskId, taskTitle, token); $watch('$wire.targetAt', value => { target = value; taskId = $wire.taskId; taskTitle = $wire.taskTitle; token = $wire.token; window.armTaskReminder?.(target, taskId, taskTitle, token); }); $watch('$wire.taskId', value => taskId = value); $watch('$wire.taskTitle', value => taskTitle = value)"
    x-on:task-reminder-alert.window="if (event.detail.token === token) { visible = true; }"
    x-on:task-reminder-dismiss.window="visible = false"
    class="pointer-events-none fixed right-4 top-20 z-[80] w-[min(36rem,calc(100vw-2rem))]">
    <section x-show="visible" x-transition class="pointer-events-auto w-full overflow-hidden rounded-2xl border border-amber-200 bg-surface p-4 shadow-2xl dark:border-amber-800 sm:p-5" role="alert" aria-live="assertive">
        <div class="flex items-start gap-3">
            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-amber-100 text-xl dark:bg-amber-950/60">🔔</div>
            <div class="min-w-0">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Recordatorio de tarea</p>
                <h2 class="mt-1 text-xl font-black text-ink" x-text="taskTitle"></h2>
                <p class="mt-2 text-sm text-muted">Se acerca la hora de este compromiso.</p>
            </div>
        </div>
        <button type="button" x-on:click="visible = false" class="button mt-4 min-h-11 w-full text-sm">Entendido</button>
    </section>
</div>

@once
    <script>
        window.taskReminderChannel = window.taskReminderChannel || ('BroadcastChannel' in window ? new BroadcastChannel('service-manager-task-reminders') : null);
        window.taskReminderWorker = window.taskReminderWorker || null;

        window.notifyTaskReminder = window.notifyTaskReminder || function (taskId, taskTitle, token) {
            if (!token) return;
            const key = 'task-reminder:' + token;
            if (localStorage.getItem(key) === '1') return;
            localStorage.setItem(key, '1');
            window.taskReminderChannel?.postMessage({ key });
            window.playBreakAlarm?.();
            window.dispatchEvent(new CustomEvent('task-reminder-alert', { detail: { taskId, taskTitle, token } }));
            if ('Notification' in window && Notification.permission === 'granted') {
                const notification = new Notification('🔔 Recordatorio de tarea', { body: taskTitle, tag: key, requireInteraction: true });
                notification.onclick = () => { window.focus(); notification.close(); };
            }
            const originalTitle = document.title;
            let flashes = 0;
            const titleTimer = window.setInterval(() => { document.title = flashes++ % 2 ? originalTitle : '🔔 Recordatorio de tarea'; if (flashes > 7) { window.clearInterval(titleTimer); document.title = originalTitle; } }, 700);
            if (navigator.vibrate) navigator.vibrate([300, 120, 300]);
        };

        window.armTaskReminder = window.armTaskReminder || function (target, taskId, taskTitle, token) {
            if (window.taskReminderWorker) window.taskReminderWorker.terminate();
            if (!target || !taskId || !token) return;
            const source = `self.onmessage=e=>{const d=e.data;const wait=Math.max(0,new Date(d.target).getTime()-Date.now());setTimeout(()=>postMessage(d),wait)}`;
            const worker = new Worker(URL.createObjectURL(new Blob([source], { type: 'application/javascript' })));
            window.taskReminderWorker = worker;
            worker.onmessage = event => window.notifyTaskReminder?.(event.data.taskId, event.data.taskTitle, event.data.token);
            worker.postMessage({ target, taskId, taskTitle, token });
        };

        window.taskReminderChannel?.addEventListener('message', event => localStorage.setItem(event.data.key, '1'));
        if ('Notification' in window && Notification.permission === 'default') Notification.requestPermission().catch(() => {});
    </script>
@endonce
