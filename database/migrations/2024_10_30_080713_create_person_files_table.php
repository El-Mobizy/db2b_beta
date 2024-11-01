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
        Schema::create('person_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_type_id')->constrained('file_types')->onDelete('cascade');
            $table->foreignId('person_id')->constrained('person')->onDelete('cascade');
            $table->string('filecode')->unique();
            $table->uuid('uid')->unique();
            $table->boolean('deleted')->default(false);
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamp('validated_on')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('person_files');
    }
};
