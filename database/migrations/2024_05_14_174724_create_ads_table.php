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
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status');
            $table->string('file_code')->unique();
            $table->text('reject_reason')->nullable();
            $table->timestamp('validated_on')->nullable();
            $table->boolean('deleted')->default(false);
            $table->string('uid')->unique();
            $table->foreignId('category_id')->references('id')->on('categories');
            $table->foreignId('owner_id')->references('id')->on('users');
            $table->foreignId('location_id')->references('id')->on('countries');
            $table->foreignId('validated_by_id')->references('id')->on('users')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
