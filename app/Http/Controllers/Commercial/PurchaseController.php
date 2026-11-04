<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $purchases = DB::table('purchases')->leftJoin('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->select('purchases.*', 'suppliers.name as supplier_name')
            ->where('purchases.company_id', $request->user()->company_id)
            ->latest('purchases.created_at')
            ->paginate(15);
        $products = DB::table('products')->where('company_id', $request->user()->company_id)->whereNull('deleted_at')->orderBy('name')->get();
        $suppliers = DB::table('suppliers')->where('company_id', $request->user()->company_id)->whereNull('deleted_at')->orderBy('name')->get();

        return view('modules.purchases', compact('purchases', 'products', 'suppliers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', 'integer'],
            'purchase_date' => ['required', 'date'],
            'document_number' => ['nullable', 'string'],
            'payment_type' => ['required', 'in:cash,installments,pix,debit_card,credit_card'],
            'due_date' => ['nullable', 'date'],
            'installments' => ['nullable', 'integer', 'min:1'],
            'products' => ['required', 'array', 'min:1'],
            'quantities' => ['required', 'array'],
            'cost_prices' => ['required', 'array'],
            'observations' => ['nullable', 'string'],
        ]);

        $purchaseId = DB::transaction(function () use ($request, $data) {
            $total = 0;
            foreach ($data['products'] as $i => $productId) {
                $total += (float) ($data['quantities'][$i] ?? 0) * (float) ($data['cost_prices'][$i] ?? 0);
            }

            $purchaseId = DB::table('purchases')->insertGetId([
                'company_id' => $request->user()->company_id,
                'branch_id' => $request->user()->branch_id,
                'supplier_id' => $data['supplier_id'] ?? null,
                'user_id' => $request->user()->id,
                'purchase_date' => $data['purchase_date'],
                'document_number' => $data['document_number'] ?? null,
                'total' => $total,
                'payment_type' => $data['payment_type'],
                'due_date' => $data['due_date'] ?? null,
                'installments' => $data['installments'] ?? 1,
                'status' => 'confirmed',
                'observations' => $data['observations'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($data['products'] as $i => $productId) {
                $qty = (float) ($data['quantities'][$i] ?? 0);
                $cost = (float) ($data['cost_prices'][$i] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $product = DB::table('products')->where('company_id', $request->user()->company_id)->where('id', $productId)->lockForUpdate()->first();
                abort_unless($product, 404);
                $before = (float) $product->stock_current;
                $after = $before + $qty;

                DB::table('purchase_items')->insert([
                    'purchase_id' => $purchaseId,
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'cost_price' => $cost,
                    'total' => $qty * $cost,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('products')->where('id', $productId)->update([
                    'stock_current' => $after,
                    'quantity' => $after,
                    'cost_price' => $cost,
                    'cost' => $cost,
                    'updated_at' => now(),
                ]);
                DB::table('stock_movements')->insert([
                    'company_id' => $request->user()->company_id,
                    'branch_id' => $request->user()->branch_id,
                    'product_id' => $productId,
                    'user_id' => $request->user()->id,
                    'quantity_before' => $before,
                    'quantity_moved' => $qty,
                    'quantity_after' => $after,
                    'type' => 'purchase',
                    'reason' => 'Compra #'.$purchaseId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($data['payment_type'] === 'installments') {
                $installments = max(1, (int) ($data['installments'] ?? 1));
                for ($i = 1; $i <= $installments; $i++) {
                    DB::table('accounts_payable')->insert([
                        'company_id' => $request->user()->company_id,
                        'supplier_id' => $data['supplier_id'] ?? null,
                        'purchase_id' => $purchaseId,
                        'description' => "Compra #{$purchaseId} parcela {$i}/{$installments}",
                        'amount' => round($total / $installments, 2),
                        'due_date' => \Carbon\Carbon::parse($data['due_date'] ?? now())->addMonths($i - 1)->toDateString(),
                        'status' => 'open',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::table('notifications')->insert([
                'company_id' => $request->user()->company_id,
                'user_id' => $request->user()->id,
                'type' => 'compra_registrada',
                'title' => 'Compra registrada',
                'message' => 'Compra #'.$purchaseId.' atualizou o estoque automaticamente.',
                'severity' => 'success',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $purchaseId;
        });

        Audit::log('purchase.created', 'Compra registrada #'.$purchaseId, [], [], 'purchases', $purchaseId);
        return back()->with('status', 'Compra registrada e estoque atualizado.');
    }
}
