<div>
    <div class="mb-6 flex flex-col gap-4 lg:mb-8 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="mb-2 text-xs font-black uppercase tracking-[0.18em] text-brand">Visión financiera</p>
            <h1 class="text-2xl font-black tracking-tight text-ink sm:text-4xl">Flujo histórico</h1>
            <p class="mt-2 max-w-2xl text-sm text-muted">Compara los ingresos reales con los egresos pagados a través del tiempo.</p>
        </div>
        <label class="w-full text-xs font-bold text-muted sm:w-52">
            Período
            <select wire:model.live="months" class="input mt-1">
                <option value="6">Últimos 6 meses</option>
                <option value="12">Últimos 12 meses</option>
                <option value="24">Últimos 24 meses</option>
                <option value="36">Últimos 36 meses</option>
            </select>
        </label>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <article class="card"><p class="text-xs font-bold uppercase tracking-[0.14em] text-muted">Ingresos reales</p><p class="mt-3 text-2xl font-black text-ink">USD {{ number_format($report['totals']['income'], 2) }}</p><p class="mt-1 text-xs text-brand">Cobros y facturas pagadas</p></article>
        <article class="card"><p class="text-xs font-bold uppercase tracking-[0.14em] text-muted">Recurrentes</p><p class="mt-3 text-2xl font-black text-brand">USD {{ number_format($report['totals']['recurring'], 2) }}</p><p class="mt-1 text-xs text-muted">Pagos validados de clientes</p></article>
        <article class="card"><p class="text-xs font-bold uppercase tracking-[0.14em] text-muted">Facturas</p><p class="mt-3 text-2xl font-black text-cyan-700 dark:text-cyan-300">USD {{ number_format($report['totals']['invoices'], 2) }}</p><p class="mt-1 text-xs text-muted">Ingresos no recurrentes</p></article>
        <article class="card"><p class="text-xs font-bold uppercase tracking-[0.14em] text-muted">Egresos reales</p><p class="mt-3 text-2xl font-black text-orange-700 dark:text-orange-300">USD {{ number_format($report['totals']['expenses'], 2) }}</p><p class="mt-1 text-xs text-muted">Compromisos y gastos pagados</p></article>
        <article class="card"><p class="text-xs font-bold uppercase tracking-[0.14em] text-muted">Flujo neto</p><p class="mt-3 text-2xl font-black {{ $report['totals']['net'] >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-600 dark:text-red-300' }}">USD {{ number_format($report['totals']['net'], 2) }}</p><p class="mt-1 text-xs text-muted">Ingresos menos egresos</p></article>
    </div>

    <section class="surface overflow-hidden">
        <div class="flex flex-col gap-4 border-b border-line px-5 py-5 sm:px-6 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="section-title">Ingresos vs. egresos</h2>
                <p class="mt-1 text-sm text-muted">{{ $from->format('m/Y') }} a {{ $to->format('m/Y') }} · montos mostrados en USD.</p>
                @if ($currentExchangeRate)
                    <p class="mt-1 text-xs font-semibold text-brand">Egresos convertidos desde DOP con la tasa actual: DOP {{ number_format($currentExchangeRate->rate, 4) }} por USD, aplicada a todos los meses.</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs font-semibold text-muted">
                <span class="inline-flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-sm bg-brand"></i> Recurrentes</span>
                <span class="inline-flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-sm bg-cyan-500"></i> Facturas</span>
                <span class="inline-flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-sm bg-orange-400"></i> Egresos</span>
            </div>
        </div>
        <div class="overflow-x-auto px-5 pb-4 pt-6 sm:px-6">
            <svg viewBox="0 0 {{ $report['chart']['width'] }} {{ $report['chart']['height'] }}" height="250" role="img"
                aria-label="Gráfico de barras de ingresos y egresos por mes" class="block h-[250px] min-w-[720px] w-full"
                style="min-width: {{ $report['chart']['width'] }}px">
                <line x1="0" y1="{{ $report['chart']['baseline'] }}" x2="{{ $report['chart']['width'] }}" y2="{{ $report['chart']['baseline'] }}" stroke="#d9deea" stroke-width="1" />
                @foreach ($report['points'] as $point)
                    <g>
                        <title>{{ $point['label'] }}: ingresos USD {{ number_format($point['income'], 2) }} · egresos USD {{ number_format($point['expenses'], 2) }}</title>
                        <rect data-chart-bar="recurring" x="{{ $point['chart_income_x'] }}" y="{{ $point['chart_recurring_y'] }}" width="12" height="{{ $point['chart_recurring_height'] }}" rx="2" fill="#087d74" />
                        <rect data-chart-bar="invoices" x="{{ $point['chart_income_x'] }}" y="{{ $point['chart_invoice_y'] }}" width="12" height="{{ $point['chart_invoice_height'] }}" rx="2" fill="#06b6d4" />
                        <rect data-chart-bar="expenses" x="{{ $point['chart_expense_x'] }}" y="{{ $point['chart_expense_y'] }}" width="12" height="{{ $point['chart_expense_height'] }}" rx="2" fill="#fb923c" />
                        <text x="{{ $point['chart_label_x'] }}" y="224" text-anchor="middle" font-size="10" font-weight="700" fill="#687386">{{ $point['label'] }}</text>
                        <text x="{{ $point['chart_label_x'] }}" y="241" text-anchor="middle" font-size="9" font-weight="800" fill="{{ $point['net'] >= 0 ? '#087d74' : '#dc2626' }}">{{ $point['net'] >= 0 ? '+' : '' }}{{ number_format($point['net'] / 1000, 1) }}k</text>
                    </g>
                @endforeach
            </svg>
        </div>
    </section>

    @if ($report['unconverted_expenses_dop'] > 0)
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100">
            <p class="font-bold">No hay una tasa actual para convertir los egresos.</p>
            <p class="mt-1">DOP {{ number_format($report['unconverted_expenses_dop'], 2) }} no se incluyeron en el gráfico ni en los totales. Registra una tasa en Gestión de compromisos para mostrarlos en USD.</p>
        </div>
    @endif

    <section class="mt-6 surface overflow-hidden">
        <div class="border-b border-line px-5 py-5 sm:px-6"><h2 class="section-title">Detalle mensual</h2><p class="mt-1 text-sm text-muted">Solo se consideran movimientos efectivamente pagados o validados.</p></div>
        <div class="hidden overflow-x-auto md:block"><table class="table"><thead><tr><th>Mes</th><th>Recurrentes</th><th>Facturas</th><th>Compromisos</th><th>Gastos extraordinarios</th><th>Flujo neto</th></tr></thead><tbody>@foreach ($report['points'] as $point)<tr><td class="font-bold text-ink">{{ $point['label'] }}</td><td>USD {{ number_format($point['recurring'], 2) }}</td><td>USD {{ number_format($point['invoices'], 2) }}</td><td>USD {{ number_format($point['commitments'], 2) }}</td><td>USD {{ number_format($point['unplanned'], 2) }}</td><td class="font-bold {{ $point['net'] >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-600 dark:text-red-300' }}">USD {{ number_format($point['net'], 2) }}</td></tr>@endforeach</tbody></table></div>
        <div class="divide-y divide-line md:hidden">@foreach ($report['points'] as $point)<article class="p-4"><div class="flex items-center justify-between gap-3"><p class="font-bold text-ink">{{ $point['label'] }}</p><p class="font-bold {{ $point['net'] >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-600 dark:text-red-300' }}">USD {{ number_format($point['net'], 2) }}</p></div><div class="mt-3 grid grid-cols-2 gap-3 text-xs"><p class="text-muted">Recurrentes <span class="mt-1 block font-bold text-ink">USD {{ number_format($point['recurring'], 2) }}</span></p><p class="text-muted">Facturas <span class="mt-1 block font-bold text-ink">USD {{ number_format($point['invoices'], 2) }}</span></p><p class="text-muted">Compromisos <span class="mt-1 block font-bold text-ink">USD {{ number_format($point['commitments'], 2) }}</span></p><p class="text-muted">Extraordinarios <span class="mt-1 block font-bold text-ink">USD {{ number_format($point['unplanned'], 2) }}</span></p></div></article>@endforeach</div>
    </section>
</div>
