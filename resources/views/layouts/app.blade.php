<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema Comercial')</title>
    <style>
        :root{--bg:#f6f7f9;--panel:#fff;--text:#1f2937;--muted:#667085;--line:#d8dde6;--brand:#0f766e;--brand2:#2563eb;--danger:#b42318;--warn:#b54708;--ok:#067647}
        *{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:Inter,system-ui,-apple-system,Segoe UI,sans-serif;font-size:14px}a{color:inherit;text-decoration:none}
        .shell{display:grid;grid-template-columns:260px 1fr;min-height:100vh}.side{background:#101828;color:#fff;padding:18px;position:sticky;top:0;height:100vh;overflow:auto}.brand{font-weight:800;font-size:20px;margin-bottom:18px}.nav{display:grid;gap:4px}.nav a{padding:10px 12px;border-radius:6px;color:#d0d5dd}.nav a:hover,.nav a.active{background:#1d2939;color:#fff}
        .main{min-width:0}.top{height:64px;background:#fff;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 24px;position:sticky;top:0;z-index:5}.content{padding:24px;max-width:1480px;margin:auto}
        h1{font-size:28px;margin:0 0 18px}.grid{display:grid;gap:16px}.cards{grid-template-columns:repeat(4,minmax(0,1fr))}.card,.panel{background:var(--panel);border:1px solid var(--line);border-radius:8px;padding:16px}.metric{font-size:24px;font-weight:800;margin-top:8px}.muted{color:var(--muted)}.row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}.between{display:flex;justify-content:space-between;gap:12px;align-items:center}
        table{width:100%;border-collapse:collapse;background:#fff;border:1px solid var(--line);border-radius:8px;overflow:hidden}th,td{text-align:left;padding:11px;border-bottom:1px solid var(--line);vertical-align:top}th{background:#f2f4f7;font-size:12px;text-transform:uppercase;color:#475467}tr:last-child td{border-bottom:0}
        input,select,textarea{width:100%;border:1px solid var(--line);border-radius:6px;padding:10px;background:#fff;color:var(--text)}textarea{min-height:90px}.field{display:grid;gap:6px}.form-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:6px;padding:10px 13px;background:#e5e7eb;color:#111827;font-weight:700;cursor:pointer}.btn.primary{background:var(--brand);color:#fff}.btn.blue{background:var(--brand2);color:#fff}.btn.danger{background:var(--danger);color:#fff}.btn.ghost{background:#fff;border:1px solid var(--line)}.badge{display:inline-flex;border-radius:999px;padding:4px 8px;font-size:12px;background:#eef2ff;color:#3538cd}.status-open{background:#ecfdf3;color:var(--ok)}.status-closed,.status-cancelled{background:#fef3f2;color:var(--danger)}
        .alert{padding:12px 14px;border-radius:6px;margin-bottom:14px;background:#ecfdf3;color:var(--ok);border:1px solid #abefc6}.error{background:#fef3f2;color:var(--danger);border-color:#fecdca}.login{min-height:100vh;display:grid;place-items:center;padding:24px}.login .panel{width:min(420px,100%)}
        @media(max-width:1000px){.shell{grid-template-columns:1fr}.side{position:relative;height:auto}.cards{grid-template-columns:repeat(2,1fr)}.form-grid{grid-template-columns:1fr 1fr}.top{position:relative}}@media(max-width:640px){.content{padding:14px}.cards,.form-grid{grid-template-columns:1fr}.between{align-items:stretch;flex-direction:column}.row>*{flex:1 1 100%}table{display:block;overflow:auto;white-space:nowrap}}
        @media print{.side,.top,.no-print{display:none}.shell{display:block}.content{padding:0}.panel,table{border:0}}
    </style>
</head>
<body>
@auth
<div class="shell">
    <aside class="side">
        <div class="brand">Comercial Pro</div>
        <nav class="nav">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('pos.create') }}">PDV</a>
            <a href="{{ route('production.index') }}">Produção</a>
            <a href="{{ route('cash.index') }}">Caixa</a>
            <a href="{{ route('modules.index','products') }}">Produtos/Cardápio</a>
            <a href="{{ route('modules.index','recipes') }}">Fichas técnicas</a>
            <a href="{{ route('modules.index','recipe-items') }}">Ingredientes da ficha</a>
            <a href="{{ route('modules.index','tables') }}">Mesas/Comandas</a>
            <a href="{{ route('modules.index','categories') }}">Categorias</a>
            <a href="{{ route('modules.index','brands') }}">Marcas</a>
            <a href="{{ route('modules.index','suppliers') }}">Fornecedores</a>
            <a href="{{ route('modules.index','customers') }}">Clientes</a>
            <a href="{{ route('stock.movements') }}">Estoque</a>
            <a href="{{ route('inventory.index') }}">Inventário</a>
            <a href="{{ route('purchases.index') }}">Compras</a>
            <a href="{{ route('modules.index','accounts-payable') }}">Contas a pagar</a>
            <a href="{{ route('modules.index','accounts-receivable') }}">Contas a receber</a>
            <a href="{{ route('reports.index') }}">Relatórios</a>
            <a href="{{ route('modules.index','users') }}">Usuários</a>
            <a href="{{ route('modules.index','branches') }}">Filiais</a>
            <a href="{{ route('backups.index') }}">Backups</a>
            <a href="{{ route('settings.edit') }}">Configurações</a>
        </nav>
    </aside>
    <main class="main">
        <header class="top">
            <div><strong>{{ auth()->user()->name }}</strong><div class="muted">{{ auth()->user()->email }}</div></div>
            <div class="row">
                <a class="btn ghost" href="{{ route('password.change') }}">Senha</a>
                <form method="post" action="{{ route('logout') }}">@csrf<button class="btn danger">Sair</button></form>
            </div>
        </header>
        <section class="content">
            @if(session('status'))<div class="alert">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
            @yield('content')
        </section>
    </main>
</div>
@else
    @yield('content')
@endauth
</body>
</html>
