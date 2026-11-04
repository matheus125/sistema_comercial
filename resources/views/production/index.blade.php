@extends('layouts.app')
@section('title','Produção')
@section('content')
<div class="between">
    <h1>Produção em tempo real</h1>
    <form class="row" method="get">
        <select name="sector">
            <option value="kitchen" @selected($sector==='kitchen')>Cozinha</option>
            <option value="bar" @selected($sector==='bar')>Bar</option>
            <option value="cashier" @selected($sector==='cashier')>Caixa</option>
            <option value="customer" @selected($sector==='customer')>Cliente</option>
        </select>
        <button class="btn blue">Filtrar setor</button>
    </form>
</div>

<div class="grid">
@forelse($orders as $order)
    <div class="panel">
        <div class="between">
            <div>
                <h2>Pedido {{ $order->number }}</h2>
                <p class="muted">
                    Tipo: {{ $order->operation_mode }} |
                    Mesa: {{ $order->table_number ?: '-' }} |
                    Comanda: {{ $order->command_code ?: '-' }} |
                    Cliente: {{ $order->customer_name ?: 'Não identificado' }}
                </p>
            </div>
            <span class="badge">{{ $order->service_status }}</span>
        </div>

        <table>
            <thead><tr><th>Item</th><th>Qtd</th><th>Status</th><th>Observação</th></tr></thead>
            <tbody>
            @foreach(($items[$order->id] ?? collect()) as $item)
                <tr><td>{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td>{{ $item->production_status }}</td><td>{{ $item->notes }}</td></tr>
            @endforeach
            </tbody>
        </table>

        <div class="row">
            <form method="post" action="{{ route('orders.status',$order->id) }}">@csrf<input type="hidden" name="service_status" value="preparing"><button class="btn ghost">Em preparo</button></form>
            <form method="post" action="{{ route('orders.status',$order->id) }}">@csrf<input type="hidden" name="service_status" value="ready"><button class="btn blue">Pronto</button></form>
            <form method="post" action="{{ route('orders.status',$order->id) }}">@csrf<input type="hidden" name="service_status" value="delivered"><button class="btn primary">Entregue</button></form>
            <form method="post" action="{{ route('orders.status',$order->id) }}">@csrf<input type="hidden" name="service_status" value="paid"><button class="btn primary">Pago</button></form>
            <a class="btn ghost" target="_blank" href="{{ route('orders.print',[$order->id,$sector]) }}">Imprimir setor</a>
            <a class="btn ghost" target="_blank" href="{{ route('orders.print',[$order->id,'customer']) }}">Comprovante cliente</a>
        </div>
    </div>
@empty
    <div class="panel">Nenhum pedido pendente para este setor.</div>
@endforelse
</div>
@endsection
