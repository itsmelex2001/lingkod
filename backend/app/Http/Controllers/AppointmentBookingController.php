<?php

namespace App\Http\Controllers;

use App\Models\BookingAppointment;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentBookingController extends Controller
{
    /**
     * Book an appointment while enforcing hospital scheduling rules and avoiding PII leakage.
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
            'consultation_mode'       => ['nullable', 'string', 'in:Face-to-Face,Teleconsultation'],
            'relationship_to_patient' => ['nullable', 'string', 'max:50'],
            'notes'                   => ['nullable', 'string', 'max:1000'],
        ]);

        $apptDate = Carbon::parse($validated['appointment_date'], 'Asia/Manila')->startOfDay();
        $today    = now('Asia/Manila')->startOfDay();
        if ($apptDate->lte($today) || $apptDate->isSunday()) {
            return response()->json([
                'success' => false,
                'message' => 'Pick a Monday to Saturday date at least one day ahead.',
            ], 422);
        }

        $consultationMode = $validated['consultation_mode'] ?? 'Face-to-Face';
        $email = strtolower(trim($validated['email']));
        $fullName = trim(implode(' ', array_filter([
            trim($validated['first_name']),
            ! empty($validated['middle_initial']) ? trim($validated['middle_initial']) : null,
            trim($validated['last_name']),
        ])));

        $startTime = $validated['start_time'] ?? '08:00:00';
        $endTime   = $validated['end_time'] ?? '12:00:00';
        $reqIsAfternoon = str_contains(strtolower((string)($validated['preferred_time_slot'] ?? '')), 'afternoon')
            || ((int)substr($startTime, 0, 2) >= 12);

        $result = DB::transaction(function () use ($validated, $email, $fullName, $consultationMode, $startTime, $endTime, $reqIsAfternoon) {
            // 1. Check for existing patient by normalized email (lock row to prevent race duplicates)
            $patient = Patient::whereRaw('LOWER(email) = ?', [$email])
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            // 2. Enforce returning-only Teleconsultation policy
            $hasCompletedVisit = $patient
                ? DB::table('patients')
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->whereRaw("LOWER(COALESCE(status, '')) IN ('completed', 'attended')")
                    ->exists()
                : false;

            if ($consultationMode === 'Teleconsultation' && ! $hasCompletedVisit) {
                return ['error' => 'First-time patients are required to have an initial Face-to-Face consultation. Teleconsultation is only available for returning patients.', 'code' => 422];
            }

            // 3. Reuse original Patient ID if found; otherwise generate a new PT-YYYY-XXXXX ID
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

            // 4. Prevent duplicate booking in the same date + morning/afternoon block
            $dayBookings = BookingAppointment::where('patient_id', $patient->patient_id)
                ->whereDate('appointment_date', $validated['appointment_date'])
                ->whereNotIn('status', ['cancelled', 'rejected', 'no-show'])
                ->get(['start_time', 'preferred_time_slot']);
            foreach ($dayBookings as $eb) {
                $ebAfternoon = str_contains(strtolower((string)($eb->preferred_time_slot ?? '')), 'afternoon')
                    || ((int)substr((string)($eb->start_time ?? '08:00:00'), 0, 2) >= 12);
                if ($ebAfternoon === $reqIsAfternoon) {
                    return ['error' => 'An appointment is already scheduled for this time slot. Please choose another time slot or date.', 'code' => 409];
                }
            }

            // 5. Create appointment strictly in 'for_approval' triage state
            $appointment = BookingAppointment::create([
                'patient_id'              => $patient->patient_id,
                'department'              => $validated['department'],
                'appointment_date'        => $validated['appointment_date'],
                'start_time'              => $startTime,
                'end_time'                => $endTime,
                'status'                  => 'for_approval',
                'notes'                   => $validated['notes'] ?? null,
                'relationship_to_patient' => $validated['relationship_to_patient'] ?? null,
                'consultation_mode'       => $consultationMode,
                'preferred_time_slot'     => $validated['preferred_time_slot'] ?? null,
            ]);

            $appointment->update([
                'reference_no' => sprintf('APT-%s-%04d', now('Asia/Manila')->format('Y'), $appointment->id),
            ]);

            return ['appointment' => $appointment->fresh()];
        });

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], $result['code']);
        }

        $appointment = $result['appointment'];

        // Never expose existing patient PII in an unauthenticated booking response
        return response()->json([
            'success'      => true,
            'reference_no' => $appointment->reference_no,
            'status'       => $appointment->status,
            'appointment'  => [
                'reference_no'        => $appointment->reference_no,
                'department'          => $appointment->department,
                'appointment_date'    => $appointment->appointment_date,
                'preferred_time_slot' => $appointment->preferred_time_slot,
                'consultation_mode'   => $appointment->consultation_mode,
                'status'              => $appointment->status,
            ],
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
