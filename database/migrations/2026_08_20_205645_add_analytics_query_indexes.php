<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS order_raws_store_date_idx ON order_raws (store_id, order_date)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS order_raws_store_nm_date_idx ON order_raws (store_id, nm_id, order_date)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS sale_raws_store_date_idx ON sale_raws (store_id, sale_date)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS sale_raws_store_nm_date_idx ON sale_raws (store_id, nm_id, sale_date)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS product_analytics_store_date_idx ON product_analytics (store_id, date)');
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS warehouse_stocks_product_id_idx ON warehouse_stocks (product_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS order_raws_store_date_idx');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS order_raws_store_nm_date_idx');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS sale_raws_store_date_idx');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS sale_raws_store_nm_date_idx');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS product_analytics_store_date_idx');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS warehouse_stocks_product_id_idx');
    }
};
