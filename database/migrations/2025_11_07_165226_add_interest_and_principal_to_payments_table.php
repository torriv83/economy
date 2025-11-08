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
            $table->decimal('interest_paid', 10, 2)->default(0)->after('actual_amount');
            $table->decimal('principal_paid', 10, 2)->default(0)->after('interest_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['interest_paid', 'principal_paid']);
        });
    }
};
