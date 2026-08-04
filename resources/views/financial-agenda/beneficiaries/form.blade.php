@extends('layouts.app')

@section('content')
    @include('partials.heading', ['heading' => $beneficiary->exists ? 'Editar beneficiario' : 'Nuevo beneficiario'])
    <form class="panel max-w-2xl space-y-4" method="post" action="{{ $beneficiary->exists ? route('financial-agenda.beneficiaries.update', $beneficiary) : route('financial-agenda.beneficiaries.store') }}">
        @csrf
        @if($beneficiary->exists) @method('put') @endif
        <label class="block text-sm font-bold text-ink">Nombre
            <input class="input" name="name" value="{{ old('name', $beneficiary->name) }}" required>
        </label>
        <label class="block text-sm font-bold text-ink">Tipo
            <input class="input" name="type" value="{{ old('type', $beneficiary->type) }}" placeholder="Banco, telecomunicaciones, suscripción..." required>
        </label>
        <label class="flex items-center gap-2 text-sm font-semibold text-ink">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $beneficiary->is_active))>
            Beneficiario activo
        </label>
        <label class="block text-sm font-bold text-ink">Observaciones
            <textarea class="input min-h-28" name="observations">{{ old('observations', $beneficiary->observations) }}</textarea>
        </label>
        <div class="flex flex-col gap-3 sm:flex-row">
            <button class="button">Guardar beneficiario</button>
            <a class="button-secondary" href="{{ route('financial-agenda.beneficiaries.index') }}" wire:navigate>Cancelar</a>
        </div>
    </form>
@endsection
