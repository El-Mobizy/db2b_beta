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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sentBy')->constrained('person')->onDelete('cascade');
            $table->foreignId('sentTo')->constrained('person')->onDelete('cascade');
            $table->foreignId('subject')->nullable()->constrained('ads')->onDelete('set null');
            $table->boolean('isSpam')->default(false);
            $table->boolean('isArchived')->default(false);
            $table->boolean('isRead')->default(false);
            $table->text('lastMessage')->nullable();
            $table->boolean('isTradeChat')->default(false)->nullable();
            $table->boolean('deleted')->default(false);
            $table->uuid('uid')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
