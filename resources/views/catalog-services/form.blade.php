@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-brand">Productos comerciales</p>
        <h1 class="text-2xl font-black tracking-tight text-ink sm:text-4xl">{{ $service->exists ? 'Editar servicio' : 'Nuevo servicio' }}</h1>
        <p class="mt-2 text-sm text-muted">{{ $service->exists ? 'Actualiza la disponibilidad del producto sin afectar las contrataciones existentes.' : 'Registra un producto que pueda utilizarse en nuevas contrataciones.' }}</p>
    </div>
    <form class="panel max-w-xl space-y-5" method="post" action="{{ $service->exists ? route('catalog-services.update', $service) : route('catalog-services.store') }}">
        @csrf
        @if($service->exists) @method('put') @endif
        <label class="block text-sm font-bold text-ink">Nombre del servicio<input class="input" name="name" value="{{ old('name', $service->name) }}" required></label>
        <label class="flex items-center gap-3 text-sm font-semibold text-ink"><input class="h-5 w-5 rounded border-line text-brand focus:ring-brand/20" type="checkbox" name="is_active" value="1" @checked(old('is_active', $service->is_active))> Disponible para nuevas contrataciones</label>
        <div class="flex flex-col gap-3 pt-2 sm:flex-row"><button class="button">Guardar servicio</button><a wire:navigate href="{{ route('catalog-services.index') }}" class="button-secondary">Cancelar</a></div>
    </form>
@endsection
