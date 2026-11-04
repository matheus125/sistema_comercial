<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('print_sector')->default('kitchen')->index()->after('product_type');
            $table->boolean('print_on_production')->default(true)->after('print_sector');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('operation_mode')->default('counter')->index()->after('order_type');
            $table->string('service_status')->default('received')->index()->after('production_status');
            $table->timestamp('paid_at')->nullable()->after('delivered_at');
            $table->timestamp('cancelled_at')->nullable()->after('paid_at');
            $table->string('print_status')->default('pending')->index()->after('notes');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('print_sector')->default('kitchen')->index()->after('production_status');
            $table->timestamp('printed_at')->nullable()->after('print_sector');
            $table->timestamp('ready_at')->nullable()->after('printed_at');
            $table->timestamp('delivered_at')->nullable()->after('ready_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['print_sector', 'printed_at', 'ready_at', 'delivered_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['operation_mode', 'service_status', 'paid_at', 'cancelled_at', 'print_status']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['print_sector', 'print_on_production']);
        });
    }
};
