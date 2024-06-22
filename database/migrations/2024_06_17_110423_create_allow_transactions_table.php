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
        Schema::create('allow_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid');
            $table->foreignId('validated_by_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamp('validated_on')->nullable();
            $table->boolean('deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allow_transactions');
    }
};
