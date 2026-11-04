@extends('layouts.app')
@section('content')
<div class="between"><h1>Backups</h1><form method="post" action="{{ route('backups.store') }}">@csrf<button class="btn primary">Backup manual</button></form></div>
<table><thead><tr><th>ID</th><th>Arquivo</th><th>Tamanho</th><th>Status</th><th>Data</th><th>Ações</th></tr></thead><tbody>
@foreach($backups as $b)<tr><td>#{{ $b->id }}</td><td>{{ $b->file_path }}</td><td>{{ number_format($b->file_size/1024,1,',','.') }} KB</td><td>{{ $b->status }}</td><td>{{ $b->created_at }}</td><td><a class="btn ghost" href="{{ route('backups.download',$b->id) }}">Baixar</a></td></tr>@endforeach
</tbody></table><br>{{ $backups->links() }}
@endsection
