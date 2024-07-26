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
        Schema::create('otp_password_forgottens', function (Blueprint $table) {
            $table->id();
            $table->integer('code_otp');
            $table->foreignId('user_id')->constrained('users');
            $table->boolean('deleted')->default(false);
            $table->dateTime('expired_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_password_forgottens');
    }
};
