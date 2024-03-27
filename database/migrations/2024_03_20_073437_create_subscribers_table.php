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
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->references('id')->on('tenants')->onDelete('cascade'); // tenant which is taking the subscription
            $table->foreignId('subscription_plan_id')->nullable()->references('id')->on('subscription_plans')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->references('id')->on('users')->onDelete('cascade');// Who Enter the Record
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('amount')->nullable();
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
        Schema::dropIfExists('subscribers');
    }
};
