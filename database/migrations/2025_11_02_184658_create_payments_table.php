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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_id')->constrained()->onDelete('cascade');
            $table->decimal('planned_amount', 10, 2);
            $table->decimal('actual_amount', 10, 2);
            $table->date('payment_date');
            $table->integer('month_number');
            $table->string('payment_month'); // YYYY-MM format
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['debt_id', 'month_number']);
            $table->index('payment_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
