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
                'fieldtype' => 'text',
                'label' => 'Title',
                'possible_value' => null,
                'isrequired' => true,
                'description' => 'Title of the category',
                'order_no' => 1,
                'is_price_field' => false,
                'is_crypto_price_field' => false,
                'search_criteria' => true,
                'is_active' => true,
                'deleted' => false,
                'uid' => uniqid(),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'fieldtype' => 'textarea',
                'label' => 'Description',
                'possible_value' => null,
                'isrequired' => true,
                'description' => 'Description of the category',
                'order_no' => 2,
                'is_price_field' => false,
                'is_crypto_price_field' => false,
                'search_criteria' => true,
                'is_active' => true,
                'deleted' => false,
                'uid' => uniqid(),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'fieldtype' => 'select',
                'label' => 'Category Type',
                'possible_value' => 'Standard, Premium, VIP',
                'isrequired' => true,
                'description' => 'Type of the category',
                'order_no' => 3,
                'is_price_field' => false,
                'is_crypto_price_field' => false,
                'search_criteria' => true,
                'is_active' => true,
                'deleted' => false,
                'uid' => uniqid(),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'fieldtype' => 'number',
                'label' => 'Price',
                'possible_value' => null,
                'isrequired' => true,
                'description' => 'Price of the category',
                'order_no' => 4,
                'is_price_field' => true,
                'is_crypto_price_field' => false,
                'search_criteria' => true,
                'is_active' => true,
                'deleted' => false,
                'uid' => uniqid(),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'fieldtype' => 'checkbox',
                'label' => 'Is Featured',
                'possible_value' => null,
                'isrequired' => false,
                'description' => 'Is this category featured?',
                'order_no' => 5,
                'is_price_field' => false,
                'is_crypto_price_field' => false,
                'search_criteria' => false,
                'is_active' => true,
                'deleted' => false,
                'uid' => uniqid(),
                'created_at' => now(),
                'updated_at' => now()
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
