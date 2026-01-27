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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // USD, SYP, TRY
            $table->string('name');
            $table->string('symbol')->nullable();
            $table->decimal('exchange_rate', 15, 2)->default(1.00); // Exchange rate against USD
            $table->boolean('is_default')->default(false); // USD is default
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_updated_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('app_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('currency_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->decimal('old_rate', 15, 2);
            $table->decimal('new_rate', 15, 2);
            $table->foreignId('updated_by')->nullable()->constrained('app_users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_histories');
        Schema::dropIfExists('currencies');
    }
};
