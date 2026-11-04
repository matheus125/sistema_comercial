@extends('layouts.app')
@section('content')
<h1>Alterar senha</h1>
<form class="panel grid" method="post" action="{{ route('password.change.store') }}">@csrf
    <label class="field">Senha atual<input name="current_password" type="password" required></label>
    <label class="field">Nova senha<input name="password" type="password" required></label>
    <label class="field">Confirmar senha<input name="password_confirmation" type="password" required></label>
    <button class="btn primary">Salvar senha</button>
</form>
@endsection
