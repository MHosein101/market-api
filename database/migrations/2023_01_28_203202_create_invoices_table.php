<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->string('payment_type')->default('');

            $table->string('state')->default('');
            
            $table->string('store_comment')->default('');
            $table->string('user_comment')->default('');

            $table->unsignedInteger('tracking_number')->default(0);
            $table->unsignedInteger('bill_number')->default(0);
            
            $table->smallInteger('items_count');
            
            $table->unsignedInteger('total_price');
            $table->unsignedInteger('total_discount');
            $table->unsignedInteger('total_tax')->default(0);

            $table->integer('delivery_time')->default(0);
            $table->integer('billed_date')->default(0);

            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('user_id');

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
        Schema::dropIfExists('invoices');
    }
}
