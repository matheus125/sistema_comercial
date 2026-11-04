<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Audit
{
    public static function log(string $action, ?string $description = null, array $old = [], array $new = [], ?string $auditableType = null, ?int $auditableId = null): void
    {
        $request = request();
        $user = Auth::user();

        DB::table('logs')->insert([
            'company_id' => $user?->company_id,
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'ip_address' => $request instanceof Request ? $request->ip() : null,
            'user_agent' => $request instanceof Request ? substr((string) $request->userAgent(), 0, 1000) : null,
            'description' => $description,
            'old_values' => $old ? json_encode($old) : null,
            'new_values' => $new ? json_encode($new) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
