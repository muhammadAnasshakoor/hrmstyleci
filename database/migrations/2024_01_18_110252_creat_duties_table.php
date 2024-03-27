
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
        Schema::create('duties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('tenant_id')->nullable()->references('id')->on('tenants')->onDelete('cascade');
            $table->foreignId('employee_id')->nullable()->references('id')->on('employees')->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->references('id')->on('companies')->onDelete('cascade');
            $table->foreignId('policy_id')->nullable()->references('id')->on('policies')->onDelete('cascade');
            $table->text('note')->nullable();
            $table->datetime('joining_date')->nullable();
            $table->date('ended_at')->nullable();
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
        Schema::dropIfExists('duties');
    }
};
