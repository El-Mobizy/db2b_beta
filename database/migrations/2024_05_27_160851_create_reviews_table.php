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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            // $table->foreignId('preorder_answer_id')->nullable()->constrained('preorder_answers');
            $table->uuid('preorder_answer_uid')->references('uid')->on('preorder_answers');
            $table->foreignId('parent_id')->nullable()->constrained('reviews');
            $table->text('content');
            $table->string('filecode')->nullable();
            $table->boolean('deleted')->default(0);
            $table->uuid('uid')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
