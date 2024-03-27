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
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('logo_media_id')->nullable()->references('id')->on('media')->onDelete('cascade');
            $table->foreignId('document_media_id')->nullable()->references('id')->on('media')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['logo_media_id']);
        $table->dropForeign(['document_media_id']);
        $table->dropColumn('document_media_id');
        $table->dropColumn('logo_media_id');
        });
    }
};
