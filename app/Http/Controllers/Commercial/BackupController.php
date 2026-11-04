<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function index(Request $request)
    {
        $backups = DB::table('backups')->where('company_id', $request->user()->company_id)->latest()->paginate(15);
        return view('backups.index', compact('backups'));
    }

    public function store(Request $request)
    {
        $path = database_path('database.sqlite');
        $target = 'backups/backup-'.now()->format('Ymd-His').'.sqlite';
        Storage::disk('local')->put($target, File::get($path));

        DB::table('backups')->insert([
            'company_id' => $request->user()->company_id,
            'user_id' => $request->user()->id,
            'file_path' => $target,
            'file_size' => Storage::disk('local')->size($target),
            'status' => 'success',
            'message' => 'Backup manual realizado.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Audit::log('backup.created', 'Backup manual realizado');
        return back()->with('status', 'Backup realizado.');
    }

    public function download(Request $request, int $id)
    {
        $backup = DB::table('backups')->where('company_id', $request->user()->company_id)->where('id', $id)->first();
        abort_unless($backup, 404);
        return Storage::disk('local')->download($backup->file_path);
    }
}
