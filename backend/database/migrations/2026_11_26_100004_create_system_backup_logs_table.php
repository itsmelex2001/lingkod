<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('system_backup_logs')) {
            Schema::create('system_backup_logs', function (Blueprint $table) {
                $table->id();
                $table->string('filename', 180);
                $table->string('disk_path', 255)->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->unsignedSmallInteger('tables_count')->default(0);
                $table->string('trigger_type', 20)->default('manual');
                $table->string('status', 20)->default('completed');
                $table->text('error_message')->nullable();
                $table->unsignedInteger('triggered_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_backup_logs');
    }
};
