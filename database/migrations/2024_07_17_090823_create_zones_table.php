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
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('city_name')->unique();
            $table->decimal('latitude', 10, 9)->unique()->nullable();
            $table->decimal('longitude', 10, 9)->unique()->nullable();
            $table->foreignId('country_id')->references('id')->on('countries')->onDelete('cascade');
            $table->boolean('active')->default(true);
            $table->boolean('deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
