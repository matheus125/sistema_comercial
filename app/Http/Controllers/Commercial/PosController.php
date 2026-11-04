<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Services\RestaurantStockService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function __construct(private readonly RestaurantStockService $stock)
    {
    }

    public function create(Request $request)
    {
        $companyId = $request->user()->company_id;
        $products = DB::table('products')
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereIn('product_type', ['resale', 'ready_dish', 'drink', 'combo', 'additional'])
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();
        $customers = DB::table('customers')->where('company_id', $companyId)->where('status', 'active')->whereNull('deleted_at')->orderBy('name')->get();
        $cash = DB::table('cash_registers')->where('company_id', $companyId)->where('user_id', $request->user()->id)->where('status', 'open')->first();
        $tables = DB::table('tables')->where('company_id', $companyId)->whereNull('deleted_at')->orderBy('number')->get();

        return view('pos.create', compact('products', 'customers', 'cash', 'tables'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'integer'],
            'table_id' => ['nullable', 'integer'],
            'order_type' => ['required', 'in:table,individual_tab,counter,pickup,delivery,internal_consumption'],
            'command_code' => ['nullable', 'string', 'max:60'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'payment_type' => ['required', 'in:cash,pix,debit_card,credit_card,mixed,credit'],
            'general_discount' => ['nullable', 'numeric', 'min:0'],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'products' => ['required', 'array', 'min:1'],
            'products.*' => ['required', 'integer'],
            'quantities' => ['required', 'array'],
            'discounts' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $orderId = DB::transaction(function () use ($request, $data) {
            $companyId = $request->user()->company_id;
            $cash = DB::table('cash_registers')->where('company_id', $companyId)->where('user_id', $request->user()->id)->where('status', 'open')->lockForUpdate()->first();
            abort_unless($cash, 422, 'Abra um caixa antes de registrar pagamento.');

            $allowNegative = $request->user()->isAdmin()
                && json_decode((string) DB::table('settings')->where('company_id', $companyId)->where('key', 'sale_without_stock')->value('value'), true) === true;

            $items = [];
            $subtotal = 0;

            foreach ($data['products'] as $i => $productId) {
                $qty = (float) ($data['quantities'][$i] ?? 1);
                if ($qty <= 0) {
                    continue;
                }

                $product = DB::table('products')->where('company_id', $companyId)->where('id', $productId)->whereNull('deleted_at')->lockForUpdate()->first();
                abort_unless($product, 404, 'Produto não encontrado.');

                if (!$allowNegative) {
                    $this->ensureStockAvailability($product, $qty, $companyId);
                }

                $discount = (float) ($data['discounts'][$i] ?? 0);
                $lineTotal = max(0, ((float) $product->sale_price * $qty) - $discount);
                $subtotal += $lineTotal;
                $items[] = compact('product', 'qty', 'discount', 'lineTotal');
            }

            abort_if(empty($items), 422, 'Inclua ao menos um item válido.');

            $generalDiscount = (float) ($data['general_discount'] ?? 0);
            $deliveryFee = (float) ($data['delivery_fee'] ?? 0);
            $total = max(0, $subtotal - $generalDiscount + $deliveryFee);
            $orderId = DB::table('orders')->insertGetId([
                'company_id' => $companyId,
                'branch_id' => $request->user()->branch_id,
                'table_id' => in_array($data['order_type'], ['table', 'individual_tab', 'internal_consumption'], true) ? ($data['table_id'] ?? null) : null,
                'cash_register_id' => $cash->id,
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => $request->user()->id,
                'waiter_id' => in_array($data['order_type'], ['table', 'individual_tab', 'internal_consumption'], true) ? $request->user()->id : null,
                'number' => 'PD'.now()->format('YmdHis').random_int(10, 99),
                'order_type' => $data['order_type'],
                'operation_mode' => $data['order_type'],
                'command_code' => $data['command_code'] ?: null,
                'production_status' => 'pending',
                'service_status' => 'received',
                'sent_to_production_at' => now(),
                'ready_at' => null,
                'delivered_at' => null,
                'delivery_fee' => $deliveryFee,
                'subtotal' => $subtotal,
                'discount' => $generalDiscount,
                'total' => $total,
                'payment_type' => $data['payment_type'],
                'cash_received' => $data['cash_received'] ?? null,
                'change_amount' => $data['payment_type'] === 'cash' ? max(0, (float) ($data['cash_received'] ?? 0) - $total) : 0,
                'status' => 'open',
                'paid_at' => $data['payment_type'] === 'credit' ? null : now(),
                'print_status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (in_array($data['order_type'], ['table', 'individual_tab', 'internal_consumption'], true) && !empty($data['table_id'])) {
                DB::table('tables')->where('id', $data['table_id'])->update(['status' => 'occupied', 'updated_at' => now()]);
            }

            foreach ($items as $item) {
                $product = $item['product'];
                DB::table('order_items')->insert([
                    'order_id' => $orderId,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['qty'],
                    'unit_price' => $product->sale_price,
                    'discount' => $item['discount'],
                    'total' => $item['lineTotal'],
                    'production_status' => 'pending',
                    'print_sector' => $product->print_sector ?? 'kitchen',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->stock->consumeForOrderItem($product, $item['qty'], $companyId, $request->user()->branch_id, $request->user()->id, 'Pedido #'.$orderId);
            }

            DB::table('payments')->insert([
                'company_id' => $companyId,
                'order_id' => $orderId,
                'cash_register_id' => $cash->id,
                'type' => $data['payment_type'],
                'amount' => $total,
                'status' => 'confirmed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('cash_movements')->insert([
                'company_id' => $companyId,
                'cash_register_id' => $cash->id,
                'user_id' => $request->user()->id,
                'order_id' => $orderId,
                'type' => $data['payment_type'] === 'credit' ? 'receivable' : 'order_payment',
                'payment_type' => $data['payment_type'],
                'amount' => $data['payment_type'] === 'credit' ? 0 : $total,
                'description' => 'Pedido #'.$orderId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($data['payment_type'] === 'credit') {
                DB::table('accounts_receivable')->insert([
                    'company_id' => $companyId,
                    'customer_id' => $data['customer_id'] ?? null,
                    'order_id' => $orderId,
                    'description' => 'Pedido fiado #'.$orderId,
                    'original_amount' => $total,
                    'paid_amount' => 0,
                    'pending_amount' => $total,
                    'due_date' => now()->addDays(30)->toDateString(),
                    'status' => 'open',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('notifications')->insert([
                'company_id' => $companyId,
                'user_id' => $request->user()->id,
                'type' => 'pedido_novo',
                'title' => 'Pedido registrado',
                'message' => 'Pedido #'.$orderId.' recebido no modo '.$this->labelOrderType($data['order_type']).'.',
                'severity' => 'success',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $orderId;
        });

        Audit::log('order.received', 'Pedido recebido #'.$orderId, [], [], 'orders', $orderId);

        return redirect()->route('sales.receipt', $orderId)->with('status', 'Pedido recebido e enviado para produção.');
    }

    public function receipt(Request $request, int $id)
    {
        $sale = DB::table('orders')->where('company_id', $request->user()->company_id)->where('id', $id)->first();
        abort_unless($sale, 404);
        $items = DB::table('order_items')->where('order_id', $id)->get();

        return view('pos.receipt', compact('sale', 'items'));
    }

    public function cancel(Request $request, int $id)
    {
        $request->validate(['reason' => ['required', 'string', 'min:5']]);

        DB::transaction(function () use ($request, $id) {
            $order = DB::table('orders')->where('company_id', $request->user()->company_id)->where('id', $id)->whereIn('status', ['open', 'closed'])->lockForUpdate()->first();
            abort_unless($order, 404);
            $items = DB::table('order_items')->where('order_id', $order->id)->get();

            foreach ($items as $item) {
                $product = DB::table('products')->where('id', $item->product_id)->lockForUpdate()->first();
                $this->stock->reverseOrderItem($product, (float) $item->quantity, $request->user()->company_id, $request->user()->branch_id, $request->user()->id, 'Cancelamento pedido #'.$order->id.': '.$request->reason);
            }

            DB::table('orders')->where('id', $order->id)->update(['status' => 'cancelled', 'production_status' => 'cancelled', 'cancellation_reason' => $request->reason, 'updated_at' => now()]);
            DB::table('cash_movements')->where('order_id', $order->id)->update(['amount' => 0, 'description' => 'Pedido cancelado: '.$request->reason]);
            DB::table('payments')->where('order_id', $order->id)->update(['status' => 'cancelled', 'updated_at' => now()]);
            DB::table('accounts_receivable')->where('order_id', $order->id)->update(['status' => 'cancelled', 'updated_at' => now()]);

            if ($order->table_id) {
                DB::table('tables')->where('id', $order->table_id)->update(['status' => 'available', 'updated_at' => now()]);
            }

            DB::table('cancellations')->insert([
                'company_id' => $request->user()->company_id,
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
                'reason' => $request->reason,
                'payload' => json_encode($order),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('notifications')->insert([
                'company_id' => $request->user()->company_id,
                'user_id' => $request->user()->id,
                'type' => 'cancelamento',
                'title' => 'Pedido cancelado',
                'message' => 'Pedido #'.$order->id.' cancelado e estoque revertido.',
                'severity' => 'warning',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Audit::log('order.cancelled', 'Pedido cancelado #'.$id, [], [], 'orders', $id);

        return back()->with('status', 'Pedido cancelado, estoque revertido e financeiro recalculado.');
    }

    private function ensureStockAvailability(object $product, float $quantity, int $companyId): void
    {
        $recipe = DB::table('recipes')
            ->where('company_id', $companyId)
            ->where('product_id', $product->id)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->first();

        if (!$recipe) {
            abort_if(in_array($product->product_type, ['resale', 'ingredient', 'drink', 'additional'], true) && (float) $product->stock_current < $quantity, 422, "Estoque insuficiente para {$product->name}.");
            return;
        }

        $ingredients = DB::table('recipe_items')
            ->join('products', 'products.id', '=', 'recipe_items.ingredient_product_id')
            ->select('recipe_items.quantity', 'products.name', 'products.stock_current')
            ->where('recipe_items.recipe_id', $recipe->id)
            ->get();

        foreach ($ingredients as $ingredient) {
            $needed = (float) $ingredient->quantity * $quantity;
            abort_if((float) $ingredient->stock_current < $needed, 422, "Insumo insuficiente para {$product->name}: {$ingredient->name}.");
        }
    }

    private function labelOrderType(string $type): string
    {
        return [
            'table' => 'mesa',
            'individual_tab' => 'comanda individual',
            'counter' => 'balcão',
            'pickup' => 'retirada',
            'delivery' => 'delivery',
            'internal_consumption' => 'consumo interno',
        ][$type] ?? $type;
    }
}
