<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $sector = $request->get('sector', 'kitchen');

        $orders = DB::table('orders')
            ->leftJoin('tables', 'tables.id', '=', 'orders.table_id')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->select('orders.*', 'tables.number as table_number', 'customers.name as customer_name')
            ->where('orders.company_id', $companyId)
            ->whereIn('orders.status', ['open', 'closed'])
            ->whereIn('orders.service_status', ['received', 'preparing', 'ready', 'delivered', 'paid'])
            ->whereExists(function ($query) use ($sector) {
                $query->select(DB::raw(1))
                    ->from('order_items')
                    ->whereColumn('order_items.order_id', 'orders.id')
                    ->where('order_items.print_sector', $sector);
            })
            ->latest('orders.created_at')
            ->limit(50)
            ->get();

        $items = DB::table('order_items')
            ->whereIn('order_id', $orders->pluck('id'))
            ->where('print_sector', $sector)
            ->orderBy('created_at')
            ->get()
            ->groupBy('order_id');

        return view('production.index', compact('orders', 'items', 'sector'));
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'service_status' => ['required', 'in:received,preparing,ready,delivered,paid,cancelled'],
        ]);

        $order = DB::table('orders')->where('company_id', $request->user()->company_id)->where('id', $id)->first();
        abort_unless($order, 404);

        $updates = [
            'service_status' => $data['service_status'],
            'updated_at' => now(),
        ];

        if ($data['service_status'] === 'preparing') {
            $updates['production_status'] = 'preparing';
            $updates['sent_to_production_at'] = $order->sent_to_production_at ?: now();
        }
        if ($data['service_status'] === 'ready') {
            $updates['production_status'] = 'ready';
            $updates['ready_at'] = now();
            DB::table('order_items')->where('order_id', $id)->update(['production_status' => 'ready', 'ready_at' => now(), 'updated_at' => now()]);
            $this->notify($request, 'pedido_pronto', 'Pedido pronto', 'Pedido #'.$id.' está pronto.', 'success');
        }
        if ($data['service_status'] === 'delivered') {
            $updates['production_status'] = 'delivered';
            $updates['delivered_at'] = now();
            DB::table('order_items')->where('order_id', $id)->update(['production_status' => 'delivered', 'delivered_at' => now(), 'updated_at' => now()]);
        }
        if ($data['service_status'] === 'paid') {
            $updates['status'] = 'closed';
            $updates['paid_at'] = now();
        }
        if ($data['service_status'] === 'cancelled') {
            $updates['status'] = 'cancelled';
            $updates['production_status'] = 'cancelled';
            $updates['cancelled_at'] = now();
        }

        DB::table('orders')->where('id', $id)->update($updates);
        Audit::log('order.status_updated', 'Status do pedido #'.$id.' alterado para '.$data['service_status'], (array) $order, $updates, 'orders', $id);

        return back()->with('status', 'Status atualizado.');
    }

    public function printSector(Request $request, int $id, string $sector)
    {
        abort_unless(in_array($sector, ['kitchen', 'bar', 'cashier', 'customer'], true), 404);

        $order = DB::table('orders')->where('company_id', $request->user()->company_id)->where('id', $id)->first();
        abort_unless($order, 404);

        $items = DB::table('order_items')
            ->where('order_id', $id)
            ->when($sector !== 'customer', fn ($query) => $query->where('print_sector', $sector))
            ->get();

        DB::table('order_items')
            ->where('order_id', $id)
            ->when($sector !== 'customer', fn ($query) => $query->where('print_sector', $sector))
            ->update(['printed_at' => now(), 'updated_at' => now()]);

        DB::table('orders')->where('id', $id)->update(['print_status' => 'printed_'.$sector, 'updated_at' => now()]);
        Audit::log('order.printed', 'Impressão do pedido #'.$id.' no setor '.$sector, [], [], 'orders', $id);

        return view('production.print', compact('order', 'items', 'sector'));
    }

    private function notify(Request $request, string $type, string $title, string $message, string $severity): void
    {
        DB::table('notifications')->insert([
            'company_id' => $request->user()->company_id,
            'user_id' => $request->user()->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'severity' => $severity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
