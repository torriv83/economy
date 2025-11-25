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
        Schema::create('payoff_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('extra_payment', 10, 2)->default(2000.00);
            $table->string('strategy', 20)->default('avalanche');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payoff_settings');
    }
};
