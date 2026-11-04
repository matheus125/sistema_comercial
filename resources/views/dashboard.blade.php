@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="between">
    <h1>Dashboard</h1>
    <form class="row" method="get">
        <input type="date" name="from" value="{{ $from->toDateString() }}">
        <input type="date" name="to" value="{{ $to->toDateString() }}">
        <button class="btn blue">Filtrar</button>
    </form>
</div>
<div class="grid cards">
@foreach($cards as $label => $value)
    <div class="card"><div class="muted">{{ $label }}</div><div class="metric">{{ is_numeric($value) ? 'R$ '.number_format($value,2,',','.') : $value }}</div></div>
@endforeach
    <div class="card"><div class="muted">Produtos em estoque baixo</div><div class="metric">{{ $lowStock }}</div></div>
    <div class="card"><div class="muted">Produtos sem estoque</div><div class="metric">{{ $zeroStock }}</div></div>
    <div class="card"><div class="muted">Próximos do vencimento</div><div class="metric">{{ $expiring }}</div></div>
</div>
<br>
<div class="grid" style="grid-template-columns:1fr 1fr">
    <div class="panel">
        <h2>Produtos mais vendidos</h2>
        <table><thead><tr><th>Produto</th><th>Qtd</th><th>Total</th></tr></thead><tbody>
        @forelse($topProducts as $item)<tr><td>{{ $item->product_name }}</td><td>{{ number_format($item->quantity,3,',','.') }}</td><td>R$ {{ number_format($item->total,2,',','.') }}</td></tr>@empty<tr><td colspan="3">Sem pedidos no período.</td></tr>@endforelse
        </tbody></table>
    </div>
    <div class="panel">
        <h2>Movimentações recentes</h2>
        <table><thead><tr><th>Ação</th><th>Usuário</th><th>Data</th></tr></thead><tbody>
        @forelse($recent as $log)<tr><td>{{ $log->description ?: $log->action }}</td><td>{{ $log->user_name }}</td><td>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}</td></tr>@empty<tr><td colspan="3">Nenhum log.</td></tr>@endforelse
        </tbody></table>
    </div>
</div>
@endsection
