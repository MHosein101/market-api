<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoiceItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->string('state')->default('pending');
            
            $table->string('store_comment')->default('');
            $table->string('user_comment')->default('');
            
            $table->smallInteger('count');
            $table->unsignedInteger('price');
            $table->unsignedInteger('discount');
            
            $table->unsignedInteger('tax')->default(0);

            $table->unsignedBigInteger('invoice_id');

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
        Schema::dropIfExists('invoice_items');
    }
}
