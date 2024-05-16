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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug');
            $table->integer('total_ads')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->foreignId('link_category_id')->nullable()->constrained('categories')->onDelete('cascade')->nullable();
            $table->bigInteger('attribute_group_id')->nullable()->constrained('attribute_groups', 'group_title_id')->onDelete('cascade');
            $table->boolean('deleted')->default(0);
            $table->boolean('is_top')->default(0);
            $table->string('filecode')->nullable();
            $table->uuid('uid')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
