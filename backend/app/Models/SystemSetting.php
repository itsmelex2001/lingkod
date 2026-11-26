<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $fillable = [
        'group_key',
        'setting_key',
        'setting_value',
        'value_type',
        'description',
        'updated_by',
    ];
}
