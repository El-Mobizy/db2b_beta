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
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained('ads');
            $table->foreignId('order_detail_id')->constrained('order_details');
            $table->foreignId('buyer_id')->nullable()->constrained('clients');
            $table->foreignId('seller_id')->nullable()->constrained('clients');
            $table->foreignId('status_id')->constrained('type_of_types');
            $table->boolean('is_viewed_by_seller')->default(false);
            $table->string('ref', 100);
            $table->float('amount');
            $table->dateTime('enddate');
            $table->boolean('received_by_client')->nullable();
            $table->boolean('delivered_by_seller')->nullable();
            $table->boolean('admin_validate')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
