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
        Schema::create('category_attributes', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('category_id')->references('id')->on('categories');
            $table->string('fieldtype');
            $table->string('label');
            $table->text('possible_value')->nullable();
            $table->boolean('isrequired')->default(0);
            $table->text('description')->nullable();
            $table->integer('order_no');
            $table->boolean('is_price_field')->default(0);
            $table->boolean('is_crypto_price_field')->default(0);
            $table->boolean('search_criteria')->default(0);
            $table->boolean('is_active')->default(0);
            $table->boolean('deleted')->default(0);
            $table->string('uid')->unique();
            $table->timestamps();
        });

        DB::table('category_attributes')->insert([
            [
                'fieldtype' => 'radio',
                'label' => 'Gender',
                'possible_value' => 'Man,Woman,',
                'isrequired' => true,
                'description' => 'Choisissez une couleur pour le produit',
                'order_no' => 6,
                'is_price_field' => false,
                'is_crypto_price_field' => false,
                'search_criteria' => false,
                'is_active' => true,
                'deleted' => false,
                'uid' => '0c7cc7f8-b1de-40e4-8c53-e190ba99cd0d',
                'created_at' => '2024-10-03 10:18:21',
                'updated_at' => '2024-10-03 10:18:21'
            ],
            [
                'fieldtype' => 'text',
                'label' => 'Couleur',
                'possible_value' => null,
                'isrequired' => true,
                'description' => 'Couleur du produit',
                'order_no' => 1,
                'is_price_field' => false,
                'is_crypto_price_field' => false,
                'search_criteria' => true,
                'is_active' => true,
                'deleted' => false,
                'uid' => '3e2f91d4-39d4-45a4-95ff-1b58f49b301d',
                'created_at' => '2024-06-28 16:17:25',
                'updated_at' => '2024-06-28 16:17:25'
            ],
            [
                'fieldtype' => 'select',
                'label' => 'Taille',
                'possible_value' => 'XS, S, M, L, XL, XXL',
                'isrequired' => true,
                'description' => 'Taille du produit',
                'order_no' => 2,
                'is_price_field' => false,
                'is_crypto_price_field' => false,
                'search_criteria' => true,
                'is_active' => true,
                'deleted' => false,
                'uid' => '9d3b037e-5cb0-4f9a-bc3d-84cc6e41ae63',
                'created_at' => '2024-06-28 16:17:25',
                'updated_at' => '2024-06-28 16:17:25'
            ],
            [
                'fieldtype' => 'number',
                'label' => 'Poids',
                'possible_value' => null,
                'isrequired' => false,
                'description' => 'Poids du produit en grammes',
                'order_no' => 3,
                'is_price_field' => false,
                'is_crypto_price_field' => false,
                'search_criteria' => true,
                'is_active' => true,
                'deleted' => false,
                'uid' => '38aeff82-5c56-4aa6-9d36-1d1fdfb3c02c',
                'created_at' => '2024-06-28 16:17:25',
                'updated_at' => '2024-06-28 16:17:25'
            ],
            [
                'fieldtype' => 'select',
                'label' => 'Type de produit',
                'possible_value' => 'Standard, Premium, Luxe',
                'isrequired' => true,
                'description' => 'Type de produit',
                'order_no' => 5,
                'is_price_field' => false,
                'is_crypto_price_field' => false,
                'search_criteria' => true,
                'is_active' => true,
                'deleted' => false,
                'uid' => '7b8be557-49fc-45ec-b4c4-b1674ef53f01',
                'created_at' => '2024-06-28 16:17:25',
                'updated_at' => '2024-06-28 16:17:25'
            ],
            [
                'fieldtype' => 'checkbox',
                'label' => 'target',
                'possible_value' => 'child, man, woman',
                'isrequired' => false,
                'description' => 'Indique si le produit est en stock',
                'order_no' => 4,
                'is_price_field' => false,
                'is_crypto_price_field' => false,
                'search_criteria' => false,
                'is_active' => true,
                'deleted' => false,
                'uid' => 'c79faef1-21da-4be2-bc5c-ec5ec4fc4d95',
                'created_at' => '2024-06-28 16:17:25',
                'updated_at' => '2024-06-28 16:17:25'
            ]
        ]);
        
    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_attributes');
    }
};
