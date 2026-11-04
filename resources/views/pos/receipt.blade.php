@extends('layouts.app')
@section('title','Comprovante')
@section('content')
<div class="between no-print"><h1>Comprovante {{ $sale->number }}</h1><button class="btn primary" onclick="print()">Imprimir/PDF</button></div>
<div class="panel">
    <h2>Restaurante Pro</h2>
    <p>Pedido: {{ $sale->number }}<br>Tipo: {{ $sale->operation_mode ?? $sale->order_type }}<br>Status: {{ $sale->service_status ?? $sale->status }}<br>Comanda: {{ $sale->command_code ?: '-' }}<br>Data: {{ \Carbon\Carbon::parse($sale->created_at)->format('d/m/Y H:i') }}<br>Pagamento: {{ $sale->payment_type }}</p>
    <table><thead><tr><th>Produto</th><th>Qtd</th><th>Unit.</th><th>Desc.</th><th>Total</th></tr></thead><tbody>
        @foreach($items as $item)<tr><td>{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td>R$ {{ number_format($item->unit_price,2,',','.') }}</td><td>R$ {{ number_format($item->discount,2,',','.') }}</td><td>R$ {{ number_format($item->total,2,',','.') }}</td></tr>@endforeach
    </tbody></table>
    @if($sale->delivery_fee > 0)<p>Taxa de entrega: R$ {{ number_format($sale->delivery_fee,2,',','.') }}</p>@endif
    <h2>Total: R$ {{ number_format($sale->total,2,',','.') }}</h2>
    @if($sale->change_amount > 0)<p>Troco: R$ {{ number_format($sale->change_amount,2,',','.') }}</p>@endif
</div>
<br>
<form class="no-print row" method="post" action="{{ route('sales.cancel',$sale->id) }}">@csrf<input name="reason" placeholder="Justificativa para cancelamento"><button class="btn danger">Cancelar pedido</button></form>
@endsection
