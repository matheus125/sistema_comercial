<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();

        $orders = DB::table('orders')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->leftJoin('users as waiters', 'waiters.id', '=', 'orders.waiter_id')
            ->leftJoin('tables', 'tables.id', '=', 'orders.table_id')
            ->select('orders.*', 'customers.name as customer_name', 'users.name as user_name', 'waiters.name as waiter_name', 'tables.number as table_number')
            ->where('orders.company_id', $companyId)
            ->whereBetween('orders.created_at', [$from, $to])
            ->when($request->filled('order_type'), fn ($query) => $query->where('orders.order_type', $request->order_type))
            ->when($request->filled('table_id'), fn ($query) => $query->where('orders.table_id', $request->table_id))
            ->when($request->filled('user_id'), fn ($query) => $query->where('orders.user_id', $request->user_id))
            ->latest('orders.created_at')
            ->get();

        $stockAlerts = DB::table('products')->where('company_id', $companyId)->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereColumn('stock_current', '<=', 'stock_minimum')->orWhere('stock_current', '<=', 0)->orWhereBetween('expires_at', [today(), today()->addDays(30)]);
            })
            ->orderBy('name')
            ->get();

        $topProducts = DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->select('order_items.product_name', DB::raw('SUM(order_items.quantity) as quantity'), DB::raw('SUM(order_items.total) as total'))
            ->where('orders.company_id', $companyId)->whereBetween('orders.created_at', [$from, $to])
            ->groupBy('order_items.product_name')->orderByDesc('quantity')->get();

        $payables = DB::table('accounts_payable')->where('company_id', $companyId)->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])->get();
        $receivables = DB::table('accounts_receivable')->where('company_id', $companyId)->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])->get();

        if ($request->get('export') === 'csv') {
            return response()->streamDownload(function () use ($orders) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Pedido', 'Tipo', 'Mesa', 'Data', 'Cliente', 'Garçom', 'Pagamento', 'Status', 'Total']);
                foreach ($orders as $order) {
                    fputcsv($out, [$order->number, $order->order_type, $order->table_number, $order->created_at, $order->customer_name, $order->waiter_name ?: $order->user_name, $order->payment_type, $order->status, $order->total]);
                }
                fclose($out);
            }, 'relatorio-pedidos.csv');
        }

        $tables = DB::table('tables')->where('company_id', $companyId)->whereNull('deleted_at')->orderBy('number')->get();
        $users = DB::table('users')->where('company_id', $companyId)->whereNull('deleted_at')->orderBy('name')->get();

        return view('reports.index', compact('from', 'to', 'orders', 'stockAlerts', 'topProducts', 'payables', 'receivables', 'tables', 'users'));
    }
}
