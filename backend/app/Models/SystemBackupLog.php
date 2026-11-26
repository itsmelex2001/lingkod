<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemBackupLog extends Model
{
    protected $table = 'system_backup_logs';

    protected $fillable = [
        'filename',
        'disk_path',
        'size_bytes',
        'tables_count',
        'trigger_type',
        'status',
        'error_message',
        'triggered_by',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'tables_count' => 'integer',
    ];
}
