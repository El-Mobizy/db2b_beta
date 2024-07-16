<?php

use App\Models\TypeOfType;
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
            $table->foreignId('statut')->nullable()->constrained('type_of_types')->default(TypeOfType::whereLibelle('pending')->first()->id);
            $table->foreignId('validated_by_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamp('validated_on')->nullable();
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
