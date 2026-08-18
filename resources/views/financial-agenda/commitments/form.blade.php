@extends('layouts.app')

@section('content')
    @include('partials.heading', ['heading' => $commitment->exists ? 'Editar compromiso financiero' : 'Nuevo compromiso financiero'])
    <form class="panel max-w-3xl space-y-4" method="post" action="{{ $commitment->exists ? route('financial-agenda.commitments.update', $commitment) : route('financial-agenda.commitments.store') }}" x-data="{ hasCutoff: {{ old('has_cutoff', $commitment->has_cutoff) ? 'true' : 'false' }}, isCreditCard: {{ old('is_credit_card', $commitment->isCreditCard()) ? 'true' : 'false' }} }">
        @csrf
        @if($commitment->exists) @method('put') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm font-bold text-ink">Nombre
                <input class="input" name="name" value="{{ old('name', $commitment->name) }}" required>
            </label>
            <label class="block text-sm font-bold text-ink">Beneficiario
                <select class="input" name="beneficiary_id" required>
                    <option value="">Selecciona un beneficiario</option>
                    @foreach($beneficiaries as $beneficiary)
                        <option value="{{ $beneficiary->id }}" @selected(old('beneficiary_id', $commitment->beneficiary_id) == $beneficiary->id)>{{ $beneficiary->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-bold text-ink">Categoría
                <input class="input" name="category" value="{{ old('category', $commitment->category) }}" placeholder="Tarjeta, préstamo, internet..." required>
            </label>
            <label class="block text-sm font-bold text-ink">Frecuencia
                <select class="input" name="frequency" required>
                    <option value="monthly" @selected(old('frequency', $commitment->frequency?->value) === 'monthly')>Mensual</option>
                </select>
            </label>
            <label class="block text-sm font-bold text-ink">Monto sugerido
                <input class="input" type="number" name="suggested_amount" min="0" step="0.01" value="{{ old('suggested_amount', $commitment->suggested_amount) }}">
            </label>
            <label class="block text-sm font-bold text-ink">Día límite de pago
                <input class="input" type="number" name="due_day" min="1" max="31" value="{{ old('due_day', $commitment->due_day) }}" required>
            </label>
            <label class="block text-sm font-bold text-ink">Días de anticipación
                <input class="input" type="number" name="activation_days_before_due" min="0" max="365" value="{{ old('activation_days_before_due', $commitment->activation_days_before_due ?? 15) }}">
                <span class="mt-1 block text-xs font-normal text-muted">Se usa para activar compromisos sin fecha de corte.</span>
            </label>
        </div>
        <div class="rounded-xl border border-line bg-surface-soft p-4">
            <label class="flex items-center gap-2 text-sm font-bold text-ink">
                <input type="hidden" name="has_cutoff" value="0">
                <input type="checkbox" name="has_cutoff" value="1" x-model="hasCutoff">
                Tiene fecha de corte
            </label>
            <label class="mt-3 block max-w-xs text-sm font-bold text-ink" x-show="hasCutoff" x-cloak>Día de corte
                <input class="input" type="number" name="cutoff_day" min="1" max="31" value="{{ old('cutoff_day', $commitment->cutoff_day) }}" :required="hasCutoff">
            </label>
        </div>
        <div class="rounded-xl border border-brand/30 bg-brand/5 p-4">
            <label class="flex items-center gap-2 text-sm font-bold text-ink"><input type="hidden" name="is_credit_card" value="0"><input type="checkbox" name="is_credit_card" value="1" x-model="isCreditCard"> Esta obligación es una tarjeta de crédito</label>
            <div x-show="isCreditCard" x-cloak class="mt-4 grid gap-4 border-t border-brand/20 pt-4 sm:grid-cols-2">
                <label class="text-sm font-bold text-ink">Margen de seguridad (días)<input class="input" type="number" name="payment_safety_days" min="0" max="31" value="{{ old('payment_safety_days', $commitment->payment_safety_days ?? 2) }}"></label>
                <label class="text-sm font-bold text-ink">Moneda<input class="input uppercase" name="card_currency" maxlength="3" value="{{ old('card_currency', $commitment->card_currency ?? 'DOP') }}"></label>
                <label class="text-sm font-bold text-ink">Límite de crédito<input class="input" type="number" name="credit_limit" min="0" step="0.01" value="{{ old('credit_limit', $commitment->credit_limit) }}"></label>
                <label class="text-sm font-bold text-ink">Balance actual<input class="input" type="number" name="current_balance" min="0" step="0.01" value="{{ old('current_balance', $commitment->current_balance) }}"><span class="mt-1 block text-xs font-normal text-muted">Se actualiza manualmente; no registra consumos individuales.</span></label>
                <label class="text-sm font-bold text-ink">Saldo al corte<input class="input" type="number" name="statement_balance" min="0" step="0.01" value="{{ old('statement_balance', $commitment->statement_balance) }}"><span class="mt-1 block text-xs font-normal text-muted">Este es el monto que se prioriza para pago.</span></label>
                <label class="text-sm font-bold text-ink">Alertas de corte<input class="input" name="cutoff_alert_days" value="{{ old('cutoff_alert_days', $commitment->cutoff_alert_days ?? '7,3,1') }}"><span class="mt-1 block text-xs font-normal text-muted">Días separados por comas.</span></label>
                <label class="text-sm font-bold text-ink">Alertas de pago<input class="input" name="payment_alert_days" value="{{ old('payment_alert_days', $commitment->payment_alert_days ?? '7,3,1') }}"></label>
                <label class="text-sm font-bold text-ink">Ventana excelente hasta día<input class="input" type="number" name="purchase_excellent_days" min="1" max="30" value="{{ old('purchase_excellent_days', $commitment->purchase_excellent_days ?? 7) }}"></label>
                <label class="text-sm font-bold text-ink">Ventana buena hasta día<input class="input" type="number" name="purchase_good_days" min="1" max="30" value="{{ old('purchase_good_days', $commitment->purchase_good_days ?? 15) }}"></label>
                <label class="text-sm font-bold text-ink">Ventana regular hasta día<input class="input" type="number" name="purchase_regular_days" min="1" max="30" value="{{ old('purchase_regular_days', $commitment->purchase_regular_days ?? 22) }}"></label>
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm font-semibold text-ink">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $commitment->is_active)) @disabled($commitment->cancelled_at !== null)>
            Compromiso activo
        </label>
        @if($commitment->cancelled_at)
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200">
                <p class="font-black">Compromiso cancelado el {{ $commitment->cancelled_at->format('d/m/Y') }}</p>
                <p class="mt-1">{{ $commitment->cancellation_reason }}</p>
            </div>
        @endif
        <label class="block text-sm font-bold text-ink">Observaciones
            <textarea class="input min-h-28" name="observations">{{ old('observations', $commitment->observations) }}</textarea>
        </label>
        <div class="flex flex-col gap-3 sm:flex-row">
            <button class="button">Guardar compromiso</button>
            <a class="button-secondary" href="{{ route('financial-agenda.commitments.index') }}" wire:navigate>Cancelar</a>
        </div>
    </form>
    @if($commitment->exists && $commitment->is_active)
        <div class="panel mt-5 max-w-3xl border-red-200 dark:border-red-900" x-data="{ showCancel: false }">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-black text-red-700 dark:text-red-300">¿Ya no existe esta obligación?</p>
                    <p class="mt-1 text-xs text-muted">Cancela el compromiso sin borrar sus pagos ni su historial.</p>
                </div>
                <button type="button" class="button-secondary border-red-300 text-red-700 dark:border-red-800 dark:text-red-300" x-on:click="showCancel = !showCancel">
                    Cancelar compromiso
                </button>
            </div>
            <form x-show="showCancel" x-cloak class="mt-4 space-y-3 border-t border-line pt-4" method="post" action="{{ route('financial-agenda.commitments.cancel', $commitment) }}" x-on:submit="return confirm('¿Confirmas la cancelación? Esta acción conserva el historial, pero detiene nuevas obligaciones.')">
                @csrf
                <label class="block text-sm font-bold text-ink">Razón de cancelación
                    <textarea class="input min-h-24" name="cancellation_reason" required>{{ old('cancellation_reason') }}</textarea>
                    @error('cancellation_reason')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                </label>
                <button class="button bg-red-600 text-white hover:bg-red-700" type="submit">Confirmar cancelación</button>
            </form>
        </div>
    @endif
@endsection
