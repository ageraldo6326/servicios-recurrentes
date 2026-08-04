@extends('layouts.app')

@section('content')
    @include('partials.heading', ['heading' => $commitment->exists ? 'Editar compromiso financiero' : 'Nuevo compromiso financiero'])
    <form class="panel max-w-3xl space-y-4" method="post" action="{{ $commitment->exists ? route('financial-agenda.commitments.update', $commitment) : route('financial-agenda.commitments.store') }}" x-data="{ hasCutoff: {{ old('has_cutoff', $commitment->has_cutoff) ? 'true' : 'false' }} }">
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
        <label class="flex items-center gap-2 text-sm font-semibold text-ink">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $commitment->is_active))>
            Compromiso activo
        </label>
        <label class="block text-sm font-bold text-ink">Observaciones
            <textarea class="input min-h-28" name="observations">{{ old('observations', $commitment->observations) }}</textarea>
        </label>
        <div class="flex flex-col gap-3 sm:flex-row">
            <button class="button">Guardar compromiso</button>
            <a class="button-secondary" href="{{ route('financial-agenda.commitments.index') }}" wire:navigate>Cancelar</a>
        </div>
    </form>
@endsection
