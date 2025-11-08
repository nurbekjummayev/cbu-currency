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
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained();
            $table->date('date')->index();
            $table->date('currency_date');
            $table->decimal('rate', 15, 4);
            $table->decimal('diff', 15, 4);
            $table->integer('nominal');
            $table->timestamps();

            // Indexes
            $table->index(['currency_id', 'date']); // For fast lookups

            // Unique constraint to prevent duplicate rates
            $table->unique(['currency_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};
