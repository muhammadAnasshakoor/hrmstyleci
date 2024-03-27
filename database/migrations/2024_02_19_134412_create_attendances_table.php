<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->references('id')->on('employees')->onDelete('cascade');
            $table->date('date')->nullable()->default(DB::raw('CURRENT_DATE'));
            $table->string('check_in')->nullable();
            $table->string('check_out')->nullable();
            $table->string('check_in_location')->nullable();
            $table->string('check_out_location')->nullable();
            $table->string('total_hours')->nullable();
            $table->string('type')->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('tenant_id')->nullable()->references('id')->on('tenants')->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->references('id')->on('companies')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
