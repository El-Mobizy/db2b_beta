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
        Schema::create('escrow_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('person_uid')->references('uid')->on('person')->onDelete('cascade');
            $table->uuid('order_uid')->references('uid')->on('orders')->onDelete('cascade');
            $table->decimal('delivery_agent_amount', 8, 2);
            $table->decimal('order_amount', 8, 2);
            $table->string('status');
            $table->dateTime('pickup_date')->nullable();
            $table->dateTime('delivery_date')->nullable();
            $table->timestamps();
        });
    }

    // $table->foreignId('vpdocument_id')->references('id')->on('verification_document_partenaires')->onDelete('cascade');

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escrow_deliveries');
    }
};
