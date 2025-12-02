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
        Schema::table('self_loans', function (Blueprint $table) {
            $table->string('ynab_account_id')->nullable()->after('current_balance');
            $table->string('ynab_category_id')->nullable()->after('ynab_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('self_loans', function (Blueprint $table) {
            $table->dropColumn(['ynab_account_id', 'ynab_category_id']);
        });
    }
};
