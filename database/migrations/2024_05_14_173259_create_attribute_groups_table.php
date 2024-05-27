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
        Schema::create('attribute_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->references('id')->on('category_attributes')->nullable();
            $table->foreignId('group_title_id')->references('id')->on('type_of_types')->nullable();
            $table->boolean('deleted')->default(false);
            $table->string('uid')->unique();
            $table->timestamps();
        });



        DB::table('attribute_groups')->insert([
            [
                'attribute_id' => 1,
                'group_title_id' => 1,
                'deleted' => false,
                'uid' => uniqid(),
                'created_at' => now(),
                'updated_at'=>now()
            ],
            [
                'attribute_id' => 2,
                'group_title_id' => 1,
                'deleted' => false,
                'uid' => uniqid(),
                'created_at' => now(),
                'updated_at'=>now()
            ],
            [
                'attribute_id' => 1,
                'group_title_id' => 2,
                'deleted' => false,
                'uid' => uniqid(),
                'created_at' => now(),
                'updated_at'=>now()
            ],
            [
                'attribute_id' => 2,
                'group_title_id' => 2,
                'deleted' => false,
                'uid' => uniqid(),
                'created_at' => now(),
                'updated_at'=>now()
            ],
        ]);
       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_groups');
    }
};
