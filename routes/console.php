<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('backup:database', function () {
    $path = database_path('database.sqlite');

    if (!File::exists($path)) {
        $this->error('Banco SQLite não encontrado em '.$path);
        return self::FAILURE;
    }

    $target = 'backups/auto-backup-'.now()->format('Ymd-His').'.sqlite';
    Storage::disk('local')->put($target, File::get($path));

    DB::table('backups')->insert([
        'file_path' => $target,
        'file_size' => Storage::disk('local')->size($target),
        'status' => 'success',
        'message' => 'Backup automático via scheduler.',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('notifications')->insert([
        'type' => 'backup',
        'title' => 'Backup realizado',
        'message' => 'Backup automático concluído com sucesso.',
        'severity' => 'success',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->info('Backup criado: '.$target);
    return self::SUCCESS;
})->purpose('Create a local database backup');

Schedule::command('backup:database')->dailyAt('02:00');
