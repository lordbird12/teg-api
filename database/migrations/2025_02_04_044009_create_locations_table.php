<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // ตารางจังหวัด
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->charset('utf8')->unique();
            $table->string('postal_code', 10)->nullable(); // เพิ่มรหัสไปรษณีย์ของจังหวัด (ถ้ามี)
            $table->timestamps();
        });

        // ตารางอำเภอ
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->charset('utf8');
            $table->foreignId('province_id')->constrained('provinces')->onDelete('cascade');
            $table->string('postal_code', 10)->nullable(); // เพิ่มรหัสไปรษณีย์ของอำเภอ
            $table->timestamps();
        });

        // ตารางตำบล
        Schema::create('sub_districts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->charset('utf8');
            $table->foreignId('district_id')->constrained('districts')->onDelete('cascade');
            $table->string('postal_code', 10)->nullable(); // รหัสไปรษณีย์ของตำบล
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sub_districts');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('provinces');
    }
}
