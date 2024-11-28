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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('name')->nullable()->unique();
            $table->text('value')->nullable();
            $table->string('type')->nullable();
            $table->boolean('deleted')->default(false);
            $table->timestamps();
        });


DB::table('settings')->insert([
    [
        'uid' => '93be6ebe-ac01-11ef-b532-00ff5210c7f1',
        'name' => 'limitOfAdInStore',
        'value' => 30,
        'type' => 'integer',
        'deleted' => false,
        'created_at' =>now(),
        'updated_at' =>now(),
    ],
    [
        'uid' => 'a12cd8d8-ac01-11ef-89a0-00ff5210c7f1',
        'name' => 'limitOfCategory',
        'value' => 3,
        'type' => 'integer',
        'deleted' => false,
        'created_at' =>now(),
        'updated_at' =>now(),
    ],
]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
