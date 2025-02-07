<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemberDetailUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member_detail_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 50)->charset('utf8')->nullable();

            $table->integer('member_id')->unsigned()->index();
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');

            $table->integer('transport_thai_master_id')->unsigned()->index();
            $table->foreign('transport_thai_master_id')->references('id')->on('transport_thai_masters')->onDelete('cascade');

            $table->string('province')->nullable(); // จังหวัด
            $table->string('district')->nullable(); // อำเภอ
            $table->string('sub_district')->nullable(); // ตำบล
            $table->string('postal_code')->nullable(); // รหัสไปรษณีย์
            $table->decimal('latitude', 10, 8)->nullable(); // ละติจูด
            $table->decimal('longitude', 11, 8)->nullable(); // ลองจิจูด
            $table->string('transport_type')->nullable(); // รูปแบบขนส่ง
            $table->boolean('ever_imported_from_china')->default(false); // เคยนำเข้าสินค้าจากจีน
            $table->integer('order_quantity')->nullable(); // ยอดจำนวนคำสั่งซื้อที่เคยนำเข้า
            $table->boolean('frequent_importer')->default(false); // ท่านนำเข้าบ่อยหรือไม่
            $table->text('need_transport_type')->nullable(); // ต้องการนำเข้าแบบใด
            $table->text('additional_requests')->nullable(); // สิ่งที่ท่านต้องการเพิ่มเติม

            $table->enum('status', ['Yes', 'No','Request'])->charset('utf8')->default('Request');

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
        Schema::dropIfExists('member_detail_users');
    }
}
