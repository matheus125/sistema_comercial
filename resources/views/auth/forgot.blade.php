@extends('layouts.app')
@section('content')
<div class="login"><form class="panel" method="post" action="{{ route('password.email') }}">@csrf
    <h1>Recuperar senha</h1>
    @if(session('status'))<div class="alert">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
    <label class="field">E-mail<input name="email" type="email" required></label><br>
    <button class="btn primary">Gerar link</button>
    <a class="btn ghost" href="{{ route('login') }}">Voltar</a>
</form></div>
@endsection
