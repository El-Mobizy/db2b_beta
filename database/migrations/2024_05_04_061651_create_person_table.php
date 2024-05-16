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
        Schema::create('person', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('country_id')->constrained('countries')->onDelete('cascade');
            $table->boolean('connected')->default(0);
            $table->boolean('sex')->default(0);
            $table->date('dateofbirth')->nullable();
            $table->string('profile_img_code')->nullable();
            $table->boolean('first_login')->default(0);
            $table->string('phonenumber')->nullable();
            $table->boolean('deleted')->default(0);
            $table->uuid('uid')->unique();
            $table->string('type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
