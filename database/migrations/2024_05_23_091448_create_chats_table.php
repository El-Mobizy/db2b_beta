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
            $table->foreignId('sent_by_id')->constrained('person')->onDelete('cascade');
            $table->foreignId('sent_to_id')->constrained('person')->onDelete('cascade');
            $table->foreignId('subject_id')->nullable()->constrained('ads')->onDelete('set null');
            $table->boolean('is_spam')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_read')->default(false);
            $table->text('last_message')->nullable();
            $table->boolean('is_trade_chat')->default(false);
            $table->timestamps();
            $table->uuid('uid')->unique();
            $table->boolean('deleted')->default(false);
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
