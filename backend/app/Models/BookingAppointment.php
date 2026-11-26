<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAppointment extends Model
{
    protected $table = 'appointments';

    protected $fillable = [
        'reference_no',
        'patient_id',
        'department',
        'appointment_date',
        'start_time',
        'end_time',
        'status',
        'notes',
        'relationship_to_patient',
        'consultation_mode',
        'preferred_time_slot',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date:Y-m-d',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }
}
