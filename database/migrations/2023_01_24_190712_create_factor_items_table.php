<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFactorItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('factor_items', function (Blueprint $table) {
            $table->id();
            
            $table->string('state')->default('pending');
            
            $table->string('store_note')->default('');
            $table->string('user_note')->default('');
            
            $table->smallInteger('count')->default(1);
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('discount')->default(0);

            $table->unsignedBigInteger('factor_id');
            $table->unsignedBigInteger('store_product_id');
            $table->unsignedBigInteger('base_product_id');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('factor_items');
    }
}
