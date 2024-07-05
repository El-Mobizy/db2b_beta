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
        Schema::create('preorder_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('preorder_id')->constrained('preorders');
            $table->foreignId('parent_id')->nullable()->constrained('preorder_answers');
            $table->boolean('deleted')->default(0);
            $table->text('reject_reason')->nullable();
            $table->string('filecode')->nullable();
            $table->text('content');
            $table->foreignId('statut')->constrained('type_of_types')->nullable();
            $table->foreignId('validated_by_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamp('validated_on')->nullable();
            $table->uuid('uid')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preorder_answers');
    }
};
