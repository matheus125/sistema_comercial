<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Impressão {{ $sector }} - Pedido {{ $order->number }}</title>
    <style>
        body{font-family:Arial,sans-serif;margin:18px;color:#111}
        h1{font-size:22px;margin:0 0 8px}
        p{margin:4px 0;font-size:13px}
        table{width:100%;border-collapse:collapse;margin-top:12px}
        th,td{border-bottom:1px solid #ddd;padding:8px;text-align:left;font-size:13px}
        .no-print{margin-bottom:14px}
        @media print{.no-print{display:none}}
    </style>
</head>
<body>
    <button class="no-print" onclick="print()">Imprimir</button>
    <h1>{{ strtoupper($sector) }} - Pedido {{ $order->number }}</h1>
    <p>Tipo: {{ $order->operation_mode }}</p>
    <p>Mesa: {{ $order->table_id ?: '-' }} | Comanda: {{ $order->command_code ?: '-' }}</p>
    <p>Status: {{ $order->service_status }}</p>
    <p>Data: {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}</p>

    <table>
        <thead><tr><th>Item</th><th>Qtd</th><th>Obs.</th></tr></thead>
        <tbody>
        @foreach($items as $item)
            <tr><td>{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td>{{ $item->notes }}</td></tr>
        @endforeach
        </tbody>
    </table>

    @if($sector === 'customer')
        <h2>Total: R$ {{ number_format($order->total,2,',','.') }}</h2>
    @endif
</body>
</html>
