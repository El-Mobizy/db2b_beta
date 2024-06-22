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
            Schema::create('commission_wallets', function (Blueprint $table) {
                $table->id();
                $table->uuid('uid');
                $table->decimal('balance', 17, 2);
                $table->decimal('prev_balance', 17, 2);
                $table->foreignId('commission_id')->constrained()->onDelete('cascade');
                $table->foreignId('person_id')->constrained('person')->onDelete('cascade');
                $table->boolean('deleted')->default(false);
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_wallets');
    }
};
