<!DOCTYPE html>
<html lang="es" class="h-full" x-data="{ dark: localStorage.getItem('theme') === 'dark' }" :class="{ 'dark': dark }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Servicios Recurrentes' }}</title>
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia(
                '(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');

        // Debe existir antes de que Livewire inicie Alpine y evalúe x-data en el menú lateral.
        window.sidebarMenuOrder = (section, updateUrl, csrfToken) => ({
            draggingKey: null,
            isSaving: false,
            hasQueuedSave: false,
            savedOrder: '',
            saveError: false,
            pointerId: null,
            pointerMoveHandler: null,
            pointerEndHandler: null,

            init() {
                this.savedOrder = this.currentOrder();
                this.pointerMoveHandler = (event) => this.pointerMove(event);
                this.pointerEndHandler = (event) => this.pointerEnd(event);
                window.addEventListener('pointermove', this.pointerMoveHandler, { passive: false });
                window.addEventListener('pointerup', this.pointerEndHandler);
                window.addEventListener('pointercancel', this.pointerEndHandler);
            },

            destroy() {
                window.removeEventListener('pointermove', this.pointerMoveHandler);
                window.removeEventListener('pointerup', this.pointerEndHandler);
                window.removeEventListener('pointercancel', this.pointerEndHandler);
            },

            startPointer(event, key) {
                if (event.isPrimary === false || (event.pointerType === 'mouse' && event.button !== 0)) {
                    return;
                }

                this.draggingKey = key;
                this.pointerId = event.pointerId;
                this.saveError = false;
                event.currentTarget.setPointerCapture?.(event.pointerId);
            },

            pointerMove(event) {
                if (!this.draggingKey || this.pointerId !== event.pointerId) {
                    return;
                }

                const targetItem = document.elementFromPoint(event.clientX, event.clientY)
                    ?.closest('[data-menu-item-key]');

                if (!targetItem || !this.$el.contains(targetItem)) {
                    return;
                }

                event.preventDefault();
                this.move(event, targetItem.dataset.menuItemKey);
            },

            pointerEnd(event) {
                if (this.pointerId !== event.pointerId) {
                    return;
                }

                this.pointerId = null;
                this.finish();
            },

            move(event, targetKey) {
                if (!this.draggingKey || this.draggingKey === targetKey) {
                    return;
                }

                const draggedItem = this.item(this.draggingKey);
                const targetItem = this.item(targetKey);

                if (!draggedItem || !targetItem) {
                    return;
                }

                const targetBounds = targetItem.getBoundingClientRect();
                const insertBeforeTarget = event.clientY < targetBounds.top + (targetBounds.height / 2);

                targetItem.parentNode.insertBefore(draggedItem, insertBeforeTarget ? targetItem : targetItem.nextSibling);
            },

            finish() {
                if (!this.draggingKey) {
                    return;
                }

                this.draggingKey = null;
                this.save();
            },

            item(key) {
                return this.$el.querySelector(`[data-menu-item-key="${key}"]`);
            },

            currentOrder() {
                return JSON.stringify([...this.$el.querySelectorAll('[data-menu-item-key]')]
                    .map((item) => item.dataset.menuItemKey));
            },

            async save() {
                const order = this.currentOrder();

                if (order === this.savedOrder) {
                    return;
                }

                if (this.isSaving) {
                    this.hasQueuedSave = true;
                    return;
                }

                this.isSaving = true;
                this.saveError = false;

                try {
                    const response = await fetch(updateUrl, {
                        method: 'PUT',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ section, order: JSON.parse(order) }),
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo guardar el orden del menú.');
                    }

                    this.savedOrder = order;
                } catch (error) {
                    this.saveError = true;
                } finally {
                    this.isSaving = false;

                    if (this.hasQueuedSave) {
                        this.hasQueuedSave = false;
                        this.save();
                    }
                }
            },
        });
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="h-full font-sans">
    <div x-data="{ menu: false }" class="app-shell">
        <div x-show="menu" x-transition.opacity class="fixed inset-0 z-30 bg-ink/40 lg:hidden" @click="menu = false">
        </div>
        <aside :class="menu ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col overflow-y-auto overscroll-contain border-r border-line bg-surface px-4 py-5 transition-transform duration-200 lg:w-64">
            <div class="flex items-center gap-3 px-3">
                <div class="grid h-10 w-10 place-items-center rounded-xl bg-ink text-lg font-black text-white">SR</div>
                <div>
                    <p class="text-lg font-black tracking-tight text-ink">ServiceManager</p>
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted">B2B operations</p>
                </div>
            </div>
            @php($sidebarSections = \App\Support\SidebarNavigation::forUser(auth()->user()))
            <nav class="mt-8 space-y-1" aria-label="Navegación principal" aria-describedby="sidebar-order-help">
                <p id="sidebar-order-help" class="px-3 pb-3 text-xs text-muted">Arrastra ⠿ para ordenar los accesos. El cambio se guarda automáticamente.</p>
                @foreach ($sidebarSections as $section)
                    <div class="{{ $loop->first ? '' : 'mt-5 border-t border-line pt-4' }}"
                        x-data="sidebarMenuOrder(@js($section['key']), @js(route('settings.sidebar-menu-order.update')), @js(csrf_token()))"
                        x-init="init()">
                        <p class="px-3 pb-2 text-[10px] font-black uppercase tracking-[0.2em] text-muted">{{ $section['label'] }}</p>
                        @foreach ($section['items'] as $item)
                            <a wire:navigate href="{{ route($item['route']) }}"
                                class="sidebar-link {{ request()->routeIs(...$item['active']) ? 'active' : '' }}"
                                data-menu-item-key="{{ $item['key'] }}"
                                :class="{ 'opacity-60': draggingKey === @js($item['key']) }">
                                <span class="text-lg">{{ $item['icon'] }}</span>
                                <span>{{ $item['label'] }}</span>
                                <span class="ml-auto cursor-grab touch-none select-none text-base leading-none text-muted active:cursor-grabbing"
                                    aria-hidden="true" title="Arrastrar para reordenar"
                                    @pointerdown.prevent.stop="startPointer($event, @js($item['key']))"
                                    @click.prevent.stop>⠿</span>
                            </a>
                        @endforeach
                        <p x-cloak x-show="isSaving" class="px-3 pt-2 text-xs font-semibold text-brand" aria-live="polite">Guardando orden…</p>
                        <p x-cloak x-show="saveError" class="px-3 pt-2 text-xs font-semibold text-red-600" role="alert">No se pudo guardar el orden. Inténtalo de nuevo.</p>
                    </div>
                @endforeach
            </nav>
            <div class="mt-auto border-t border-line pt-4">
                @auth
                    <a wire:navigate href="{{ route('profile') }}"
                        class="flex items-center gap-3 rounded-xl px-3 py-3 hover:bg-surface-soft">
                        <span
                            class="grid h-10 w-10 place-items-center rounded-full bg-brand/15 font-bold text-brand">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                        <span class="min-w-0"><span
                                class="block truncate text-sm font-bold text-ink">{{ auth()->user()->name }}</span><span
                                class="block truncate text-xs text-muted">{{ auth()->user()->email }}</span></span>
                    </a>
                    <form method="post" action="{{ route('logout') }}" class="px-3">@csrf<button
                            class="w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-red-600 transition hover:bg-red-50 dark:hover:bg-red-950/30">↪
                            Cerrar sesión</button></form>
                @endauth
            </div>
        </aside>

        <div class="lg:pl-64">
            <div class="hidden" aria-hidden="true">
                <livewire:layout.navigation />
            </div>
            <header class="relative sticky top-0 z-20 border-b border-line bg-surface/90 backdrop-blur">
                <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                    <button @click="menu = !menu"
                        class="grid h-10 w-10 place-items-center rounded-xl border border-line text-xl lg:hidden"
                        aria-label="Abrir menú">☰</button>
                    @auth
                        <div class="min-w-0 flex-1">
                            <livewire:breaks.global-cycle />
                        </div>
                        <livewire:tasks.global-reminder />
                    @endauth
                    <div class="ml-auto flex shrink-0 items-center gap-2">
                        <button type="button" x-on:click="$dispatch('business-coach-analyze')" wire:loading.attr="disabled"
                            class="button-secondary hidden sm:inline-flex" aria-label="Analizar pantalla">
                            <span class="mr-2">🧠</span> Analizar
                        </button>
                        <div x-data="{
                                now: new Date(),
                                open: false,
                                monthDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
                                selectedDate: new Date(),
                                get monthLabel() {
                                    return this.monthDate.toLocaleDateString('es-DO', { month: 'long', year: 'numeric' });
                                },
                                get selectedLabel() {
                                    return this.selectedDate.toLocaleDateString('es-DO', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                                },
                                get days() {
                                    const firstDay = new Date(this.monthDate.getFullYear(), this.monthDate.getMonth(), 1);
                                    const offset = (firstDay.getDay() + 6) % 7;
                                    const totalDays = new Date(this.monthDate.getFullYear(), this.monthDate.getMonth() + 1, 0).getDate();
                                    const days = Array(offset).fill(null);
                                    for (let day = 1; day <= totalDays; day++) days.push(new Date(this.monthDate.getFullYear(), this.monthDate.getMonth(), day));
                                    return days;
                                },
                                isToday(day) {
                                    return day && day.toDateString() === new Date().toDateString();
                                },
                                isSelected(day) {
                                    return day && day.toDateString() === this.selectedDate.toDateString();
                                },
                                previousMonth() {
                                    this.monthDate = new Date(this.monthDate.getFullYear(), this.monthDate.getMonth() - 1, 1);
                                },
                                nextMonth() {
                                    this.monthDate = new Date(this.monthDate.getFullYear(), this.monthDate.getMonth() + 1, 1);
                                },
                                chooseDay(day) {
                                    if (day) this.selectedDate = day;
                                },
                                goToday() {
                                    this.selectedDate = new Date();
                                    this.monthDate = new Date(this.selectedDate.getFullYear(), this.selectedDate.getMonth(), 1);
                                }
                            }"
                            x-init="setInterval(() => now = new Date(), 1000)"
                            @click.outside="open = false"
                            @keydown.escape.window="open = false"
                            class="relative hidden sm:block">
                            <button type="button" @click="open = !open"
                                class="inline-flex h-10 items-center gap-2 rounded-xl border border-line bg-surface px-3 text-sm font-bold tabular-nums text-ink transition hover:border-brand hover:text-brand"
                                aria-live="polite" aria-label="Abrir calendario y ver la hora actual"
                                :aria-expanded="open">
                                <span class="text-base text-brand">◷</span>
                                <span x-text="now.toLocaleTimeString('es-DO', { hour: '2-digit', minute: '2-digit', second: '2-digit' })"></span>
                            </button>

                            <div x-cloak x-show="open" x-transition style="display: none"
                                class="absolute right-0 top-full z-[70] mt-3 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-line bg-surface p-4 shadow-2xl"
                                role="dialog" aria-label="Calendario">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-black uppercase tracking-[0.16em] text-brand">Calendario</p>
                                        <p class="mt-1 text-sm font-bold capitalize text-ink" x-text="selectedLabel"></p>
                                    </div>
                                    <button type="button" @click="open = false"
                                        class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-line text-lg text-muted transition hover:border-brand hover:text-brand"
                                        aria-label="Cerrar calendario">×</button>
                                </div>

                                <div class="mt-4 flex items-center justify-between">
                                    <button type="button" @click="previousMonth"
                                        class="grid h-9 w-9 place-items-center rounded-lg border border-line text-lg text-ink transition hover:border-brand hover:text-brand"
                                        aria-label="Mes anterior">‹</button>
                                    <p class="text-base font-black capitalize text-ink" x-text="monthLabel"></p>
                                    <button type="button" @click="nextMonth"
                                        class="grid h-9 w-9 place-items-center rounded-lg border border-line text-lg text-ink transition hover:border-brand hover:text-brand"
                                        aria-label="Mes siguiente">›</button>
                                </div>

                                <div class="mt-4 grid grid-cols-7 gap-1 text-center">
                                    <template x-for="(weekday, index) in ['L','M','M','J','V','S','D']" :key="index">
                                        <span class="py-1 text-[10px] font-black uppercase tracking-wider text-muted" x-text="weekday"></span>
                                    </template>
                                    <template x-for="(day, index) in days" :key="index">
                                        <button type="button" @click="chooseDay(day)" :disabled="!day"
                                            class="grid aspect-square place-items-center rounded-lg text-sm font-bold transition"
                                            :class="!day ? 'cursor-default' : (isSelected(day) ? 'bg-brand text-white' : (isToday(day) ? 'border border-brand text-brand hover:bg-brand/10' : 'text-ink hover:bg-surface-soft'))"
                                            :aria-label="day ? day.toLocaleDateString('es-DO', { day: 'numeric', month: 'long', year: 'numeric' }) : null"
                                            x-text="day ? day.getDate() : ''"></button>
                                    </template>
                                </div>

                                <button type="button" @click="goToday"
                                    class="button-secondary mt-4 w-full text-xs">Ir a hoy</button>
                            </div>
                        </div>
                        <button @click="dark = !dark; localStorage.setItem('theme', dark ? 'dark' : 'light')"
                            class="grid h-10 w-10 place-items-center rounded-xl border border-line text-lg"
                            :aria-label="dark ? 'Activar modo claro' : 'Activar modo oscuro'"><span
                                x-text="dark ? '☀' : '☾'"></span></button>
                        <a wire:navigate href="{{ route('profile') }}"
                            class="hidden rounded-xl px-3 py-2 text-sm font-bold text-muted hover:bg-surface-soft sm:block">Mi
                            cuenta</a>
                    </div>
                </div>
            </header>
            <livewire:business-coach.panel />
            <livewire:ai-analysis.panel />
            <main class="mx-auto max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                @if (session('success'))
                    <div
                        class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                        {{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div
                        class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
                        {{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div
                        class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>
        </div>
    </div>
    @livewireScripts
</body>

</html>
