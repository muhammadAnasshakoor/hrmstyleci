<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDutyEquipmentTable extends Migration
{
    public function up()
    {
        Schema::create('duty_equipment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('duty_id')->nullable();
            $table->unsignedBigInteger('equipment_id')->nullable();
            $table->foreign('duty_id')->references('id')->on('duties')->onDelete('cascade');
            $table->foreign('equipment_id')->references('id')->on('equipments')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('duty_equipment');
    }
}
