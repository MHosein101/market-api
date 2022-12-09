<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_products', function (Blueprint $table) {
            $table->id();
            
            $table->string('production_date')->default('');
            $table->string('expire_date')->default('');
            
            $table->unsignedInteger('production_price')->default(0);
            $table->unsignedInteger('consumer_price');
            $table->unsignedInteger('store_price');
            
            $table->unsignedInteger('store_price_1')->default(0);
            $table->unsignedInteger('store_price_2')->default(0);

            $table->integer('price_update_time');

            $table->smallInteger('per_unit')->default(1);
            $table->smallInteger('warehouse_count')->default(0);

            $table->string('delivery_description')->default('');
            $table->string('store_note')->default('');
            
            $table->tinyInteger('cash_payment_discount')->default(0);

            $table->tinyInteger('commission')->default(0);

            $table->integer('admin_confirmed')->default(-1);
            
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('store_id');

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
        Schema::dropIfExists('store_products');
    }
}
