<?php

declare(strict_types=1);

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
        Schema::table('self_loan_repayments', function (Blueprint $table) {
            $table->index('self_loan_id');
            $table->index('paid_at');
            // Composite index for common query pattern: WHERE self_loan_id = ? ORDER BY paid_at
            $table->index(['self_loan_id', 'paid_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('self_loan_repayments', function (Blueprint $table) {
            $table->dropIndex(['self_loan_id']);
            $table->dropIndex(['paid_at']);
            $table->dropIndex(['self_loan_id', 'paid_at']);
        });
    }
};
