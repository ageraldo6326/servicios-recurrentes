<div class="min-w-56">
    @if ($showClient ?? false)
        <p class="font-bold text-ink">{{ $service->client->name }}</p>
    @endif

    <p class="font-semibold text-ink">
        {{ $service->catalogService->name }}
        <span class="font-normal text-muted">— {{ filled($service->observations) ? $service->observations : 'Sin descripción' }}</span>
    </p>
    <p class="mt-1 font-mono text-xs text-brand">IP: {{ $service->ip ?: 'Sin IP registrada' }}</p>
</div>
