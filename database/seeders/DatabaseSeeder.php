<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = [
            'dashboard.view' => ['Dashboard', 'dashboard'],
            'products.manage' => ['Gerenciar produtos', 'products'],
            'catalog.manage' => ['Gerenciar categorias e marcas', 'catalog'],
            'suppliers.manage' => ['Gerenciar fornecedores', 'suppliers'],
            'customers.manage' => ['Gerenciar clientes', 'customers'],
            'sales.manage' => ['Gerenciar pedidos', 'orders'],
            'sales.cancel' => ['Cancelar pedidos', 'orders'],
            'discount.apply' => ['Aplicar desconto', 'orders'],
            'cash.manage' => ['Gerenciar caixa', 'cash'],
            'cash.open' => ['Abrir caixa', 'cash'],
            'cash.close' => ['Fechar caixa', 'cash'],
            'stock.manage' => ['Visualizar estoque', 'stock'],
            'stock.adjust' => ['Alterar estoque', 'stock'],
            'inventory.manage' => ['Gerenciar inventário', 'inventory'],
            'purchases.manage' => ['Gerenciar compras', 'purchases'],
            'finance.manage' => ['Gerenciar financeiro', 'finance'],
            'reports.view' => ['Acessar relatórios', 'reports'],
            'settings.manage' => ['Configurações', 'settings'],
            'backup.manage' => ['Backups', 'backup'],
        ];

        foreach ($permissions as $slug => [$name, $module]) {
            DB::table('permissions')->updateOrInsert(['slug' => $slug], ['name' => $name, 'module' => $module, 'created_at' => now(), 'updated_at' => now()]);
        }

        $profiles = [
            'administrador' => ['Administrador', array_keys($permissions)],
            'gerente' => ['Gerente', ['dashboard.view', 'products.manage', 'catalog.manage', 'suppliers.manage', 'customers.manage', 'sales.manage', 'sales.cancel', 'discount.apply', 'cash.manage', 'cash.open', 'cash.close', 'stock.manage', 'stock.adjust', 'inventory.manage', 'purchases.manage', 'finance.manage', 'reports.view']],
            'vendedor' => ['Vendedor', ['dashboard.view', 'customers.manage', 'sales.manage', 'discount.apply']],
            'operador-caixa' => ['Operador de Caixa', ['dashboard.view', 'sales.manage', 'cash.manage', 'cash.open', 'cash.close']],
            'estoquista' => ['Estoquista', ['dashboard.view', 'products.manage', 'stock.manage', 'stock.adjust', 'inventory.manage', 'purchases.manage']],
            'financeiro' => ['Financeiro', ['dashboard.view', 'finance.manage', 'cash.manage', 'reports.view']],
        ];

        foreach ($profiles as $slug => [$name, $profilePermissions]) {
            $profileId = DB::table('profiles')->updateOrInsert(
                ['slug' => $slug],
                ['name' => $name, 'description' => 'Perfil '.$name, 'created_at' => now(), 'updated_at' => now()]
            );
            $profile = DB::table('profiles')->where('slug', $slug)->first();
            foreach ($profilePermissions as $permissionSlug) {
                $permission = DB::table('permissions')->where('slug', $permissionSlug)->first();
                DB::table('profile_permissions')->updateOrInsert(
                    ['profile_id' => $profile->id, 'permission_id' => $permission->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        $companyId = DB::table('companies')->insertGetId([
            'name' => 'Restaurante Demonstração',
            'legal_name' => 'Restaurante Demonstração LTDA',
            'document' => '00.000.000/0001-00',
            'phone' => '(00) 0000-0000',
            'email' => 'contato@sistema.local',
            'address' => 'Endereço da empresa',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $branchId = DB::table('branches')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Matriz',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminProfile = DB::table('profiles')->where('slug', 'administrador')->first();
        User::query()->updateOrCreate(
            ['email' => 'admin@sistema.local'],
            [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'profile_id' => $adminProfile->id,
                'name' => 'Administrador',
                'phone' => '(00) 0000-0000',
                'status' => 'active',
                'password' => Hash::make('Admin@123456'),
            ]
        );

        $categoryId = DB::table('categories')->insertGetId(['company_id' => $companyId, 'name' => 'Pratos', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $brandId = DB::table('brands')->insertGetId(['company_id' => $companyId, 'name' => 'Marca Própria', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $supplierId = DB::table('suppliers')->insertGetId(['company_id' => $companyId, 'name' => 'Fornecedor Padrão', 'document' => '00000000000', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('customers')->insert([
            'company_id' => $companyId,
            'name' => 'Cliente Delivery',
            'phone' => '(00) 90000-0000',
            'address' => 'Rua das Entregas, 100',
            'delivery_reference' => 'Próximo à praça',
            'delivery_fee' => 7,
            'delivery_notes' => 'Chamar no WhatsApp ao chegar',
            'credit_limit' => 200,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (range(1, 8) as $number) {
            DB::table('tables')->insert([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'number' => str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'name' => 'Salão',
                'seats' => 4,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $products = [];
        foreach ([
            ['ARZ001', '7890000000011', 'Arroz cozido', 'ingredient', 'kitchen', 'KG', 5.00, 0.00, 25, 5],
            ['FEJ001', '7890000000028', 'Feijão cozido', 'ingredient', 'kitchen', 'KG', 7.00, 0.00, 18, 5],
            ['CAR001', '7890000000035', 'Carne grelhada', 'ingredient', 'kitchen', 'KG', 32.00, 0.00, 12, 3],
            ['REF001', '7890000000042', 'Refrigerante lata', 'drink', 'bar', 'UN', 3.20, 6.50, 48, 12],
            ['ADD001', null, 'Ovo extra', 'additional', 'kitchen', 'UN', 1.20, 3.00, 60, 12],
            ['PRA001', null, 'PF Executivo', 'ready_dish', 'kitchen', 'UN', 12.00, 28.90, 0, 0],
            ['COM001', null, 'Combo PF + Refri', 'combo', 'kitchen', 'UN', 15.20, 33.90, 0, 0],
        ] as [$code, $barcode, $name, $type, $sector, $unit, $cost, $price, $stock, $minimum]) {
            $products[$code] = DB::table('products')->insertGetId([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'supplier_id' => $supplierId,
                'internal_code' => $code,
                'barcode' => $barcode,
                'name' => $name,
                'product_type' => $type,
                'print_sector' => $sector,
                'print_on_production' => true,
                'unit' => $unit,
                'unit_of_measure' => $unit,
                'cost_price' => $cost,
                'sale_price' => $price,
                'profit_margin' => $cost > 0 ? round((($price - $cost) / $cost) * 100, 2) : 0,
                'stock_current' => $stock,
                'stock_minimum' => $minimum,
                'quantity' => $stock,
                'minimum_stock' => $minimum,
                'cost' => $cost,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $pfRecipeId = DB::table('recipes')->insertGetId([
            'company_id' => $companyId,
            'product_id' => $products['PRA001'],
            'name' => 'Ficha técnica - PF Executivo',
            'yield_quantity' => 1,
            'yield_unit' => 'UN',
            'estimated_cost' => 12,
            'preparation_notes' => 'Montar prato com arroz, feijão e carne grelhada.',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            [$products['ARZ001'], 0.18, 'KG', 0.90],
            [$products['FEJ001'], 0.14, 'KG', 0.98],
            [$products['CAR001'], 0.22, 'KG', 7.04],
        ] as [$ingredientId, $quantity, $unit, $cost]) {
            DB::table('recipe_items')->insert([
                'company_id' => $companyId,
                'recipe_id' => $pfRecipeId,
                'ingredient_product_id' => $ingredientId,
                'quantity' => $quantity,
                'unit_of_measure' => $unit,
                'cost' => $cost,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $comboRecipeId = DB::table('recipes')->insertGetId([
            'company_id' => $companyId,
            'product_id' => $products['COM001'],
            'name' => 'Ficha técnica - Combo PF + Refri',
            'yield_quantity' => 1,
            'yield_unit' => 'UN',
            'estimated_cost' => 15.20,
            'preparation_notes' => 'Baixa os insumos do PF e uma unidade de refrigerante.',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            [$products['ARZ001'], 0.18, 'KG', 0.90],
            [$products['FEJ001'], 0.14, 'KG', 0.98],
            [$products['CAR001'], 0.22, 'KG', 7.04],
            [$products['REF001'], 1, 'UN', 3.20],
        ] as [$ingredientId, $quantity, $unit, $cost]) {
            DB::table('recipe_items')->insert([
                'company_id' => $companyId,
                'recipe_id' => $comboRecipeId,
                'ingredient_product_id' => $ingredientId,
                'quantity' => $quantity,
                'unit_of_measure' => $unit,
                'cost' => $cost,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('settings')->insert([
            ['company_id' => $companyId, 'key' => 'sale_without_stock', 'value' => json_encode(false), 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $companyId, 'key' => 'receipt_message', 'value' => json_encode('Obrigado pela preferência.'), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
