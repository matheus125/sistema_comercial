<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends Controller
{
    public function index(Request $request)
    {
        $registers = DB::table('cash_registers')->join('users', 'users.id', '=', 'cash_registers.user_id')
            ->select('cash_registers.*', 'users.name as user_name')
            ->where('cash_registers.company_id', $request->user()->company_id)
            ->latest('cash_registers.created_at')
            ->paginate(15);

        return view('modules.cash', compact('registers'));
    }

    public function open(Request $request)
    {
        $data = $request->validate(['opening_amount' => ['required', 'numeric', 'min:0'], 'notes' => ['nullable', 'string']]);
        $exists = DB::table('cash_registers')->where('user_id', $request->user()->id)->where('status', 'open')->exists();
        abort_if($exists, 422, 'Este usuário já possui um caixa aberto.');

        DB::table('cash_registers')->insert([
            'company_id' => $request->user()->company_id,
            'branch_id' => $request->user()->branch_id,
            'user_id' => $request->user()->id,
            'opening_amount' => $data['opening_amount'],
            'expected_amount' => $data['opening_amount'],
            'status' => 'open',
            'opened_at' => now(),
            'notes' => $data['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Audit::log('cash.opened', 'Caixa aberto');
        return back()->with('status', 'Caixa aberto.');
    }

    public function movement(Request $request)
    {
        $data = $request->validate([
            'cash_register_id' => ['required', 'integer'],
            'type' => ['required', 'in:supply,withdrawal,expense,receivable'],
            'payment_type' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
        ]);

        DB::table('cash_movements')->insert([
            'company_id' => $request->user()->company_id,
            'cash_register_id' => $data['cash_register_id'],
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'payment_type' => $data['payment_type'] ?? 'cash',
            'amount' => in_array($data['type'], ['withdrawal', 'expense'], true) ? -$data['amount'] : $data['amount'],
            'description' => $data['description'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Audit::log('cash.movement', 'Movimentação de caixa: '.$data['type']);
        return back()->with('status', 'Movimentação registrada.');
    }

    public function close(Request $request, int $id)
    {
        $request->validate(
            ['informed_amount' => ['required', 'numeric', 'min:0']],
            [
                'informed_amount.required' => 'Informe o valor conferido para fechar o caixa.',
                'informed_amount.numeric' => 'O valor conferido deve ser numérico.',
                'informed_amount.min' => 'O valor conferido não pode ser negativo.',
            ]
        );
        $cash = DB::table('cash_registers')->where('company_id', $request->user()->company_id)->where('id', $id)->where('status', 'open')->first();
        abort_unless($cash, 404);

        $movementTotal = DB::table('cash_movements')->where('cash_register_id', $id)->sum('amount');
        $expected = (float) $cash->opening_amount + (float) $movementTotal;
        $informed = (float) $request->informed_amount;
        $totals = DB::table('cash_movements')->where('cash_register_id', $id)->select('payment_type', DB::raw('SUM(amount) as total'))->groupBy('payment_type')->pluck('total', 'payment_type');

        DB::table('cash_registers')->where('id', $id)->update([
            'expected_amount' => $expected,
            'informed_amount' => $informed,
            'difference_amount' => $informed - $expected,
            'payment_totals' => json_encode($totals),
            'status' => 'closed',
            'closed_at' => now(),
            'updated_at' => now(),
        ]);

        Audit::log('cash.closed', 'Caixa fechado #'.$id);
        return back()->with('status', 'Caixa fechado.');
    }
}
