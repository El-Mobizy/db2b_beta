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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->foreignId('reciever')->constrained('person')->onDelete('cascade');
            $table->foreignId('sender')->constrained('person')->onDelete('cascade');
            $table->foreignId('chat')->nullable()->constrained('chats')->onDelete('set null');
            $table->foreignId('tradeChats')->nullable()->constrained('trade_chats')->onDelete('set null');
            $table->boolean('isRead')->default(false)->nullable();
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
        Schema::dropIfExists('chat_messages');
    }
};
