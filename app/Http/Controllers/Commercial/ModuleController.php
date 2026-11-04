<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModuleController extends Controller
{
    private array $modules = [
        'products' => ['title' => 'Produtos', 'table' => 'products', 'permission' => 'products.manage', 'search' => ['name', 'internal_code', 'barcode'], 'fields' => [
            'internal_code' => 'Código interno', 'barcode' => 'Código de barras', 'name' => 'Nome', 'product_type' => 'Tipo de produto', 'print_sector' => 'Setor de impressão', 'category_id' => 'Categoria', 'brand_id' => 'Marca', 'supplier_id' => 'Fornecedor', 'unit_of_measure' => 'Unidade de medida', 'cost_price' => 'Custo', 'sale_price' => 'Preço de venda', 'profit_margin' => 'Margem %', 'stock_current' => 'Quantidade/estoque', 'stock_minimum' => 'Estoque mínimo', 'lot' => 'Lote', 'expires_at' => 'Validade', 'status' => 'Status', 'observations' => 'Observações',
        ]],
        'recipes' => ['title' => 'Fichas técnicas', 'table' => 'recipes', 'permission' => 'products.manage', 'search' => ['name'], 'fields' => ['product_id' => 'Prato/combo', 'name' => 'Nome da ficha', 'yield_quantity' => 'Rendimento', 'yield_unit' => 'Unidade do rendimento', 'estimated_cost' => 'Custo estimado', 'preparation_notes' => 'Modo de preparo', 'status' => 'Status']],
        'recipe-items' => ['title' => 'Ingredientes da ficha', 'table' => 'recipe_items', 'permission' => 'products.manage', 'search' => ['unit_of_measure'], 'fields' => ['recipe_id' => 'Ficha técnica', 'ingredient_product_id' => 'Insumo/ingrediente', 'quantity' => 'Quantidade', 'unit_of_measure' => 'Unidade de medida', 'cost' => 'Custo']],
        'categories' => ['title' => 'Categorias', 'table' => 'categories', 'permission' => 'catalog.manage', 'search' => ['name'], 'fields' => ['name' => 'Nome', 'status' => 'Status', 'observations' => 'Observações']],
        'brands' => ['title' => 'Marcas', 'table' => 'brands', 'permission' => 'catalog.manage', 'search' => ['name'], 'fields' => ['name' => 'Nome', 'status' => 'Status', 'observations' => 'Observações']],
        'suppliers' => ['title' => 'Fornecedores', 'table' => 'suppliers', 'permission' => 'suppliers.manage', 'search' => ['name', 'document', 'email'], 'fields' => ['name' => 'Nome/Razão social', 'document' => 'CPF/CNPJ', 'phone' => 'Telefone', 'whatsapp' => 'WhatsApp', 'email' => 'E-mail', 'address' => 'Endereço', 'status' => 'Status', 'observations' => 'Observações']],
        'customers' => ['title' => 'Clientes', 'table' => 'customers', 'permission' => 'customers.manage', 'search' => ['name', 'document', 'email'], 'fields' => ['name' => 'Nome', 'document' => 'CPF/CNPJ', 'phone' => 'Telefone', 'whatsapp' => 'WhatsApp', 'email' => 'E-mail', 'address' => 'Endereço', 'delivery_reference' => 'Referência delivery', 'delivery_fee' => 'Taxa de entrega padrão', 'delivery_notes' => 'Observações entrega', 'birth_date' => 'Nascimento', 'credit_limit' => 'Limite de crédito', 'status' => 'Status', 'observations' => 'Observações']],
        'accounts-payable' => ['title' => 'Contas a pagar', 'table' => 'accounts_payable', 'permission' => 'finance.manage', 'search' => ['description', 'expense_category'], 'fields' => ['supplier_id' => 'Fornecedor', 'description' => 'Descrição', 'expense_category' => 'Categoria', 'amount' => 'Valor', 'due_date' => 'Vencimento', 'paid_at' => 'Pagamento', 'payment_type' => 'Forma de pagamento', 'status' => 'Status', 'observations' => 'Observações']],
        'accounts-receivable' => ['title' => 'Contas a receber', 'table' => 'accounts_receivable', 'permission' => 'finance.manage', 'search' => ['description'], 'fields' => ['customer_id' => 'Cliente', 'description' => 'Descrição', 'original_amount' => 'Valor original', 'paid_amount' => 'Valor pago', 'pending_amount' => 'Valor pendente', 'due_date' => 'Vencimento', 'status' => 'Status']],
        'users' => ['title' => 'Usuários', 'table' => 'users', 'permission' => 'settings.manage', 'search' => ['name', 'email'], 'fields' => ['name' => 'Nome', 'email' => 'E-mail', 'phone' => 'Telefone', 'profile_id' => 'Perfil', 'branch_id' => 'Filial', 'status' => 'Status', 'password' => 'Senha']],
        'branches' => ['title' => 'Filiais', 'table' => 'branches', 'permission' => 'settings.manage', 'search' => ['name', 'document'], 'fields' => ['name' => 'Nome', 'document' => 'CNPJ', 'phone' => 'Telefone', 'address' => 'Endereço', 'status' => 'Status']],
        'tables' => ['title' => 'Mesas e comandas', 'table' => 'tables', 'permission' => 'settings.manage', 'search' => ['number', 'name'], 'fields' => ['number' => 'Número', 'name' => 'Nome/ambiente', 'seats' => 'Lugares', 'status' => 'Status', 'merged_table_id' => 'Mesa agrupada', 'notes' => 'Observações']],
    ];

    public function index(Request $request, string $module)
    {
        $config = $this->config($module);
        $this->ensure($request, $config);
        $query = DB::table($config['table'])->where('company_id', $request->user()->company_id)->whereNull('deleted_at');

        if ($search = $request->get('q')) {
            $query->where(function ($builder) use ($config, $search) {
                foreach ($config['search'] as $field) {
                    $builder->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        $rows = $query->latest()->paginate(15)->withQueryString();
        $options = $this->options($request);

        return view('modules.index', compact('module', 'config', 'rows', 'options'));
    }

    public function create(Request $request, string $module)
    {
        $config = $this->config($module);
        $this->ensure($request, $config);
        $row = null;
        $options = $this->options($request);

        return view('modules.form', compact('module', 'config', 'row', 'options'));
    }

    public function store(Request $request, string $module)
    {
        $config = $this->config($module);
        $this->ensure($request, $config);
        $data = $this->payload($request, $config);
        $data['company_id'] = $request->user()->company_id;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        if ($config['table'] === 'products' && blank($data['internal_code'] ?? null)) {
            $data['internal_code'] = 'PRD'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        }
        if ($config['table'] === 'users' && filled($data['password'] ?? null)) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        }

        $id = DB::table($config['table'])->insertGetId($data);
        Audit::log($config['table'].'.created', $config['title'].' cadastrado', [], ['id' => $id] + $data);

        return redirect()->route('modules.index', $module)->with('status', 'Registro salvo.');
    }

    public function edit(Request $request, string $module, int $id)
    {
        $config = $this->config($module);
        $this->ensure($request, $config);
        $row = DB::table($config['table'])->where('company_id', $request->user()->company_id)->where('id', $id)->first();
        abort_unless($row, 404);
        $options = $this->options($request);

        return view('modules.form', compact('module', 'config', 'row', 'options'));
    }

    public function update(Request $request, string $module, int $id)
    {
        $config = $this->config($module);
        $this->ensure($request, $config);
        $old = (array) DB::table($config['table'])->where('company_id', $request->user()->company_id)->where('id', $id)->first();
        abort_unless($old, 404);

        $data = $this->payload($request, $config);
        if ($config['table'] === 'users') {
            if (filled($data['password'] ?? null)) {
                $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
            } else {
                unset($data['password']);
            }
        }
        $data['updated_at'] = now();
        DB::table($config['table'])->where('id', $id)->update($data);
        Audit::log($config['table'].'.updated', $config['title'].' atualizado', $old, $data);

        return redirect()->route('modules.index', $module)->with('status', 'Registro atualizado.');
    }

    public function destroy(Request $request, string $module, int $id)
    {
        $config = $this->config($module);
        $this->ensure($request, $config);
        $old = (array) DB::table($config['table'])->where('company_id', $request->user()->company_id)->where('id', $id)->first();
        abort_unless($old, 404);

        DB::table($config['table'])->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);
        Audit::log($config['table'].'.deleted', $config['title'].' removido', $old);

        return back()->with('status', 'Registro removido.');
    }

    private function config(string $module): array
    {
        abort_unless(isset($this->modules[$module]), 404);
        return $this->modules[$module];
    }

    private function ensure(Request $request, array $config): void
    {
        abort_unless($request->user()->isAdmin() || $request->user()->hasPermission($config['permission']), 403);
    }

    private function payload(Request $request, array $config): array
    {
        $data = [];
        foreach (array_keys($config['fields']) as $field) {
            $data[$field] = $request->input($field);
        }

        foreach (['cost_price', 'sale_price', 'profit_margin', 'stock_current', 'stock_minimum', 'quantity', 'minimum_stock', 'cost', 'yield_quantity', 'estimated_cost', 'credit_limit', 'delivery_fee', 'amount', 'original_amount', 'paid_amount', 'pending_amount'] as $decimal) {
            if (array_key_exists($decimal, $data)) {
                $data[$decimal] = (float) str_replace(',', '.', (string) ($data[$decimal] ?: 0));
            }
        }

        if (array_key_exists('stock_current', $data)) {
            $data['quantity'] = $data['stock_current'];
        }
        if (array_key_exists('stock_minimum', $data)) {
            $data['minimum_stock'] = $data['stock_minimum'];
        }
        if (array_key_exists('cost_price', $data)) {
            $data['cost'] = $data['cost_price'];
        }

        if (isset($data['original_amount']) && !isset($data['pending_amount'])) {
            $data['pending_amount'] = $data['original_amount'] - ($data['paid_amount'] ?? 0);
        }

        return $data;
    }

    private function options(Request $request): array
    {
        $companyId = $request->user()->company_id;
        return [
            'category_id' => DB::table('categories')->where('company_id', $companyId)->whereNull('deleted_at')->pluck('name', 'id'),
            'brand_id' => DB::table('brands')->where('company_id', $companyId)->whereNull('deleted_at')->pluck('name', 'id'),
            'supplier_id' => DB::table('suppliers')->where('company_id', $companyId)->whereNull('deleted_at')->pluck('name', 'id'),
            'customer_id' => DB::table('customers')->where('company_id', $companyId)->whereNull('deleted_at')->pluck('name', 'id'),
            'product_id' => DB::table('products')->where('company_id', $companyId)->whereIn('product_type', ['ready_dish', 'combo'])->whereNull('deleted_at')->pluck('name', 'id'),
            'ingredient_product_id' => DB::table('products')->where('company_id', $companyId)->whereIn('product_type', ['ingredient', 'resale', 'drink', 'additional'])->whereNull('deleted_at')->pluck('name', 'id'),
            'recipe_id' => DB::table('recipes')->where('company_id', $companyId)->whereNull('deleted_at')->pluck('name', 'id'),
            'profile_id' => DB::table('profiles')->whereNull('deleted_at')->pluck('name', 'id'),
            'branch_id' => DB::table('branches')->where('company_id', $companyId)->whereNull('deleted_at')->pluck('name', 'id'),
            'merged_table_id' => DB::table('tables')->where('company_id', $companyId)->whereNull('deleted_at')->pluck('number', 'id'),
            'product_type' => collect([
                'resale' => 'Produto de revenda',
                'ingredient' => 'Ingrediente',
                'ready_dish' => 'Prato pronto',
                'drink' => 'Bebida',
                'combo' => 'Combo',
                'additional' => 'Adicional',
            ]),
            'print_sector' => collect([
                'kitchen' => 'Cozinha',
                'bar' => 'Bar',
                'cashier' => 'Caixa',
                'customer' => 'Comprovante do cliente',
            ]),
        ];
    }
}
