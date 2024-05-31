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
        Schema::table('preorder_answers', function (Blueprint $table) {
            $table->double('price')->nullable();
            $table->integer('delivery_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preorders_answers', function (Blueprint $table) {
            //
        });
    }
};
