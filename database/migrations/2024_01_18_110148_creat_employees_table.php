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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('phone_no')->nullable();
            $table->string('gender')->nullable();
            $table->string('emirates_id')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('permanent_address')->nullable();
            $table->string('local_address')->nullable();
            $table->string('nationality')->nullable();
            $table->foreignId('designation_id')->nullable()->references('id')->on('designations')->onDelete('cascade');
            $table->foreignId('profile_image_id')->nullable()->references('id')->on('media')->onDelete('cascade');
            $table->foreignId('passport_image_id')->nullable()->references('id')->on('media')->onDelete('cascade');
            $table->foreignId('emirates_image_id')->nullable()->references('id')->on('media')->onDelete('cascade');
            $table->foreignId('resume_image_id')->nullable()->references('id')->on('media')->onDelete('cascade');
            $table->string('acount_title')->nullable();
            $table->string('acount_no')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
