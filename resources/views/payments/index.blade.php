@extends('layouts.app')

@section('content')
    @include('partials.heading', ['heading' => 'Pagos recibidos', 'action' => route('payments.create')])
    @include('partials.list-search', ['placeholder' => 'Buscar por moneda o estado', 'value' => $search])

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
                            <td>{{ $payment->status->value }}</td>
                            <td>{{ $payment->charges->count() }}</td>
                            <td>@if ($payment->status->value === 'pending')<form method="post" action="{{ route('payments.validate', $payment) }}">@csrf<button class="font-bold text-emerald-700 dark:text-emerald-300">Validar</button></form>@endif</td>
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
