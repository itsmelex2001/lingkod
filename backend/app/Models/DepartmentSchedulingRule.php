<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentSchedulingRule extends Model
{
    protected $table = 'department_scheduling_rules';

    protected $fillable = [
        'department_code',
        'display_name',
        'daily_capacity_limit',
        'am_slot_limit',
        'pm_slot_limit',
        'booking_cutoff_time',
        'qr_expiry_hours_override',
        'allow_same_day_booking',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'daily_capacity_limit' => 'integer',
        'am_slot_limit' => 'integer',
        'pm_slot_limit' => 'integer',
        'qr_expiry_hours_override' => 'integer',
        'allow_same_day_booking' => 'boolean',
        'is_active' => 'boolean',
    ];
}
