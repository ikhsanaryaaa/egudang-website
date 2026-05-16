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
        Schema::create('inventory_batch_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_item_id')->constrained('stock_transaction_items')->onDelete('cascade');
            $table->foreignId('inventory_batch_id')->constrained('inventory_batches')->onDelete('cascade');
            $table->integer('qty_taken');
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_batch_usages');
    }
};
