<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Appointment extends Model
{
    /**
     * Map to existing patient bookings table.
     */
    protected $table = 'patients';

    protected $guarded = [];

    public $timestamps = true;

    protected $casts = [
        'appointment_date' => 'date:Y-m-d',
        'is_verified' => 'boolean',
    ];

    protected $appends = [
        'full_datetime',
        'computed_status',
        'formatted_date',
        'formatted_time',
        'relative_label',
        'can_reschedule'
    ];

    /**
     * Scope for a patient's bookings (patient_id, email, or user_id).
     */
    public function scopeForPatient(Builder $query, ?string $patientId, ?string $email = null, $userId = null): Builder
    {
        return $query->where(function ($q) use ($patientId, $email, $userId) {
            $hasCondition = false;
            if (!empty($patientId)) {
                $q->where('patient_id', $patientId);
                $hasCondition = true;
            }
            if (!empty($email)) {
                if ($hasCondition) {
                    $q->orWhere('email', $email);
                } else {
                    $q->where('email', $email);
                    $hasCondition = true;
                }
            }
            if (!empty($userId)) {
                if ($hasCondition) {
                    $q->orWhere('user_id', $userId);
                } else {
                    $q->where('user_id', $userId);
                }
            }
        });
    }

    /**
     * Scope for upcoming appointments:
     * - Belongs to authenticated patient
     * - Status in ('scheduled', 'confirmed', 'approved', 'for_approval', 'pending')
     * - Full datetime >= now() in Asia/Manila
     * - Ordered chronologically ascending
     */
    public function scopeUpcoming(Builder $query, ?string $patientId, ?string $email = null, $userId = null): Builder
    {
        $now = Carbon::now('Asia/Manila');

        return $query->forPatient($patientId, $email, $userId)
            ->whereNotNull('appointment_date')
            ->whereIn(\DB::raw('LOWER(TRIM(COALESCE(status, "")))'), ['scheduled', 'confirmed', 'approved', 'for_approval', 'for approval', 'pending'])
            ->whereRaw("TIMESTAMP(CONCAT(appointment_date, ' ', COALESCE(scheduled_time, '08:00:00'))) >= ?", [$now->format('Y-m-d H:i:s')])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('scheduled_time', 'asc');
    }

    /**
     * Scope for past/completed appointments:
     * - Completed visits OR past appointments that were not completed (missed)
     * - Excludes cancelled
     * - Ordered newest first
     */
    public function scopePast(Builder $query, ?string $patientId, ?string $email = null, $userId = null): Builder
    {
        $now = Carbon::now('Asia/Manila');

        return $query->forPatient($patientId, $email, $userId)
            ->whereNotNull('appointment_date')
            ->whereNotIn(\DB::raw('LOWER(TRIM(COALESCE(status, "")))'), ['cancelled', 'rejected', 'no-show', 'cancelled_by_patient'])
            ->where(function ($q) use ($now) {
                $q->where(\DB::raw('LOWER(TRIM(COALESCE(status, "")))'), 'completed')
                  ->orWhereRaw("TIMESTAMP(CONCAT(appointment_date, ' ', COALESCE(scheduled_time, '08:00:00'))) < ?", [$now->format('Y-m-d H:i:s')]);
            })
            ->orderBy('appointment_date', 'desc')
            ->orderBy('scheduled_time', 'desc');
    }

    /**
     * Scope for cancelled appointments:
     * - Ordered newest first
     */
    public function scopeCancelled(Builder $query, ?string $patientId, ?string $email = null, $userId = null): Builder
    {
        return $query->forPatient($patientId, $email, $userId)
            ->whereIn(\DB::raw('LOWER(TRIM(COALESCE(status, "")))'), ['cancelled', 'rejected', 'no-show', 'cancelled_by_patient'])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('scheduled_time', 'desc');
    }

    /**
     * Accessor for full datetime.
     */
    public function getFullDatetimeAttribute(): ?string
    {
        if (empty($this->appointment_date)) {
            return null;
        }
        $dateStr = is_string($this->appointment_date) ? $this->appointment_date : $this->appointment_date->format('Y-m-d');
        $timeStr = $this->scheduled_time ?: '08:00:00';
        return "{$dateStr} {$timeStr}";
    }

    /**
     * Clean department name without 'OPU'.
     */
    public function getCleanDepartmentAttribute(): string
    {
        $d = strtolower($this->department ?? '');
        if (str_contains($d, 'psych') || str_contains($d, 'opu')) return 'Psychiatry';
        if (str_contains($d, 'neuro')) return 'Neurology';
        return 'General Medical';
    }

    /**
     * Computed accurate status logic:
     * - 'In Consultation' only if today and inside consultation window.
     * - If past date/time and not completed/cancelled, marked 'Missed'.
     * - If future, maps to 'Confirmed' or 'For approval'.
     */
    public function getComputedStatusAttribute(): string
    {
        $raw = strtolower(trim($this->status ?? 'pending'));
        $now = Carbon::now('Asia/Manila');

        if (in_array($raw, ['cancelled', 'rejected', 'cancelled_by_patient'])) {
            return 'Cancelled';
        }
        if ($raw === 'no-show' || $raw === 'missed') {
            return 'Missed';
        }
        if ($raw === 'completed') {
            return 'Completed';
        }

        if (empty($this->full_datetime)) {
            return 'For approval';
        }

        $apptStart = Carbon::parse($this->full_datetime, 'Asia/Manila');
        $apptEnd = (clone $apptStart)->addHour();

        // Check if currently happening right now
        if ($now->between($apptStart, $apptEnd) && in_array($raw, ['in consultation', 'in_consultation', 'confirmed', 'approved', 'scheduled'])) {
            return 'In Consultation';
        }

        // Stale appointment in the past that was not completed
        if ($now->gt($apptEnd) && !in_array($raw, ['completed', 'cancelled', 'rejected'])) {
            return 'Missed';
        }

        if (in_array($raw, ['confirmed', 'approved', 'scheduled'])) {
            return 'Confirmed';
        }

        return 'For approval';
    }

    public function getFormattedDateAttribute(): string
    {
        if (empty($this->appointment_date)) return '—';
        $d = is_string($this->appointment_date) ? Carbon::parse($this->appointment_date) : $this->appointment_date;
        return $d->timezone('Asia/Manila')->format('M j, Y');
    }

    public function getFormattedTimeAttribute(): string
    {
        if (empty($this->scheduled_time)) return '8:00 AM';
        return Carbon::parse($this->scheduled_time)->format('g:i A');
    }

    public function getRelativeLabelAttribute(): ?string
    {
        if (empty($this->appointment_date)) return null;
        $apptDate = Carbon::parse($this->appointment_date)->startOfDay();
        $today = Carbon::now('Asia/Manila')->startOfDay();

        $diffDays = (int)$today->diffInDays($apptDate, false);

        if ($diffDays === 0) return 'Today';
        if ($diffDays === 1) return 'Tomorrow';
        if ($diffDays > 1 && $diffDays <= 7) return "In {$diffDays} days";
        return null;
    }

    public function getCanRescheduleAttribute(): bool
    {
        if (empty($this->full_datetime)) return false;
        $now = Carbon::now('Asia/Manila');
        $apptTime = Carbon::parse($this->full_datetime, 'Asia/Manila');
        $rawStatus = strtolower(trim($this->status ?? ''));

        return $apptTime->gt($now) && !in_array($rawStatus, ['completed', 'cancelled', 'rejected', 'in consultation', 'confirmed', 'approved', 'scheduled']);
    }
}

