<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('module')->index();
            $table->timestamps();
        });

        Schema::create('profile_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['profile_id', 'permission_id']);
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('document', 32)->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('logo_path')->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('name');
            $table->string('document', 32)->nullable()->index();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('name');
            $table->string('status')->default('active')->index();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('name');
            $table->string('status')->default('active')->index();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('name');
            $table->string('document', 32)->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('observations')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('name');
            $table->string('document', 32)->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('delivery_reference')->nullable();
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->text('delivery_notes')->nullable();
            $table->date('birth_date')->nullable();
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->string('status')->default('active')->index();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->foreignId('brand_id')->nullable()->constrained('brands');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->string('internal_code')->index();
            $table->string('barcode')->nullable()->index();
            $table->string('name')->index();
            $table->string('product_type')->default('resale')->index();
            $table->string('unit')->default('UN');
            $table->string('unit_of_measure')->default('UN');
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->decimal('profit_margin', 8, 2)->default(0);
            $table->decimal('stock_current', 12, 3)->default(0);
            $table->decimal('stock_minimum', 12, 3)->default(0);
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('minimum_stock', 12, 3)->default(0);
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('lot')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->string('image_path')->nullable();
            $table->string('status')->default('active')->index();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('product_id')->constrained('products');
            $table->string('name');
            $table->decimal('yield_quantity', 12, 3)->default(1);
            $table->string('yield_unit')->default('UN');
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->text('preparation_notes')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('ingredient_product_id')->constrained('products');
            $table->decimal('quantity', 12, 3);
            $table->string('unit_of_measure')->default('UN');
            $table->decimal('cost', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->string('number')->index();
            $table->string('name')->nullable();
            $table->unsignedInteger('seats')->default(4);
            $table->string('status')->default('available')->index();
            $table->foreignId('merged_table_id')->nullable()->constrained('tables');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('opening_amount', 12, 2)->default(0);
            $table->decimal('expected_amount', 12, 2)->default(0);
            $table->decimal('informed_amount', 12, 2)->nullable();
            $table->decimal('difference_amount', 12, 2)->default(0);
            $table->json('payment_totals')->nullable();
            $table->string('status')->default('open')->index();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('table_id')->nullable()->constrained('tables');
            $table->foreignId('cash_register_id')->nullable()->constrained('cash_registers');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('waiter_id')->nullable()->constrained('users');
            $table->string('number')->unique();
            $table->string('order_type')->default('counter')->index();
            $table->string('command_code')->nullable()->index();
            $table->string('production_status')->default('pending')->index();
            $table->timestamp('sent_to_production_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('payment_type')->nullable()->index();
            $table->decimal('cash_received', 12, 2)->nullable();
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->string('status')->default('open')->index();
            $table->text('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->string('product_name');
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('production_status')->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->foreignId('cash_register_id')->nullable()->constrained('cash_registers');
            $table->string('type')->index();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('confirmed')->index();
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('quantity_before', 12, 3);
            $table->decimal('quantity_moved', 12, 3);
            $table->decimal('quantity_after', 12, 3);
            $table->string('type')->index();
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->foreignId('user_id')->constrained('users');
            $table->date('purchase_date');
            $table->string('document_number')->nullable()->index();
            $table->decimal('total', 12, 2)->default(0);
            $table->string('payment_type')->default('cash');
            $table->date('due_date')->nullable();
            $table->unsignedInteger('installments')->default(1);
            $table->string('status')->default('confirmed')->index();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 12, 3);
            $table->decimal('cost_price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('cash_register_id')->constrained('cash_registers');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->string('type')->index();
            $table->string('payment_type')->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('accounts_payable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->foreignId('purchase_id')->nullable()->constrained('purchases');
            $table->string('description');
            $table->string('expense_category')->nullable()->index();
            $table->decimal('amount', 12, 2);
            $table->date('due_date')->index();
            $table->date('paid_at')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('status')->default('open')->index();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('accounts_receivable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->string('description');
            $table->decimal('original_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('pending_amount', 12, 2);
            $table->date('due_date')->index();
            $table->string('status')->default('open')->index();
            $table->json('payment_history')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('user_id')->constrained('users');
            $table->string('status')->default('open')->index();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventory')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('system_quantity', 12, 3);
            $table->decimal('counted_quantity', 12, 3)->nullable();
            $table->decimal('divergence', 12, 3)->default(0);
            $table->boolean('adjusted')->default(false);
            $table->timestamps();
        });

        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->foreignId('user_id')->constrained('users');
            $table->string('type')->default('partial');
            $table->decimal('total', 12, 2)->default(0);
            $table->text('reason');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 12, 3);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->foreignId('user_id')->constrained('users');
            $table->text('reason');
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('type')->index();
            $table->string('title');
            $table->text('message');
            $table->string('severity')->default('info')->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies');
            $table->string('key')->index();
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'key']);
        });

        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('status')->default('success')->index();
            $table->text('message')->nullable();
            $table->timestamps();
        });

        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('action')->index();
            $table->string('auditable_type')->nullable()->index();
            $table->unsignedBigInteger('auditable_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'logs', 'backups', 'settings', 'notifications', 'cancellations', 'return_items', 'returns',
            'inventory_items', 'inventory', 'accounts_receivable', 'accounts_payable', 'cash_movements',
            'purchase_items', 'purchases', 'stock_movements', 'payments', 'order_items', 'orders',
            'cash_registers', 'tables', 'recipe_items', 'recipes', 'products', 'customers', 'suppliers', 'brands', 'categories', 'branches',
            'companies', 'profile_permissions', 'permissions', 'profiles',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
