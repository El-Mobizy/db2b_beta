<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('preorders', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('filecode');
            $table->text('description');
            $table->float('price')->nullable();
            $table->foreignId('statut')->constrained('type_of_types');
            $table->foreignId('user_id')->constrained('users');
            $table->string('address')->nullable();
            $table->text('reject_reason')->nullable();
            $table->foreignId('location_id')->constrained('countries');
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('validated_by_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamp('validated_on')->nullable();
            $table->boolean('deleted')->default(0);
            $table->string('uid')->unique();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE preorders ADD CONSTRAINT your_column_length CHECK (char_length(description) <= 3000)');
    }

    public function down(): void
    {
        Schema::table('your_table', function (Blueprint $table) {
            DB::statement('ALTER TABLE preorders DROP CONSTRAINT your_column_length');
        });
        Schema::dropIfExists('preorders');
    }
};
