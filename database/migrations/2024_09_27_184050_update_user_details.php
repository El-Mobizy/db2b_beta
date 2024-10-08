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
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
            
            $table->foreignId('address_id')->nullable()->constrained('addresses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->decimal('latitude', 10, 9)->unique()->nullable();
            $table->decimal('longitude', 10, 9)->unique()->nullable();

            $table->dropForeign(['address_id']);
            $table->dropColumn('address_id');
        });
    }
};
