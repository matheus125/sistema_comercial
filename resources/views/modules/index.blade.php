@extends('layouts.app')
@section('title',$config['title'])
@section('content')
<div class="between">
    <h1>{{ $config['title'] }}</h1>
    <a class="btn primary" href="{{ route('modules.create',$module) }}">Novo</a>
</div>
<form class="row" method="get"><input name="q" placeholder="Buscar..." value="{{ request('q') }}"><button class="btn blue">Buscar</button></form><br>
<table>
    <thead><tr><th>ID</th>@foreach($config['fields'] as $field => $label)<th>{{ $label }}</th>@endforeach<th>Ações</th></tr></thead>
    <tbody>
    @forelse($rows as $row)
        <tr>
            <td>{{ $row->id }}</td>
            @foreach($config['fields'] as $field => $label)<td>{{ Str::limit((string)($row->$field ?? ''), 60) }}</td>@endforeach
            <td class="row">
                <a class="btn ghost" href="{{ route('modules.edit',[$module,$row->id]) }}">Editar</a>
                <form method="post" action="{{ route('modules.destroy',[$module,$row->id]) }}" onsubmit="return confirm('Remover registro?')">@csrf @method('delete')<button class="btn danger">Excluir</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="{{ count($config['fields']) + 2 }}">Nenhum registro encontrado.</td></tr>
    @endforelse
    </tbody>
</table>
<br>{{ $rows->links() }}
@endsection
