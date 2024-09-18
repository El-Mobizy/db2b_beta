<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('type_of_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('type_of_types')->onDelete('cascade')->nullable();
            $table->string('libelle');
            $table->string('codereference')->nullable();
            $table->boolean('deleted')->default(false);
            $table->string('uid')->unique();
            $table->timestamps();
        });

      

        DB::table('type_of_types')->insert([
            ['libelle' => 'Group A', 'created_at' => now(), 'updated_at' => now(),'uid'=>uniqid(), 'codereference'=>null,'parent_id'=>null],
            ['libelle' => 'Group B', 'created_at' => now(), 'updated_at' => now(),'uid'=>uniqid(), 'codereference'=>null,'parent_id'=>null],
            ['libelle' => 'Group C', 'created_at' => now(), 'updated_at' => now(),'uid'=>uniqid(), 'codereference'=>null,'parent_id'=>null],
            ['libelle' => 'STATUS','codereference' => 'STATUS', 'created_at' => now(), 'updated_at' => now(),'uid'=>uniqid(),'parent_id'=>null],
            ['libelle' => 'validated','codereference' => 'STATUS_VALIDATED', 'created_at' => now(), 'updated_at' => now(),'uid'=>uniqid(),'parent_id'=>4],
            ['libelle' => 'pending','codereference' => 'STATUS_PENDING', 'created_at' => now(), 'updated_at' => now(),'uid'=>uniqid(),'parent_id'=>4],
            ['libelle' => 'rejected','codereference' => 'STATUS_REJECTED', 'created_at' => now(), 'updated_at' => now(),'uid'=>uniqid(),'parent_id'=>4],
            ['libelle' => 'incomplete','codereference' => 'STATUS_DRAFT', 'created_at' => now(), 'updated_at' => now(),'uid'=>uniqid(),'parent_id'=>4],
            ['libelle' => 'canceled','codereference' => 'STATUS_CANCELED', 'created_at' => now(), 'updated_at' => now(),'uid'=>uniqid(),'parent_id'=>null],
            ['libelle' => 'paid','codereference' => 'STATUS_paid', 'created_at' => now(), 'updated_at' => now(),'uid'=>uniqid(),'parent_id'=>null],
            ['libelle' => 'partially_released','codereference' => 'STATUS_PARTIALLY_RELEASED', 'created_at' => now(), 'updated_at' => now(),'uid'=>uniqid(),'parent_id'=>null],
            ['libelle' => 'endtrade','codereference' => 'STATUS_ENDTRADE', 'created_at' => now(), 'updated_at' => now(),'uid'=>uniqid(),'parent_id'=>null],
            ['libelle' => 'canceltrade','codereference' => 'STATUS_CANCEL_TRADE', 'created_at' => now(), 'updated_at' => now(),'uid'=>uniqid(),'parent_id'=>null],
            ['libelle' => 'started','codereference' => 'STATUS_STARTED', 'created_at' => now(), 'updated_at' => now(),'uid'=>uniqid(),'parent_id'=>null],
          
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('type_of_types');
    }
};
