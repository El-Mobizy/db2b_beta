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

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid');
            $table->string('name', 255);
            $table->string('short', 55);
            $table->boolean('is_transfer')->default(false);
            $table->boolean('is_wthdraw')->default(true);
            $table->boolean('is_exchange')->default(false);
            $table->boolean('is_purchase')->default(false);
            $table->boolean('is_exchange_receive')->default(false);
            $table->boolean('is_visible_to_client')->default(false);
            $table->string('ref_code', 20)->nullable();
            $table->boolean('deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
