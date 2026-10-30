@extends('layouts.app')

@section('content')
    @include('partials.heading', ['heading' => 'Pagos recibidos', 'action' => route('payments.create')])
    @include('partials.list-search', ['placeholder' => 'Buscar por cliente, servicio, moneda o estado', 'value' => $search])

    <div class="surface overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Fecha</th><th>Cliente / servicio contratado</th><th>Monto</th><th>Estado</th><th>Cobros aplicados</th><th></th></tr></thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $payment->received_at->format('d/m/Y') }}</td>
                            <td>
                                @forelse ($payment->charges as $charge)
                                    <div @class(['border-t border-line pt-3 mt-3' => ! $loop->first])>
                                        @include('partials.contracted-service-identity', ['service' => $charge->contractedService, 'showClient' => true])
                                        <div class="mt-2 flex flex-col gap-1 text-xs sm:flex-row sm:flex-wrap sm:gap-x-4">
                                            <p class="text-muted">Período pagado: <span class="font-bold text-ink">cobro con vencimiento {{ $charge->due_date->format('d/m/Y') }}</span></p>
                                            <p class="text-muted">Monto aplicado: <span class="font-bold text-ink">{{ $charge->pivot->currency }} {{ number_format($charge->pivot->amount, 2) }}</span></p>
                                        </div>
                                    </div>
                                @empty
                                    <p class="font-semibold text-amber-700 dark:text-amber-300">Sin servicio ni período asignado</p>
                                    <p class="mt-1 text-xs text-muted">Este pago aún no está imputado a un cobro específico.</p>
                                @endforelse
                            </td>
                            <td class="font-bold text-ink">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                            <td>
                                <p class="font-semibold text-ink">{{ match ($payment->status->value) { 'pending' => 'Pendiente', 'validated' => 'Validado', 'rejected' => 'Anulado' } }}</p>
                                @if ($payment->status->value === 'rejected')
                                    <p class="mt-1 max-w-xs text-xs text-red-700 dark:text-red-300">{{ $payment->rejection_reason }}</p>
                                    <p class="mt-1 text-[11px] text-muted">{{ $payment->rejected_at?->format('d/m/Y H:i') }}{{ $payment->rejectedBy ? ' · '.$payment->rejectedBy->name : '' }}</p>
                                @endif
                            </td>
                            <td>{{ $payment->charges->count() }}</td>
                            <td>
                                @if ($payment->status->value === 'pending')
                                    <form method="post" action="{{ route('payments.validate', $payment) }}">@csrf<button class="font-bold text-emerald-700 dark:text-emerald-300">Validar</button></form>
                                @elseif ($payment->status->value === 'validated')
                                    <details class="min-w-56">
                                        <summary class="cursor-pointer font-bold text-red-600">Anular pago</summary>
                                        <form method="post" action="{{ route('payments.reject', $payment) }}" class="mt-2 space-y-2">@csrf
                                            <label class="block text-xs font-bold text-muted" for="rejection_reason_{{ $payment->id }}">Motivo obligatorio</label>
                                            <textarea id="rejection_reason_{{ $payment->id }}" name="rejection_reason" class="input mt-0 min-h-20" maxlength="1000" required></textarea>
                                            <button class="text-sm font-bold text-red-600 hover:underline" onclick="return confirm('¿Confirmas que deseas anular este pago? El cobro volverá a quedar pendiente.')">Confirmar anulación</button>
                                        </form>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">No hay pagos que coincidan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">{{ $payments->links() }}</div>
    </div>
@endsection
