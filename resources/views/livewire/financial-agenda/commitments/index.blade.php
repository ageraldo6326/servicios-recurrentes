<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-brand">Gestión de Compromisos</p>
            <h1 class="text-2xl font-black tracking-tight text-ink sm:text-4xl">Compromisos financieros</h1>
            <p class="mt-2 text-sm text-muted">Consulta y administra las obligaciones recurrentes del negocio.</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row"><a wire:navigate
                href="{{ route('financial-agenda.beneficiaries.index') }}" class="button-secondary">Beneficiarios</a><a
                wire:navigate href="{{ route('financial-agenda.commitments.create') }}" class="button">＋ Nuevo
                compromiso</a></div>
    </div>
    <div class="panel mb-5">
        <div class="grid gap-3 md:grid-cols-[1fr_220px_auto]"><label class="relative"><span class="sr-only">Buscar
                    compromisos</span><span
                    class="pointer-events-none absolute left-3 top-3 text-lg text-muted">⌕</span><input
                    wire:model.live.debounce.300ms="search" class="input mt-0 pl-10"
                    placeholder="Buscar compromiso o beneficiario..."></label><select wire:model.live="status"
                class="input mt-0">
                <option value="all">Todos los estados</option>
                <option value="active">Activos</option>
                <option value="inactive">Inactivos</option>
            </select><button type="button" wire:click="clearFilters" class="button-secondary">Limpiar</button></div>
        <div wire:loading class="mt-3 text-xs font-semibold text-brand">Actualizando compromisos…</div>
    </div>
    <div class="surface overflow-hidden">
        <div class="hidden overflow-x-auto md:block">
            <table class="table">
                <thead>
                    <tr>
                        <th>Compromiso</th>
                        <th>Beneficiario</th>
                        <th>Categoría</th>
                        <th>Frecuencia</th>
                        <th>Corte</th>
                        <th>Límite de pago</th>
                        <th>Monto sugerido</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($commitments as $commitment)
                        <tr>
                            <td>
                                <p class="font-bold text-ink">{{ $commitment->name }}</p>
                                <p class="text-xs text-muted">{{ $commitment->payments_count }} períodos registrados</p>
                            </td>
                            <td class="text-sm text-muted">{{ $commitment->beneficiary->name }}</td>
                            <td class="text-sm text-muted">{{ $commitment->category }}</td>
                            <td class="text-sm text-muted">Mensual</td>
                            <td class="text-sm text-muted">
                                {{ $commitment->has_cutoff ? 'Día ' . $commitment->cutoff_day : 'No aplica' }}</td>
                            <td class="text-sm font-semibold text-ink">Día {{ $commitment->due_day }}</td>
                            <td class="text-sm font-semibold text-ink">
                                {{ $commitment->suggested_amount !== null ? number_format((float) $commitment->suggested_amount, 2) : '—' }}
                            </td>
                            <td><span
                                    class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $commitment->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300' }}">{{ $commitment->is_active ? 'Activo' : 'Inactivo' }}</span>
                            </td>
                            <td><a wire:navigate class="font-bold text-brand hover:underline"
                                    href="{{ route('financial-agenda.commitments.edit', $commitment) }}">Editar</a>
                            </td>
                    </tr>@empty<tr>
                            <td colspan="9" class="py-12 text-center text-sm text-muted">No hay compromisos
                                registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="divide-y divide-line md:hidden">
            @forelse($commitments as $commitment)
                <article class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-ink">{{ $commitment->name }}</p>
                            <p class="text-sm text-muted">{{ $commitment->beneficiary->name }} ·
                                {{ $commitment->category }}</p>
                        </div><span
                            class="rounded-full px-2 py-1 text-[10px] font-black uppercase {{ $commitment->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $commitment->is_active ? 'Activo' : 'Inactivo' }}</span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-muted">Corte</p>
                            <p class="font-semibold text-ink">
                                {{ $commitment->has_cutoff ? 'Día ' . $commitment->cutoff_day : 'No aplica' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted">Pago</p>
                            <p class="font-semibold text-ink">Día {{ $commitment->due_day }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted">Monto sugerido</p>
                            <p class="font-semibold text-ink">
                                {{ $commitment->suggested_amount !== null ? number_format((float) $commitment->suggested_amount, 2) : '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted">Frecuencia</p>
                            <p class="font-semibold text-ink">Mensual</p>
                        </div>
                    </div><a wire:navigate class="mt-4 block font-bold text-brand"
                        href="{{ route('financial-agenda.commitments.edit', $commitment) }}">Editar compromiso →</a>
            </article>@empty<div class="p-10 text-center text-sm text-muted">No hay compromisos registrados.</div>
            @endforelse
        </div>
        <div class="border-t border-line px-4 py-3">{{ $commitments->links() }}</div>
    </div>
</div>
