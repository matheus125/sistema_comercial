<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function movements(Request $request)
    {
        $movements = DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->join('users', 'users.id', '=', 'stock_movements.user_id')
            ->select('stock_movements.*', 'products.name as product_name', 'users.name as user_name')
            ->where('stock_movements.company_id', $request->user()->company_id)
            ->latest('stock_movements.created_at')
            ->paginate(20);

        $products = DB::table('products')->where('company_id', $request->user()->company_id)->whereNull('deleted_at')->orderBy('name')->get();

        return view('modules.stock', compact('movements', 'products'));
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric'],
            'type' => ['required', 'in:manual_adjustment,loss,return,transfer,inventory'],
            'reason' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $data) {
            $product = DB::table('products')->where('company_id', $request->user()->company_id)->where('id', $data['product_id'])->lockForUpdate()->first();
            abort_unless($product, 404);

            $before = (float) $product->stock_current;
            $moved = (float) $data['quantity'];
            $after = $before + $moved;

            DB::table('products')->where('id', $product->id)->update(['stock_current' => $after, 'quantity' => $after, 'updated_at' => now()]);
            DB::table('stock_movements')->insert([
                'company_id' => $request->user()->company_id,
                'branch_id' => $request->user()->branch_id,
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'quantity_before' => $before,
                'quantity_moved' => $moved,
                'quantity_after' => $after,
                'type' => $data['type'],
                'reason' => $data['reason'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Audit::log('stock.adjusted', 'Ajuste manual de estoque');

        return back()->with('status', 'Estoque ajustado.');
    }
}
