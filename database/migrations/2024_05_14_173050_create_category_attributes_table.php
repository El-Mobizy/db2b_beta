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
        Schema::create('category_attributes', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('category_id')->references('id')->on('categories');
            $table->string('fieldtype');
            $table->string('label');
            $table->text('possible_value')->nullable();
            $table->boolean('isrequired')->default(0);
            $table->text('description')->nullable();
            $table->integer('order_no');
            $table->boolean('is_price_field')->default(0);
            $table->boolean('is_crypto_price_field')->default(0);
            $table->boolean('search_criteria')->default(0);
            $table->boolean('is_active')->default(0);
            $table->boolean('deleted')->default(0);
            $table->string('uid')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_attributes');
    }
};
