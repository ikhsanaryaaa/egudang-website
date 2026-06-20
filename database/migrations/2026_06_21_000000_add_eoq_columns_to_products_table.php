<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('ordering_cost', 15, 2)->default(0)->after('minimum_stock');
            $table->decimal('holding_cost', 15, 2)->default(0)->after('ordering_cost');
            $table->integer('lead_time_days')->default(0)->after('holding_cost');
            $table->integer('safety_stock_days')->default(0)->after('lead_time_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'ordering_cost',
                'holding_cost',
                'lead_time_days',
                'safety_stock_days',
            ]);
        });
    }
};
