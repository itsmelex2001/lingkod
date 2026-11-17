<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $dateStr = $this->appointment_date ? (is_string($this->appointment_date) ? $this->appointment_date : $this->appointment_date->format('Y-m-d')) : null;

        return [
            'id'             => $this->id,
            'appointment_id' => $this->patient_id,
            'department'     => $this->clean_department,
            'date_raw'       => $dateStr,
            'time_raw'       => $this->scheduled_time,
            'datetime_iso'   => $this->full_datetime,
            'date'           => $this->formatted_date,
            'time'           => $this->formatted_time,
            'relative_label' => $this->relative_label,
            'consult_mode'   => $this->consultation_mode ?? 'Face-to-Face',
            'status'         => $this->computed_status,
            'raw_status'     => $this->status,
            'doctor'         => $this->assigned_doctor ?: 'Attending Physician',
            'chief_complaint'=> $this->chief_complaints ?: $this->consultation_nature,
            'can_reschedule' => $this->can_reschedule,
            'can_cancel'     => $this->can_reschedule,
        ];
    }
}

