<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFactorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('factors', function (Blueprint $table) {
            $table->id();

            $table->string('state')->default('pending');
            
            $table->string('store_note')->default('');
            $table->string('user_note')->default('');

            $table->smallInteger('count')->default(1);
            
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('discount')->default(0);

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
        Schema::dropIfExists('factors');
    }
}
