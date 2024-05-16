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
        Schema::create('delivery_agencies', function (Blueprint $table) {
            $table->id();
            $table->enum('agent_type', ['company', 'individual']);
            $table->foreignId('person_id')->references('id')->on('person');
            $table->uuid('uid');
            $table->boolean('deleted')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_agencies');
    }
};
