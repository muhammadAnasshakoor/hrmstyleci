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
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->references('id')->on('tenants')->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->references('id')->on('companies')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->date('starting_date')->nullable();
            $table->date('ending_date')->nullable();
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
        Schema::dropIfExists('holidays');
    }
};
