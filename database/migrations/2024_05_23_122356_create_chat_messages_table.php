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
            $table->foreignId('reciever_id')->constrained('person')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('person')->onDelete('cascade')->nullable();
            $table->foreignId('chat_id')->nullable()->constrained('chats')->onDelete('cascade');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_trade_chat')->default(false);
            // $table->foreignId('trade_chat_id')->nullable()->constrained('trade_chats')->onDelete('set null');
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
