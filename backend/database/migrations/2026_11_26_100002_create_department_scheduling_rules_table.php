<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('department_scheduling_rules')) {
            Schema::create('department_scheduling_rules', function (Blueprint $table) {
                $table->id();
                $table->string('department_code', 50)->unique();
                $table->string('display_name', 100);
                $table->unsignedSmallInteger('daily_capacity_limit')->default(50);
                $table->unsignedSmallInteger('am_slot_limit')->nullable();
                $table->unsignedSmallInteger('pm_slot_limit')->nullable();
                $table->string('booking_cutoff_time', 8)->default('16:00');
                $table->unsignedSmallInteger('qr_expiry_hours_override')->nullable();
                $table->boolean('allow_same_day_booking')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('department_scheduling_rules');
    }
};
