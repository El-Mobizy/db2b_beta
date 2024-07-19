<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDeliveryAgentZonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('delivery_agent_zones', function (Blueprint $table) {
            // Supprimer la colonne delivery_agency_id
            $table->dropForeign(['delivery_agency_id']);
            $table->dropColumn('delivery_agency_id');

            // Ajouter les nouvelles colonnes
            $table->decimal('latitude', 10, 9);
            $table->decimal('longitude', 10, 9);
            $table->integer('point_order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('delivery_agent_zones', function (Blueprint $table) {
            
            $table->foreignId('delivery_agency_id')->constrained()->onDelete('cascade');

           
            $table->dropColumn(['latitude', 'longitude', 'point_order']);
        });
    }
}
