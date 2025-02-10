<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegisterImportersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('register_importers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('member_id')->nullable();
            $table->string('comp_name');
            $table->string('comp_tax')->unique();
            $table->boolean('registered')->default(false);
            $table->string('address');
            $table->string('province');
            $table->string('district');
            $table->string('sub_district');
            $table->string('postal_code');
            $table->string('authorized_person');
            $table->string('authorized_person_phone');
            $table->string('authorized_person_email');
            $table->string('id_card_picture')->nullable();
            $table->string('certificate_book_file')->nullable();
            $table->string('tax_book_file')->nullable();
            $table->string('logo_file')->nullable();
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
        Schema::dropIfExists('register_importers');
    }
}
