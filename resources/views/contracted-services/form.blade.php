@extends('layouts.app')

@section('content')
    @include('partials.heading', ['heading' => $service->exists ? 'Editar servicio contratado' : 'Nuevo servicio contratado'])

    <form class="panel grid max-w-4xl gap-4 md:grid-cols-2" method="post" action="{{ $service->exists ? route('contracted-services.update', $service) : route('contracted-services.store') }}">
        @csrf
        @if ($service->exists) @method('put') @endif

        <label>Cliente
            <select class="input" name="client_id" required>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}" @selected(old('client_id', $service->client_id) == $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
            @error('client_id') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
        </label>
        <label>Servicio
            <select class="input" name="catalog_service_id" required>
                @foreach ($catalogServices as $item)
                    <option value="{{ $item->id }}" @selected(old('catalog_service_id', $service->catalog_service_id) == $item->id)>{{ $item->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Proveedor
            <select class="input" name="provider_id" required>
                @foreach ($providers as $provider)
                    <option value="{{ $provider->id }}" @selected(old('provider_id', $service->provider_id) == $provider->id)>{{ $provider->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Precio<input class="input" type="number" step="0.01" name="price" value="{{ old('price', $service->price) }}" required></label>
        <label>Moneda precio<input class="input" name="price_currency" maxlength="3" value="{{ old('price_currency', $service->price_currency) }}" required></label>
        <label>Costo<input class="input" type="number" step="0.01" name="cost" value="{{ old('cost', $service->cost) }}" required></label>
        <label>Moneda costo<input class="input" name="cost_currency" maxlength="3" value="{{ old('cost_currency', $service->cost_currency) }}" required></label>
        <label>IP del servidor<input class="input" name="ip" value="{{ old('ip', $service->ip) }}" placeholder="203.0.113.10">@error('ip') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror</label>
        <label>Día de cobro<input class="input" type="number" min="1" max="31" name="billing_day" value="{{ old('billing_day', $service->billing_day) }}" required></label>
        <label>Inicio<input class="input" type="date" name="starts_at" value="{{ old('starts_at', $service->starts_at?->format('Y-m-d')) }}" required></label>
        <label>Estado<div class="input bg-slate-100">{{ $service->status?->value === 'cancelled' ? 'Cancelado' : 'Activo' }}</div></label>

        <fieldset class="md:col-span-2 rounded-2xl border border-line bg-surface-soft p-4 sm:p-5">
            <legend class="px-2 text-sm font-black text-ink">Monitoreo de actividad saliente</legend>
            <label class="flex items-start gap-3">
                <input type="hidden" name="call_monitoring_enabled" value="0">
                <input class="mt-1 rounded border-line text-brand focus:ring-brand" type="checkbox" name="call_monitoring_enabled" value="1" @checked(filter_var(old('call_monitoring_enabled', $service->call_monitoring_enabled), FILTER_VALIDATE_BOOLEAN))>
                <span><span class="block font-bold text-ink">Activar monitoreo para este servidor</span><span class="mt-1 block text-xs text-muted">La IP debe identificar un único servicio activo.</span></span>
            </label>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <label>Plataforma
                    <select class="input" name="call_monitoring_platform">
                        <option value="">Seleccionar</option>
                        <option value="vicidial" @selected(old('call_monitoring_platform', $service->call_monitoring_platform?->value) === 'vicidial')>VICIdial</option>
                        <option value="issabel" @selected(old('call_monitoring_platform', $service->call_monitoring_platform?->value) === 'issabel')>Issabel</option>
                    </select>
                    @error('call_monitoring_platform') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
                <label>Alerta sin marcado (horas)<input class="input" type="number" min="1" max="8760" name="inactivity_threshold_hours" value="{{ old('inactivity_threshold_hours', $service->inactivity_threshold_hours ?? 48) }}" required>@error('inactivity_threshold_hours') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror</label>
                <label>Alerta sin reporte (horas)<input class="input" type="number" min="1" max="8760" name="report_delay_threshold_hours" value="{{ old('report_delay_threshold_hours', $service->report_delay_threshold_hours ?? 36) }}" required>@error('report_delay_threshold_hours') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror</label>
            </div>
        </fieldset>

        <label class="md:col-span-2">Descripción<textarea class="input" name="observations">{{ old('observations', $service->observations) }}</textarea></label>
        <button class="button md:col-span-2">Guardar</button>
    </form>
@endsection
