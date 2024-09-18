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
        DB::table('type_of_types')->insert([
            'libelle' => 'limitOfCategory',
            'codereference' => 3,
            'created_at' => now(),
            'updated_at' => now(),
            'uid' => uniqid(),
            'parent_id' => null,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('type_of_types')
            ->where('libelle', 'limitOfCategory')
            ->where('codereference', 3)
            ->delete();
    }
};
