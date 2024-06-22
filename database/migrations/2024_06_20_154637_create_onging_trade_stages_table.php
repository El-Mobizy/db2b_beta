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
        Schema::create('onging_trade_stages', function (Blueprint $table) {
            $table->id();
            $table->string('stage_title', 255);
            $table->foreignId('next_step_id')->nullable()->constrained('trade_stages');
            $table->foreignId('previous_step_id')->nullable()->constrained('trade_stages');
            $table->integer('steporder');
            $table->string('yes_action', 100);
            $table->string('no_action', 100);
            $table->string('action_done_by', 20)->nullable();
            $table->foreignId('trade_id')->constrained('trades');
            $table->boolean('complete')->default(false);
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
        Schema::dropIfExists('onging_trade_stages');
    }
};
