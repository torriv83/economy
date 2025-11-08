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
        Schema::table('payments', function (Blueprint $table) {
            // Make month_number nullable to allow reconciliation adjustments with NULL month_number
            // This prevents collision between reconciliation adjustments and regular monthly payments
            $table->integer('month_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Revert month_number back to NOT NULL
            $table->integer('month_number')->nullable(false)->change();
        });
    }
};
