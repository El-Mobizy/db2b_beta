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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person')->constrained('person')->onDelete('cascade');
            $table->string('object');
            $table->text('description');
            $table->boolean('isRead')->default(false);
            $table->boolean('isGeneral')->default(false);
            $table->string('icon')->nullable();
            $table->dateTime('startDate')->nullable();
            $table->dateTime('endDate')->nullable();
            $table->boolean('application')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
