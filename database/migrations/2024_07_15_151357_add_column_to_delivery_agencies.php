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
        Schema::table('delivery_agencies', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('delivery_agencies');
            $table->string('address')->nullable();
            $table->string('file_reference_code')->unique()->nullable();
            $table->string('company_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_agencies', function (Blueprint $table) {
            //
        });
    }
};
