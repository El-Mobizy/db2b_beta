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
        Schema::create('payements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->foreignId('commission_wallet_id')->nullable()->constrained('commission_wallets')->onDelete('cascade');
            $table->string('transaction_id')->unique()->nullable();
            $table->string('payement_type')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('statut')->nullable();
            $table->text('motif')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payements');
    }
};
