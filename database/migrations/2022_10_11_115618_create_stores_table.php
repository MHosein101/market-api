<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug');
            $table->string('economic_code')->default('');
            
            $table->string('owner_full_name');
            $table->string('owner_phone_number');
            $table->string('second_phone_number')->default('');
            
            $table->string('province');
            $table->string('city');

            $table->string('office_address');
            $table->string('office_number');

            $table->string('warehouse_address')->default('');
            $table->string('warehouse_number')->default('');

            $table->smallInteger('minimum_shopping_count')->default(0);
            $table->string('minimum_shopping_unit')->default('');

            $table->string('bank_name')->default('');
            $table->string('bank_code')->default('');
            $table->string('bank_card_number')->default('');
            $table->string('bank_sheba_number')->default('');
            
            $table->integer('admin_confirmed')->default(-1);

            $table->string('license_image')->default('');
            $table->string('logo_image')->default('');
            $table->string('banner_image')->default('');
            
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
        Schema::dropIfExists('stores');
    }
}
