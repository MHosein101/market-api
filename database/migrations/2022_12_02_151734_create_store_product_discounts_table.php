<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreProductDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_product_discounts', function (Blueprint $table) {
            $table->id();

            $table->string('discount_type');
            $table->unsignedBigInteger('discount_value');
            $table->unsignedBigInteger('final_price');

            $table->unsignedBigInteger('product_id');

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
        Schema::dropIfExists('store_product_discounts');
    }
}
