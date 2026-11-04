@extends('layouts.app')
@section('title','Login')
@section('content')
<div class="login">
    <form class="panel" method="post" action="{{ route('login.store') }}">
        @csrf
        <h1>Comercial Pro</h1>
        <p class="muted">Acesse o sistema de pedidos, mesas, delivery, estoque, caixa e financeiro.</p>
        @if(session('status'))<div class="alert">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
        <div class="grid">
            <label class="field">E-mail<input name="email" type="email" required autofocus value="{{ old('email','admin@sistema.local') }}"></label>
            <label class="field">Senha<input name="password" type="password" required></label>
            <label class="row"><input style="width:auto" type="checkbox" name="remember"> Lembrar acesso</label>
            <button class="btn primary">Entrar</button>
            <a class="muted" href="{{ route('password.request') }}">Recuperar senha</a>
        </div>
    </form>
</div>
@endsection
