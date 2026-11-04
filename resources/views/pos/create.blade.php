@extends('layouts.app')
@section('title','PDV')
@section('content')
<div class="between"><h1>Pedidos / PDV</h1><span class="badge {{ $cash ? 'status-open' : 'status-cancelled' }}">{{ $cash ? 'Caixa aberto' : 'Caixa fechado' }}</span></div>
@unless($cash)<div class="alert error">Abra um caixa antes de registrar pagamento.</div>@endunless
<form class="panel" method="post" action="{{ route('pos.store') }}">@csrf
    <div class="form-grid">
        <label class="field">Tipo do pedido<select name="order_type"><option value="table">Mesa</option><option value="individual_tab">Comanda individual</option><option value="counter">Balcão</option><option value="pickup">Retirada</option><option value="delivery">Delivery</option><option value="internal_consumption">Consumo interno</option></select></label>
        <label class="field">Mesa<select name="table_id"><option value="">Sem mesa</option>@foreach($tables as $table)<option value="{{ $table->id }}">Mesa {{ $table->number }} - {{ $table->status }}</option>@endforeach</select></label>
        <label class="field">Comanda<input name="command_code" placeholder="Número/nome da comanda"></label>
        <label class="field">Cliente<select name="customer_id"><option value="">Consumidor não identificado</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach</select></label>
        <label class="field">Pagamento<select name="payment_type"><option value="cash">Dinheiro</option><option value="pix">Pix</option><option value="debit_card">Cartão de débito</option><option value="credit_card">Cartão de crédito</option><option value="mixed">Pagamento misto</option><option value="credit">Fiado/Crediário</option></select></label>
        <label class="field">Valor recebido<input name="cash_received" type="number" step="0.01"></label>
        <label class="field">Taxa de entrega<input name="delivery_fee" type="number" step="0.01" value="0"></label>
    </div>
    <h2>Itens</h2>
    <div id="items" class="grid">
        <div class="form-grid item">
            <label class="field">Item do cardápio<select name="products[]">@foreach($products as $product)<option value="{{ $product->id }}" data-price="{{ $product->sale_price }}">{{ $product->internal_code }} - {{ $product->name }} | {{ $product->product_type }} | R$ {{ number_format($product->sale_price,2,',','.') }} | est. {{ $product->stock_current }}</option>@endforeach</select></label>
            <label class="field">Quantidade<input name="quantities[]" type="number" step="0.001" value="1"></label>
            <label class="field">Desconto item<input name="discounts[]" type="number" step="0.01" value="0"></label>
        </div>
    </div>
    <br><label class="field">Observações do pedido<textarea name="notes"></textarea></label>
    <br><div class="row"><button type="button" class="btn ghost" onclick="addItem()">Adicionar item</button><label class="field" style="max-width:260px">Desconto geral<input name="general_discount" type="number" step="0.01" value="0"></label><button class="btn primary" @disabled(!$cash)>Finalizar pedido</button></div>
</form>
<script>
function addItem(){const first=document.querySelector('.item');document.getElementById('items').appendChild(first.cloneNode(true));}
</script>
@endsection
