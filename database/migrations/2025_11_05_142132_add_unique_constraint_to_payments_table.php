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
            // Drop the existing index before adding unique constraint
            $table->dropIndex(['debt_id', 'month_number']);

            // Add unique constraint to prevent duplicate payments for same debt in same month
            $table->unique(['debt_id', 'month_number'], 'payments_debt_month_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('payments_debt_month_unique');

            // Re-add the original index
            $table->index(['debt_id', 'month_number']);
        });
    }
};
