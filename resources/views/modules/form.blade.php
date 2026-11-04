@extends('layouts.app')
@section('title',$config['title'])
@section('content')
<h1>{{ $row ? 'Editar' : 'Novo' }} - {{ $config['title'] }}</h1>
<form class="panel" method="post" action="{{ $row ? route('modules.update',[$module,$row->id]) : route('modules.store',$module) }}">
    @csrf @if($row) @method('put') @endif
    <div class="form-grid">
    @foreach($config['fields'] as $field => $label)
        <label class="field">{{ $label }}
            @if(isset($options[$field]))
                <select name="{{ $field }}"><option value="">Selecione</option>@foreach($options[$field] as $id => $name)<option value="{{ $id }}" @selected(($row->$field ?? '') == $id)>{{ $name }}</option>@endforeach</select>
            @elseif(Str::contains($field,'date') || Str::endsWith($field,'_at'))
                <input type="date" name="{{ $field }}" value="{{ old($field, isset($row->$field) && $row->$field ? \Carbon\Carbon::parse($row->$field)->format('Y-m-d') : '') }}">
            @elseif($field === 'observations' || $field === 'address')
                <textarea name="{{ $field }}">{{ old($field,$row->$field ?? '') }}</textarea>
            @elseif($field === 'password')
                <input type="password" name="{{ $field }}" placeholder="{{ $row ? 'Deixe em branco para manter' : '' }}">
            @elseif($field === 'status')
                <select name="status"><option value="active" @selected(($row->status ?? 'active')==='active')>Ativo</option><option value="inactive" @selected(($row->status ?? '')==='inactive')>Inativo</option><option value="open" @selected(($row->status ?? '')==='open')>Aberto</option><option value="paid" @selected(($row->status ?? '')==='paid')>Pago</option><option value="cancelled" @selected(($row->status ?? '')==='cancelled')>Cancelado</option></select>
            @else
                <input name="{{ $field }}" value="{{ old($field,$row->$field ?? '') }}">
            @endif
        </label>
    @endforeach
    </div>
    <br><div class="row"><button class="btn primary">Salvar</button><a class="btn ghost" href="{{ route('modules.index',$module) }}">Voltar</a></div>
</form>
@endsection
