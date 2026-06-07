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
        Schema::table('orders', function (Blueprint $table) {
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('unpaid', 'paid', 'partial_paid_due', 'failed', 'refunded') DEFAULT 'unpaid'");
        });
    }

    /**'paid', 'unpaid', 'partial_paid_due', 'failed', 'refunded'
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('unpaid', 'paid', 'partial_paid_due', 'failed', 'refunded') DEFAULT 'unpaid'");
        });
    }
};
