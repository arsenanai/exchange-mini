<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('symbol', 10);
            $table->enum('side', ['buy', 'sell']);
            $table->decimal('price', 18, 8);
            $table->decimal('amount', 18, 8);
            $table->unsignedTinyInteger('status')->default(1); // 1=open,2=filled,3=cancelled
            $table->decimal('locked_usd', 18, 8)->default(0);
            $table->decimal('locked_asset', 18, 8)->default(0);
            $table->timestamps();

            $table->index(['symbol', 'side', 'status', 'price', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
