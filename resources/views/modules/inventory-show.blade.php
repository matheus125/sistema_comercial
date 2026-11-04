@extends('layouts.app')
@section('content')
<div class="between"><h1>Inventário #{{ $inventory->id }}</h1><span class="badge status-{{ $inventory->status }}">{{ $inventory->status }}</span></div>
<form method="post" action="{{ route('inventory.count',$inventory->id) }}">@csrf
<table><thead><tr><th>Produto</th><th>Sistema</th><th>Contado</th><th>Divergência</th><th>Ajustado</th></tr></thead><tbody>
@foreach($items as $item)<tr><td>{{ $item->product_name }}</td><td>{{ $item->system_quantity }}</td><td><input name="counted[{{ $item->id }}]" type="number" step="0.001" value="{{ $item->counted_quantity }}"></td><td>{{ $item->divergence }}</td><td>{{ $item->adjusted ? 'Sim' : 'Não' }}</td></tr>@endforeach
</tbody></table><br><div class="row"><button class="btn blue">Salvar contagem</button></form><form method="post" action="{{ route('inventory.apply',$inventory->id) }}">@csrf<button class="btn primary">Aplicar ajustes autorizados</button></form></div>
@endsection
