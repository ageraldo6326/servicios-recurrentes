<div>
    <div class="mb-6">
        <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-brand">Herramienta operativa</p>
        <h1 class="text-2xl font-black tracking-tight text-ink sm:text-4xl">Comparar IP de proveedor</h1>
        <p class="mt-2 max-w-3xl text-sm text-muted">Contrasta un inventario copiado con los servicios contratados activos. La información pegada se descarta al procesarla.</p>
    </div>

    <form wire:submit="compare" class="panel">
        <div class="grid gap-4 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
            <div>
                <label for="providerId" class="text-sm font-bold text-ink">Proveedor</label>
                <select id="providerId" wire:model.live="providerId" class="input">
                    <option value="">Selecciona un proveedor</option>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                    @endforeach
                </select>
                @error('providerId') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror

                <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-line bg-surface-soft p-3">
                    <input wire:model="inventoryComplete" type="checkbox" class="mt-1 rounded border-line text-brand focus:ring-brand">
                    <span><span class="block text-sm font-bold text-ink">Inventario completo</span><span class="mt-1 block text-xs text-muted">Activo por defecto. Si es parcial, las IP internas ausentes se muestran solo como referencia.</span></span>
                </label>
            </div>

            <div>
                <label for="pastedText" class="text-sm font-bold text-ink">Información copiada del proveedor</label>
                <textarea id="pastedText" wire:model.defer="pastedText" rows="9" maxlength="1048576" class="input font-mono text-xs" placeholder="Pegue aquí el inventario de IP del proveedor…"></textarea>
                <p class="mt-2 text-xs text-muted">Solo se extraerán IPv4 válidas. Este contenido no se guarda.</p>
                @error('pastedText') <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:justify-end">
            <button wire:click="clear" type="button" class="button-secondary">Limpiar</button>
            <button type="submit" class="button" wire:loading.attr="disabled" wire:target="compare">
                <span wire:loading.remove wire:target="compare">Extraer y comparar</span>
                <span wire:loading wire:target="compare">Comparando…</span>
            </button>
        </div>
    </form>

    @if ($comparison)
        <section class="mt-6" aria-live="polite">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-brand">Resultado temporal</p>
                    <h2 class="mt-1 text-xl font-black text-ink">{{ $comparison['provider']['name'] }}</h2>
                    <p class="mt-1 text-sm text-muted">Comparación procesada; el contenido original fue descartado.</p>
                </div>
                @if (! $inventoryComplete)
                    <span class="rounded-full bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-800 dark:bg-amber-950/50 dark:text-amber-200">Inventario marcado como parcial</span>
                @endif
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="card p-4"><p class="text-[11px] font-bold uppercase tracking-[0.16em] text-muted">IP válidas detectadas</p><p class="mt-1 text-2xl font-black text-ink">{{ $comparison['summary']['valid_pasted_ips'] }}</p></div>
                <div class="card border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-900 dark:bg-emerald-950/20"><p class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">IP coincidentes</p><p class="mt-1 text-2xl font-black text-emerald-700 dark:text-emerald-300">{{ $comparison['summary']['matching_ips'] }}</p></div>
                <div class="card border-red-200 bg-red-50/60 p-4 dark:border-red-900 dark:bg-red-950/20"><p class="text-[11px] font-bold uppercase tracking-[0.16em] text-red-700 dark:text-red-300">Proveedor sin servicio</p><p class="mt-1 text-2xl font-black text-red-700 dark:text-red-300">{{ $comparison['summary']['provider_only_ips'] }}</p></div>
                <div class="card border-amber-200 bg-amber-50/60 p-4 dark:border-amber-900 dark:bg-amber-950/20"><p class="text-[11px] font-bold uppercase tracking-[0.16em] text-amber-700 dark:text-amber-300">Servicios sin IP detectada</p><p class="mt-1 text-2xl font-black text-amber-700 dark:text-amber-300">{{ $comparison['summary']['service_only_ips'] }}</p></div>
            </div>

            @if ($comparison['summary']['duplicate_pasted_ips'] > 0)
                <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100">Se descartaron {{ $comparison['summary']['duplicate_pasted_ips'] }} ocurrencia(s) repetida(s) de IP.</p>
            @endif

            @if (! $inventoryComplete)
                <p class="mt-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-100">El paste se marcó como parcial; una IP interna ausente no confirma que el recurso haya desaparecido.</p>
            @endif

            <div class="mt-6 grid gap-5 xl:grid-cols-2">
                <section class="surface overflow-hidden">
                    <div class="border-b border-line px-4 py-3"><h3 class="font-black text-ink">IP coincidentes</h3><p class="mt-1 text-xs text-muted">El recurso detectado ya tiene servicio interno asociado.</p></div>
                    <div class="overflow-x-auto"><table class="table"><thead><tr><th>IP</th><th>Servicio contratado</th><th>Cliente</th><th>Estado</th></tr></thead><tbody>@forelse ($comparison['matches'] as $match)@foreach ($match['services'] as $service)<tr><td class="font-mono text-xs text-ink">{{ $match['ip'] }}</td><td class="font-semibold text-ink">{{ $service['name'] }}</td><td>{{ $service['client'] ?? '—' }}</td><td>Activo</td></tr>@endforeach@empty<tr><td colspan="4" class="py-8 text-center text-sm text-muted">No hay IP coincidentes.</td></tr>@endforelse</tbody></table></div>
                </section>

                <section class="surface overflow-hidden">
                    <div class="border-b border-line px-4 py-3"><h3 class="font-black text-ink">IP del proveedor sin servicio contratado</h3><p class="mt-1 text-xs text-muted">Revise si corresponde registrar o asociar un servicio fuera de este módulo.</p></div>
                    <div class="overflow-x-auto"><table class="table"><thead><tr><th>IP detectada</th><th>Resultado</th><th>Recomendación</th></tr></thead><tbody>@forelse ($comparison['provider_only'] as $item)<tr><td class="font-mono text-xs font-bold text-red-700 dark:text-red-300">{{ $item['ip'] }}</td><td>No registrada</td><td class="text-sm text-muted">Revisar gestión comercial.</td></tr>@empty<tr><td colspan="3" class="py-8 text-center text-sm text-muted">No hay diferencias en el proveedor.</td></tr>@endforelse</tbody></table></div>
                </section>

                <section class="surface overflow-hidden xl:col-span-2">
                    <div class="border-b border-line px-4 py-3"><h3 class="font-black text-ink">Servicios contratados sin IP en el paste</h3><p class="mt-1 text-xs text-muted">{{ $inventoryComplete ? 'Revise el inventario o un posible cambio de IP.' : 'Dato informativo porque el inventario se marcó como parcial.' }}</p></div>
                    <div class="overflow-x-auto"><table class="table"><thead><tr><th>IP registrada</th><th>Servicio contratado</th><th>Cliente</th><th>Resultado</th></tr></thead><tbody>@forelse ($comparison['service_only'] as $item)<tr><td class="font-mono text-xs text-ink">{{ $item['ip'] }}</td><td class="font-semibold text-ink">{{ $item['service']['name'] }}</td><td>{{ $item['service']['client'] ?? '—' }}</td><td>{{ $inventoryComplete ? 'No detectada en el inventario pegado' : 'No detectada (inventario parcial)' }}</td></tr>@empty<tr><td colspan="4" class="py-8 text-center text-sm text-muted">No hay servicios internos sin coincidencia.</td></tr>@endforelse</tbody></table></div>
                </section>

                @if ($comparison['services_without_internal_ip'] !== [])
                    <section class="surface overflow-hidden xl:col-span-2">
                        <div class="border-b border-line px-4 py-3"><h3 class="font-black text-ink">Servicios sin IP interna válida</h3><p class="mt-1 text-xs text-muted">No pueden compararse automáticamente.</p></div>
                        <div class="overflow-x-auto"><table class="table"><thead><tr><th>Servicio contratado</th><th>Cliente</th><th>IP registrada</th><th>Estado</th></tr></thead><tbody>@foreach ($comparison['services_without_internal_ip'] as $service)<tr><td class="font-semibold text-ink">{{ $service['name'] }}</td><td>{{ $service['client'] ?? '—' }}</td><td class="font-mono text-xs">{{ $service['stored_ip'] ?: '—' }}</td><td>Activo</td></tr>@endforeach</tbody></table></div>
                    </section>
                @endif

                @if ($comparison['invalid_or_discarded'] !== [])
                    <section class="surface overflow-hidden xl:col-span-2">
                        <div class="border-b border-line px-4 py-3"><h3 class="font-black text-ink">IP inválidas o descartadas</h3></div>
                        <div class="overflow-x-auto"><table class="table"><thead><tr><th>Valor candidato</th><th>Motivo</th></tr></thead><tbody>@foreach ($comparison['invalid_or_discarded'] as $item)<tr><td class="font-mono text-xs text-ink">{{ $item['value'] }}</td><td>{{ $item['reason'] }}</td></tr>@endforeach</tbody></table></div>
                    </section>
                @endif
            </div>
        </section>
    @endif
</div>
