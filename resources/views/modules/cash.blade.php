@extends('layouts.app')
@section('content')
<h1>Caixa</h1>
<div class="grid" style="grid-template-columns:1fr 1fr">
<form class="panel grid" method="post" action="{{ route('cash.open') }}">@csrf
    <h2>Abrir caixa</h2><label class="field">Valor inicial<input name="opening_amount" type="number" step="0.01" required></label><label class="field">Observações<textarea name="notes"></textarea></label><button class="btn primary">Abrir</button>
</form>
<form class="panel grid" method="post" action="{{ route('cash.movement') }}">@csrf
    <h2>Suprimento/Sangria/Despesa</h2><label class="field">Caixa<select name="cash_register_id">@foreach($registers as $r)<option value="{{ $r->id }}">#{{ $r->id }} - {{ $r->user_name }} - {{ $r->status }}</option>@endforeach</select></label><label class="field">Tipo<select name="type"><option value="supply">Suprimento</option><option value="withdrawal">Sangria</option><option value="expense">Despesa</option><option value="receivable">Recebimento fiado</option></select></label><label class="field">Valor<input name="amount" type="number" step="0.01"></label><label class="field">Descrição<input name="description"></label><button class="btn blue">Registrar</button>
</form>
</div><br>
<table><thead><tr><th>ID</th><th>Usuário</th><th>Status</th><th>Abertura</th><th>Esperado</th><th>Informado</th><th>Diferença</th><th>Fechar</th></tr></thead><tbody>
@foreach($registers as $r)<tr><td>#{{ $r->id }}</td><td>{{ $r->user_name }}</td><td><span class="badge status-{{ $r->status }}">{{ $r->status }}</span></td><td>R$ {{ number_format($r->opening_amount,2,',','.') }}</td><td>R$ {{ number_format($r->expected_amount,2,',','.') }}</td><td>{{ $r->informed_amount }}</td><td>{{ $r->difference_amount }}</td><td>@if($r->status==='open')<form class="row" method="post" action="{{ route('cash.close',$r->id) }}">@csrf<input name="informed_amount" type="number" step="0.01" min="0" required placeholder="Valor conferido" value="{{ number_format((float) $r->expected_amount, 2, '.', '') }}"><button class="btn danger">Fechar</button></form>@endif</td></tr>@endforeach
</tbody></table><br>{{ $registers->links() }}
@endsection
