@extends('layouts.app')
@section('content')
<h1>Inventário</h1>
<form class="panel row" method="post" action="{{ route('inventory.open') }}">@csrf
    <input name="observations" placeholder="Observações do inventário">
    <button class="btn primary">Abrir inventário</button>
</form><br>
<table><thead><tr><th>ID</th><th>Status</th><th>Responsável</th><th>Abertura</th><th>Fechamento</th><th>Ações</th></tr></thead><tbody>
@foreach($inventories as $i)<tr><td>#{{ $i->id }}</td><td><span class="badge status-{{ $i->status }}">{{ $i->status }}</span></td><td>{{ $i->user_name }}</td><td>{{ $i->opened_at }}</td><td>{{ $i->closed_at }}</td><td><a class="btn ghost" href="{{ route('inventory.show',$i->id) }}">Conferir</a></td></tr>@endforeach
</tbody></table><br>{{ $inventories->links() }}
@endsection
