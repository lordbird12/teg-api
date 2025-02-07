<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('members', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 100)->charset('utf8')->nullable();

            $table->string('fname', 255)->charset('utf8')->nullable();
            $table->string('lname', 255)->charset('utf8')->nullable();
            $table->string('phone', 100)->charset('utf8')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            
            $table->string('importer_code', 100)->charset('utf8')->nullable();
            $table->string('password', 100)->charset('utf8')->nullable();
            $table->string('referrer', 100)->charset('utf8')->nullable();
            
            $table->string('company_name', 255)->charset('utf8')->nullable();

            $table->string('address', 255)->charset('utf8')->nullable();
            $table->string('province', 100)->charset('utf8')->nullable();
            $table->string('district', 100)->charset('utf8')->nullable();
            $table->string('sub_district', 100)->charset('utf8')->nullable();
            $table->string('postal_code', 10)->charset('utf8')->nullable();
            
            $table->enum('shipping_type', ['standard', 'express', 'pickup'])->default('standard');

            $table->string('image', 255)->charset('utf8')->nullable();
            
            $table->string('create_by', 100)->charset('utf8')->nullable();
            $table->string('update_by', 100)->charset('utf8')->nullable();

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
        Schema::dropIfExists('members');
    }
}
