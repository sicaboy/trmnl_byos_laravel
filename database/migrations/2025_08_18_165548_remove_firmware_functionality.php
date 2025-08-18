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
        // Remove update_firmware_id column from devices table
        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['update_firmware_id']);
            $table->dropColumn('update_firmware_id');
        });

        // Drop the firmware table
        Schema::dropIfExists('firmware');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate firmware table
        Schema::create('firmware', function (Blueprint $table) {
            $table->id();
            $table->string('version_tag');
            $table->string('url');
            $table->string('storage_location')->nullable();
            $table->boolean('latest')->default(false);
            $table->timestamps();
        });

        // Add back update_firmware_id column to devices table
        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedBigInteger('update_firmware_id')->nullable();
            $table->foreign('update_firmware_id')->references('id')->on('firmware');
        });
    }
};
