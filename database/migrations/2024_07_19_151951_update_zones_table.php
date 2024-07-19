<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateZonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('zones', function (Blueprint $table) {
            
            $table->dropColumn('city_name');
            $table->dropForeign(['country_id']);
            $table->dropColumn('country_id');

            
            $table->foreignId('delivery_agency_id')->constrained('delivery_agencies')->onDelete('cascade');

            
            $table->decimal('latitude', 10, 9)->nullable()->change();
            $table->decimal('longitude', 10, 9)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
     {
         Schema::table('zones', function (Blueprint $table) {

             $table->string('city_name')->unique();
             $table->foreignId('country_id')->constrained('countries')->onDelete('cascade');

             $table->decimal('latitude', 10, 9)->unique()->change();
             $table->decimal('longitude', 10, 9)->unique()->change();

           
             $table->dropForeign(['delivery_agency_id']);
             $table->dropColumn('delivery_agency_id');
         });
     }
}
