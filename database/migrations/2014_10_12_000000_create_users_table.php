<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('account_type');

            $table->string('profile_image')->default('');
            $table->string('full_name');
            $table->string('national_code')->default('');

            $table->string('phone_number_primary');
            $table->string('phone_number_secondary')->default('');
            $table->string('house_number')->default('');

            $table->string('password')->nullable();

            $table->string('verification_code')->nullable();
            // $table->rememberToken();
            $table->unsignedBigInteger('store_id')->nullable();
            
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
        Schema::dropIfExists('users');
    }
}
