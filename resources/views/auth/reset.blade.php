@extends('layouts.app')
@section('content')
<div class="login"><form class="panel" method="post" action="{{ route('password.update') }}">@csrf
    <h1>Nova senha</h1>
    <input type="hidden" name="token" value="{{ $token }}"><input type="hidden" name="email" value="{{ $email }}">
    @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
    <div class="grid"><label class="field">Senha<input name="password" type="password" required></label><label class="field">Confirmar senha<input name="password_confirmation" type="password" required></label><button class="btn primary">Salvar</button></div>
</form></div>
@endsection
