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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('fullname')->unique();
            $table->string('shortcode', length:10)->unique();
            $table->string('flag')->nullable();
            $table->string('symbol')->nullable();
            $table->string('currency')->nullable();
            $table->boolean('banned')->default(0);
            $table->uuid('uid')->unique();
            $table->boolean('deleted')->default(0);
            $table->string('callcode');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
