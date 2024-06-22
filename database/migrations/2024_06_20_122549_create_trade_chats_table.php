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
        Schema::create('trade_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sentBy')->constrained('clients');
            $table->foreignId('sentTo')->constrained('clients');
            $table->foreignId('trade_id')->constrained('trades');
            $table->boolean('isSpam')->default(false);
            $table->boolean('isArchived')->default(false);
            $table->boolean('isRead')->default(false);
            $table->text('lastMessage');
            $table->boolean('deleted')->default(false);
            $table->uuid('uid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_chats');
    }
};
