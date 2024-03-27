

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
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->references('id')->on('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->references('id')->on('users')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('shift_start')->nullable();
            $table->string('shift_end')->nullable();
            $table->string('late_allow')->nullable();
            $table->string('early_departure_allow')->nullable();
            $table->string('late_deduction')->nullable();
            $table->string('early_deduction')->nullable();
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
        Schema::dropIfExists('policies');
    }
};