<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $table = 'patients';

    protected $fillable = [
        'patient_id',
        'user_id',
        'hrn',
        'full_name',
        'first_name',
        'last_name',
        'middle_initial',
        'gender',
        'birthdate',
        'age',
        'contact_number',
        'email',
        'address',
        'is_verified',
        'is_otp_enabled',
        'status',
    ];

    protected $hidden = [
        'password',
        'otp_code',
        'otp_hash',
        'verification_token',
        'reset_token',
    ];

    protected function casts(): array
    {
        return [
            'birthdate'      => 'date:Y-m-d',
            'is_verified'    => 'boolean',
            'is_otp_enabled' => 'boolean',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(BookingAppointment::class, 'patient_id', 'patient_id');
    }

    public static function generateNextPatientId(): string
    {
        $year = now('Asia/Manila')->format('Y');

        do {
            $candidate = sprintf('PT-%s-%05d', $year, random_int(10000, 99999));
        } while (self::where('patient_id', $candidate)->exists());

        return $candidate;
    }
}
