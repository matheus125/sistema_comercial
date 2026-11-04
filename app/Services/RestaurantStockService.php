<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RestaurantStockService
{
    public function consumeForOrderItem(object $product, float $quantity, int $companyId, ?int $branchId, int $userId, string $reason): void
    {
        $recipe = DB::table('recipes')
            ->where('company_id', $companyId)
            ->where('product_id', $product->id)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->first();

        if ($recipe) {
            $ingredients = DB::table('recipe_items')
                ->join('products', 'products.id', '=', 'recipe_items.ingredient_product_id')
                ->select('recipe_items.*', 'products.name as ingredient_name', 'products.stock_current')
                ->where('recipe_items.recipe_id', $recipe->id)
                ->get();

            foreach ($ingredients as $ingredient) {
                $this->moveProduct(
                    productId: $ingredient->ingredient_product_id,
                    quantityMoved: -((float) $ingredient->quantity * $quantity),
                    type: 'recipe_consumption',
                    companyId: $companyId,
                    branchId: $branchId,
                    userId: $userId,
                    reason: $reason.' | insumo '.$ingredient->ingredient_name
                );
            }

            return;
        }

        if (in_array($product->product_type, ['resale', 'ingredient', 'drink', 'additional'], true)) {
            $this->moveProduct($product->id, -$quantity, 'order_sale', $companyId, $branchId, $userId, $reason);
        }
    }

    public function reverseOrderItem(object $product, float $quantity, int $companyId, ?int $branchId, int $userId, string $reason): void
    {
        $recipe = DB::table('recipes')
            ->where('company_id', $companyId)
            ->where('product_id', $product->id)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->first();

        if ($recipe) {
            DB::table('recipe_items')->where('recipe_id', $recipe->id)->get()->each(function ($ingredient) use ($quantity, $companyId, $branchId, $userId, $reason) {
                $this->moveProduct(
                    productId: $ingredient->ingredient_product_id,
                    quantityMoved: (float) $ingredient->quantity * $quantity,
                    type: 'order_cancellation',
                    companyId: $companyId,
                    branchId: $branchId,
                    userId: $userId,
                    reason: $reason
                );
            });

            return;
        }

        if (in_array($product->product_type, ['resale', 'ingredient', 'drink', 'additional'], true)) {
            $this->moveProduct($product->id, $quantity, 'order_cancellation', $companyId, $branchId, $userId, $reason);
        }
    }

    public function moveProduct(int $productId, float $quantityMoved, string $type, int $companyId, ?int $branchId, int $userId, ?string $reason = null): void
    {
        $product = DB::table('products')->where('company_id', $companyId)->where('id', $productId)->lockForUpdate()->first();
        abort_unless($product, 404, 'Produto não encontrado.');

        $before = (float) $product->stock_current;
        $after = $before + $quantityMoved;

        DB::table('products')->where('id', $product->id)->update([
            'stock_current' => $after,
            'quantity' => $after,
            'updated_at' => now(),
        ]);

        DB::table('stock_movements')->insert([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'product_id' => $product->id,
            'user_id' => $userId,
            'quantity_before' => $before,
            'quantity_moved' => $quantityMoved,
            'quantity_after' => $after,
            'type' => $type,
            'reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
