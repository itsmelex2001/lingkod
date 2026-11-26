<?php

namespace App\Http\Controllers;

use App\Models\BookingAppointment;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentBookingController extends Controller
{
    /**
     * Book an appointment while reusing the original patient record/PT- ID matched by email.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'                   => ['required', 'email', 'max:120'],
            'first_name'              => ['required', 'string', 'max:100'],
            'last_name'               => ['required', 'string', 'max:100'],
            'middle_initial'          => ['nullable', 'string', 'max:10'],
            'gender'                  => ['nullable', 'string', 'max:20'],
            'birthdate'               => ['nullable', 'date'],
            'contact_number'          => ['nullable', 'string', 'max:25'],
            'address'                 => ['nullable', 'string', 'max:255'],
            'department'              => ['required', 'string', 'max:100'],
            'appointment_date'        => ['required', 'date'],
            'start_time'              => ['nullable', 'date_format:H:i:s'],
            'end_time'                => ['nullable', 'date_format:H:i:s'],
            'preferred_time_slot'     => ['nullable', 'string', 'max:60'],
            'consultation_mode'       => ['nullable', 'string', 'max:50'],
            'relationship_to_patient' => ['nullable', 'string', 'max:50'],
            'notes'                   => ['nullable', 'string'],
        ]);

        $email = strtolower(trim($validated['email']));
        $fullName = trim(implode(' ', array_filter([
            trim($validated['first_name']),
            ! empty($validated['middle_initial']) ? trim($validated['middle_initial']) : null,
            trim($validated['last_name']),
        ])));

        [$patient, $appointment] = DB::transaction(function () use ($validated, $email, $fullName) {
            // 1. Check for existing patient by normalized email (lock row to prevent race duplicates)
            $patient = Patient::whereRaw('LOWER(email) = ?', [$email])
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            // 2. Reuse original Patient ID if found; otherwise generate a new PT-YYYY-XXXXX ID
            if (! $patient) {
                $newPatientId = Patient::generateNextPatientId();

                $patient = Patient::create([
                    'patient_id'     => $newPatientId,
                    'hrn'            => 'HRN-' . substr($newPatientId, 3),
                    'full_name'      => $fullName,
                    'first_name'     => $validated['first_name'],
                    'last_name'      => $validated['last_name'],
                    'middle_initial' => $validated['middle_initial'] ?? null,
                    'gender'         => $validated['gender'] ?? null,
                    'birthdate'      => $validated['birthdate'] ?? null,
                    'contact_number' => $validated['contact_number'] ?? null,
                    'email'          => $email,
                    'address'        => $validated['address'] ?? null,
                    'is_verified'    => false,
                    'status'         => 'for_approval',
                ]);
            }

            // 3. Link the new appointment to the original (or newly created) patient_id
            $startTime = $validated['start_time'] ?? '08:00:00';
            $endTime   = $validated['end_time'] ?? '12:00:00';

            $appointment = BookingAppointment::create([
                'patient_id'              => $patient->patient_id,
                'department'              => $validated['department'],
                'appointment_date'        => $validated['appointment_date'],
                'start_time'              => $startTime,
                'end_time'                => $endTime,
                'status'                  => 'scheduled',
                'notes'                   => $validated['notes'] ?? null,
                'relationship_to_patient' => $validated['relationship_to_patient'] ?? null,
                'consultation_mode'       => $validated['consultation_mode'] ?? 'Face-to-Face',
                'preferred_time_slot'     => $validated['preferred_time_slot'] ?? null,
            ]);

            $appointment->update([
                'reference_no' => sprintf('APT-%s-%04d', now('Asia/Manila')->format('Y'), $appointment->id),
            ]);

            return [$patient, $appointment->fresh()];
        });

        return response()->json([
            'success'      => true,
            'patient_id'   => $patient->patient_id,
            'reference_no' => $appointment->reference_no,
            'patient'      => $patient,
            'appointment'  => $appointment,
        ], 201);
    }

    /**
     * Patient Records view: one row per unique patient (grouped by patient_id / canonical email)
     * with related appointments eager-loaded for the Svelte frontend.
     */
    public function patientRecords(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        // Select canonical earliest patient row per unique patient_id (deduplicating legacy duplicate emails)
        $canonicalIds = Patient::query()
            ->selectRaw('MIN(id) as id')
            ->groupByRaw("LOWER(COALESCE(NULLIF(TRIM(email), ''), patient_id))");

        $query = Patient::query()
            ->whereIn('id', $canonicalIds)
            ->with([
                'appointments' => fn ($q) => $q
                    ->orderByDesc('appointment_date')
                    ->orderByDesc('start_time'),
            ])
            ->withCount('appointments')
            ->orderByDesc('updated_at');

        if ($search !== '') {
            $like = '%' . strtolower($search) . '%';
            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(full_name) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(email) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(patient_id) LIKE ?', [$like]);
            });
        }

        $records = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $records,
        ]);
    }
}
