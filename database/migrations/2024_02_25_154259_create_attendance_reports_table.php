<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->references('id')->on('employees')->onDelete('cascade');
            $table->foreignId('tenant_id')->nullable()->references('id')->on('tenants')->onDelete('cascade');
            $table->foreignId('attendance_id')->nullable()->references('id')->on('attendances')->onDelete('cascade');
            $table->string('employee_name')->nullable();
            $table->string('checkin')->nullable();
            $table->string('checkout')->nullable();
            $table->string('total_hours_worked')->nullable();
            $table->string('type')->nullable();
            $table->string('reason')->nullable();
            $table->string('day')->nullable();
            $table->string('expected_time')->nullable();
            $table->foreignId('company_id')->nullable()->references('id')->on('companies')->onDelete('cascade');
            $table->date('date')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_reports');
    }
};
