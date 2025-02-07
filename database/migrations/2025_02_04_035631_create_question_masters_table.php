<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('question_masters', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 100)->charset('utf8')->nullable();

            $table->enum('type', ['Total', 'Often', 'Import'])->nullable();
            
            $table->string('option', 100)->charset('utf8')->nullable();
            
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
        Schema::dropIfExists('question_masters');
    }
}
