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
        Schema::create('eoq_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->date('calculation_date');
            $table->string('period_label');
            $table->string('period_type')->default('bulanan'); // bulanan | tahunan
            $table->integer('demand');
            $table->decimal('ordering_cost', 15, 2)->default(0);
            $table->decimal('holding_cost', 15, 2)->default(0);
            $table->integer('lead_time_days')->default(0);
            $table->decimal('eoq', 15, 2)->default(0);
            $table->decimal('rop', 15, 2)->default(0);
            $table->decimal('order_frequency', 10, 2)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eoq_calculations');
    }
};
