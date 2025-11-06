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
        Schema::table('debts', function (Blueprint $table) {
            $table->enum('type', ['forbrukslån', 'kredittkort'])->default('kredittkort')->after('name');
        });

        // Backfill existing debts: set type to 'kredittkort' and calculate proper minimum_payment
        DB::table('debts')->get()->each(function ($debt) {
            $minimumPayment = $debt->minimum_payment;

            // If minimum_payment is null or 0, calculate it for kredittkort
            if (! $minimumPayment || $minimumPayment == 0) {
                $minimumPayment = max($debt->balance * 0.03, 300);
            }

            DB::table('debts')
                ->where('id', $debt->id)
                ->update([
                    'type' => 'kredittkort',
                    'minimum_payment' => $minimumPayment,
                ]);
        });

        // Change minimum_payment to NOT NULL with default 0
        Schema::table('debts', function (Blueprint $table) {
            $table->decimal('minimum_payment', 10, 2)->default(0)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->decimal('minimum_payment', 10, 2)->nullable()->change();
            $table->dropColumn('type');
        });
    }
};
