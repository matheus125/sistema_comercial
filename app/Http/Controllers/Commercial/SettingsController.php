<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        $company = DB::table('companies')->where('id', $request->user()->company_id)->first();
        $settings = DB::table('settings')->where('company_id', $request->user()->company_id)->pluck('value', 'key')->map(fn ($value) => json_decode($value, true));

        return view('settings.edit', compact('company', 'settings'));
    }

    public function update(Request $request)
    {
        DB::table('companies')->where('id', $request->user()->company_id)->update([
            'name' => $request->company_name,
            'legal_name' => $request->legal_name,
            'document' => $request->document,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'updated_at' => now(),
        ]);

        foreach ([
            'receipt_message', 'sale_without_stock', 'default_minimum_stock', 'expiration_alert_days',
            'currency', 'date_format', 'auto_backup', 'print_format',
        ] as $key) {
            DB::table('settings')->updateOrInsert(
                ['company_id' => $request->user()->company_id, 'key' => $key],
                ['value' => json_encode($request->input($key)), 'updated_at' => now(), 'created_at' => now()]
            );
        }

        Audit::log('settings.updated', 'Configurações atualizadas');
        return back()->with('status', 'Configurações salvas.');
    }
}
