<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('general_trades', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->nullable()->unique();
            $table->boolean('deleted')->default(false)->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade');
            $table->foreignId('person_id')->nullable()->constrained('person')->onDelete('cascade');
            $table->foreignId('trade_id')->nullable()->constrained('trades')->onDelete('cascade');
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_trades');
    }
};
