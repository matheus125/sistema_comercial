<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $inventories = DB::table('inventory')->join('users', 'users.id', '=', 'inventory.user_id')
            ->select('inventory.*', 'users.name as user_name')
            ->where('inventory.company_id', $request->user()->company_id)
            ->latest('inventory.created_at')
            ->paginate(15);

        return view('modules.inventory', compact('inventories'));
    }

    public function open(Request $request)
    {
        $inventoryId = DB::transaction(function () use ($request) {
            $inventoryId = DB::table('inventory')->insertGetId([
                'company_id' => $request->user()->company_id,
                'branch_id' => $request->user()->branch_id,
                'user_id' => $request->user()->id,
                'status' => 'open',
                'opened_at' => now(),
                'observations' => $request->observations,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('products')->where('company_id', $request->user()->company_id)->whereNull('deleted_at')->orderBy('name')->get()->each(function ($product) use ($inventoryId) {
                DB::table('inventory_items')->insert([
                    'inventory_id' => $inventoryId,
                    'product_id' => $product->id,
                    'system_quantity' => $product->stock_current,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            return $inventoryId;
        });

        Audit::log('inventory.opened', 'Inventário aberto #'.$inventoryId);
        return redirect()->route('inventory.show', $inventoryId);
    }

    public function show(Request $request, int $id)
    {
        $inventory = DB::table('inventory')->where('company_id', $request->user()->company_id)->where('id', $id)->first();
        abort_unless($inventory, 404);
        $items = DB::table('inventory_items')->join('products', 'products.id', '=', 'inventory_items.product_id')
            ->select('inventory_items.*', 'products.name as product_name')
            ->where('inventory_id', $id)
            ->orderBy('products.name')
            ->get();

        return view('modules.inventory-show', compact('inventory', 'items'));
    }

    public function count(Request $request, int $id)
    {
        foreach ($request->input('counted', []) as $itemId => $counted) {
            $item = DB::table('inventory_items')->where('inventory_id', $id)->where('id', $itemId)->first();
            if ($item) {
                DB::table('inventory_items')->where('id', $itemId)->update([
                    'counted_quantity' => (float) $counted,
                    'divergence' => (float) $counted - (float) $item->system_quantity,
                    'updated_at' => now(),
                ]);
            }
        }

        Audit::log('inventory.counted', 'Contagem registrada #'.$id);
        return back()->with('status', 'Contagem salva.');
    }

    public function apply(Request $request, int $id)
    {
        DB::transaction(function () use ($request, $id) {
            $items = DB::table('inventory_items')->where('inventory_id', $id)->where('adjusted', false)->whereNotNull('counted_quantity')->get();
            foreach ($items as $item) {
                if ((float) $item->divergence === 0.0) {
                    continue;
                }
                $product = DB::table('products')->where('id', $item->product_id)->lockForUpdate()->first();
                $before = (float) $product->stock_current;
                $after = (float) $item->counted_quantity;
                DB::table('products')->where('id', $product->id)->update(['stock_current' => $after, 'quantity' => $after, 'updated_at' => now()]);
                DB::table('stock_movements')->insert([
                    'company_id' => $request->user()->company_id,
                    'branch_id' => $request->user()->branch_id,
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'quantity_before' => $before,
                    'quantity_moved' => $after - $before,
                    'quantity_after' => $after,
                    'type' => 'inventory',
                    'reason' => 'Ajuste de inventário #'.$id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('inventory_items')->where('id', $item->id)->update(['adjusted' => true, 'updated_at' => now()]);
            }
            DB::table('inventory')->where('id', $id)->update(['status' => 'closed', 'closed_at' => now(), 'updated_at' => now()]);
        });

        Audit::log('inventory.applied', 'Ajustes aplicados #'.$id);
        return redirect()->route('inventory.index')->with('status', 'Inventário ajustado e encerrado.');
    }
}
