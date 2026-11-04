@extends('layouts.app')
@section('content')
<h1>Estoque</h1>
<form class="panel form-grid" method="post" action="{{ route('stock.adjust') }}">@csrf
    <label class="field">Produto<select name="product_id">@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }} | estoque {{ $product->stock_current }}</option>@endforeach</select></label>
    <label class="field">Quantidade movimentada<input name="quantity" type="number" step="0.001" required></label>
    <label class="field">Tipo<select name="type"><option value="manual_adjustment">Ajuste manual</option><option value="loss">Perda/avaria</option><option value="return">Devolução</option><option value="transfer">Transferência</option><option value="inventory">Inventário</option></select></label>
    <label class="field" style="grid-column:1/-1">Motivo<textarea name="reason"></textarea></label>
    <button class="btn primary">Registrar movimentação</button>
</form><br>
<table><thead><tr><th>Data</th><th>Produto</th><th>Tipo</th><th>Anterior</th><th>Mov.</th><th>Final</th><th>Usuário</th><th>Motivo</th></tr></thead><tbody>
@foreach($movements as $m)<tr><td>{{ \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i') }}</td><td>{{ $m->product_name }}</td><td>{{ $m->type }}</td><td>{{ $m->quantity_before }}</td><td>{{ $m->quantity_moved }}</td><td>{{ $m->quantity_after }}</td><td>{{ $m->user_name }}</td><td>{{ $m->reason }}</td></tr>@endforeach
</tbody></table><br>{{ $movements->links() }}
@endsection
