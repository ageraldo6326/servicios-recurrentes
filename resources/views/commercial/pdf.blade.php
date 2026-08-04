<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $type }} {{ $document->number }}</title>
    <style>
        body{font-family:Arial,sans-serif;color:#0e1b31;margin:40px}
        header{display:flex;align-items:flex-start;justify-content:space-between;gap:32px;border-bottom:2px solid #087d74;padding-bottom:20px}
        .company{display:grid;grid-template-columns:minmax(220px,1fr) minmax(240px,1fr);gap:36px;flex:1;font-size:15px;line-height:1.55}
        .company strong{font-size:20px}.company p{margin:0}.logo{display:flex;min-width:120px;max-width:180px;justify-content:flex-end}.logo img{max-height:90px;max-width:160px;object-fit:contain}
        .document-meta{text-align:right;min-width:150px}.document-meta h1{margin:0 0 6px}.document-meta p{margin:4px 0}
        table{width:100%;border-collapse:collapse;margin-top:30px}th,td{padding:10px;border-bottom:1px solid #d9deea;text-align:left}th{background:#f0f4ff;font-size:12px;text-transform:uppercase}.total{margin-left:auto;width:260px;margin-top:20px}.total p{margin:6px 0}.actions{margin-top:30px}@media print{.actions{display:none}}
        @media(max-width:700px){body{margin:20px}.company{grid-template-columns:1fr;gap:8px}.logo{justify-content:flex-start}.document-meta{text-align:left}}
    </style>
</head>
<body>
    <header>
        <div class="company">
            <div>
                <strong>{{ $company?->company_name ?: 'ServiceManager' }}</strong>
                @if($company?->website)<p>{{ $company->website }}</p>@endif
                @if($company?->email)<p>{{ $company->email }}</p>@endif
                @if($company?->phone)<p>{{ $company->phone }}</p>@endif
            </div>
            <div>
                @if($company?->address)<p>{{ $company->address }}</p>@endif
                @if($company?->city || $company?->province || $company?->postal_code)<p>{{ collect([$company?->city, $company?->province, $company?->postal_code])->filter()->join(', ') }}</p>@endif
                @if($company?->country)<p>{{ $company->country }}</p>@endif
                @if($company?->tax_id)<p>RNC / ID fiscal: {{ $company->tax_id }}</p>@endif
            </div>
        </div>
        @if($company?->logo_path)<div class="logo"><img src="{{ route('settings.company.logo') }}" alt="Logo de {{ $company->company_name }}"></div>@endif
        <div class="document-meta"><h1>{{ $type }}</h1><p>{{ $document->number }}</p><p>{{ $document->issue_date->format('d/m/Y') }}</p></div>
    </header>
    <section style="margin-top:25px"><strong>Cliente</strong><p>{{ $document->client->name }}</p><p>{{ $document->client->commercial_email ?? $document->client->phone }}</p></section>
    <table><thead><tr><th>Concepto</th><th>Descripción</th><th>Cant.</th><th>Precio</th><th>Total</th></tr></thead><tbody>@foreach($document->items as $item)<tr><td>{{ $item->concept }}</td><td>{{ $item->description }}</td><td>{{ $item->quantity }} {{ $item->unit }}</td><td>{{ $document->currency }} {{ number_format($item->unit_price,2) }}</td><td>{{ $document->currency }} {{ number_format($item->line_subtotal + $item->line_tax,2) }}</td></tr>@endforeach</tbody></table>
    <div class="total"><p>Subtotal: {{ $document->currency }} {{ number_format($document->subtotal,2) }}</p><p>Descuento: {{ $document->currency }} {{ number_format($document->discount,2) }}</p><p>Impuestos: {{ $document->currency }} {{ number_format($document->tax_total,2) }}</p><h2>Total: {{ $document->currency }} {{ number_format($document->total,2) }}</h2></div>
    @if($document->notes)<p style="margin-top:40px"><strong>Notas</strong><br>{{ $document->notes }}</p>@endif
    <div class="actions"><button onclick="window.print()">Imprimir / Guardar PDF</button></div>
</body>
</html>
