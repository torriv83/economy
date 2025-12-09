<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL requires dropping foreign key before dropping an index it uses
        if (DB::getDriverName() === 'mysql') {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['debt_id']);
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            // Drop the existing index before adding unique constraint
            $table->dropIndex(['debt_id', 'month_number']);

            // Add unique constraint to prevent duplicate payments for same debt in same month
            $table->unique(['debt_id', 'month_number'], 'payments_debt_month_unique');
        });

        // Re-add foreign key for MySQL (it will use the unique constraint as its index)
        if (DB::getDriverName() === 'mysql') {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreign('debt_id')->references('id')->on('debts')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['debt_id']);
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('payments_debt_month_unique');

            // Re-add the original index
            $table->index(['debt_id', 'month_number']);
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreign('debt_id')->references('id')->on('debts')->onDelete('cascade');
            });
        }
    }
};
