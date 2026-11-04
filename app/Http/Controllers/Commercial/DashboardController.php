<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $companyId = $request->user()->company_id;
        $from = $request->date('from')?->startOfDay() ?? now()->startOfDay();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();

        $orders = DB::table('orders')->where('company_id', $companyId)->whereNull('deleted_at');
        $periodOrders = (clone $orders)->whereBetween('created_at', [$from, $to])->where('status', '!=', 'cancelled');

        $cards = [
            'Pedidos do dia' => (clone $orders)->whereDate('created_at', today())->where('status', '!=', 'cancelled')->sum('total'),
            'Pedidos do mês' => (clone $orders)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->where('status', '!=', 'cancelled')->sum('total'),
            'Lucro estimado' => DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->where('orders.company_id', $companyId)
                ->where('orders.status', '!=', 'cancelled')
                ->whereBetween('orders.created_at', [$from, $to])
                ->sum(DB::raw('(order_items.unit_price - products.cost_price) * order_items.quantity - order_items.discount')),
            'Total em dinheiro' => (clone $periodOrders)->where('payment_type', 'cash')->sum('total'),
            'Total em Pix' => (clone $periodOrders)->where('payment_type', 'pix')->sum('total'),
            'Total em cartão' => (clone $periodOrders)->whereIn('payment_type', ['debit_card', 'credit_card'])->sum('total'),
            'Caixas abertos' => DB::table('cash_registers')->where('company_id', $companyId)->where('status', 'open')->count(),
            'Caixas fechados' => DB::table('cash_registers')->where('company_id', $companyId)->where('status', 'closed')->whereBetween('closed_at', [$from, $to])->count(),
            'Contas a pagar vencidas' => DB::table('accounts_payable')->where('company_id', $companyId)->where('status', 'open')->whereDate('due_date', '<', today())->sum('amount'),
            'Contas a receber vencidas' => DB::table('accounts_receivable')->where('company_id', $companyId)->whereIn('status', ['open', 'partial'])->whereDate('due_date', '<', today())->sum('pending_amount'),
            'Pedidos cancelados' => (clone $orders)->where('status', 'cancelled')->whereBetween('created_at', [$from, $to])->count(),
            'Mesas ocupadas' => DB::table('tables')->where('company_id', $companyId)->where('status', 'occupied')->count(),
            'Pedidos delivery' => (clone $periodOrders)->where('order_type', 'delivery')->count(),
        ];

        $lowStock = DB::table('products')->where('company_id', $companyId)->whereColumn('stock_current', '<=', 'stock_minimum')->where('stock_current', '>', 0)->whereNull('deleted_at')->count();
        $zeroStock = DB::table('products')->where('company_id', $companyId)->where('stock_current', '<=', 0)->whereNull('deleted_at')->count();
        $expiring = DB::table('products')->where('company_id', $companyId)->whereBetween('expires_at', [today(), today()->addDays(30)])->whereNull('deleted_at')->count();

        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select('order_items.product_name', DB::raw('SUM(order_items.quantity) as quantity'), DB::raw('SUM(order_items.total) as total'))
            ->where('orders.company_id', $companyId)
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('order_items.product_name')
            ->orderByDesc('quantity')
            ->limit(8)
            ->get();

        $recent = DB::table('logs')
            ->leftJoin('users', 'users.id', '=', 'logs.user_id')
            ->select('logs.*', 'users.name as user_name')
            ->where('logs.company_id', $companyId)
            ->latest('logs.created_at')
            ->limit(12)
            ->get();

        return view('dashboard', compact('cards', 'lowStock', 'zeroStock', 'expiring', 'topProducts', 'recent', 'from', 'to'));
    }
}
