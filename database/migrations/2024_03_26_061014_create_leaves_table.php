<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->references('id')->on('tenants')->onDelete('cascade');
                $table->foreignId('employee_id')->nullable()->references('id')->on('employees')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->references('id')->on('users')->onDelete('cascade');//it contains the id of the user which requested a leave
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('total_days')->nullable();
                $table->string('description')->nullable();
                $table->string('status')->nullable();
                $table->softDeletes();
                $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
