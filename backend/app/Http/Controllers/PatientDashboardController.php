<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Appointment;
use App\Http\Resources\AppointmentResource;
use Carbon\Carbon;

class PatientDashboardController extends Controller
{
    /**
     * Resolves authenticated patient identifiers from request or session.
     */
    protected function resolvePatientIdentifiers(Request $request): array
    {
        $patientId = $request->input('patient_id') ?: session('patient_id');
        $email     = $request->input('email') ?: session('patient_email');
        $userId    = $request->input('user_id') ?: session('user_id');

        if (empty($patientId) && empty($email) && empty($userId)) {
            // Default demo fallback if unauthenticated demo access
            $patientId = 'PT-2026-082';
            $email     = 'maria.santos@gmail.com';
        }

        return [$patientId, $email, $userId];
    }

    /**
     * Get patient portal dashboard summary, counts, next appointment, and upcoming list.
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        [$patientId, $email, $userId] = $this->resolvePatientIdentifiers($request);

        try {
            // 1. Patient Profile
            $profileRecord = DB::table('patients')
                ->where(function ($q) use ($patientId, $email, $userId) {
                    if ($patientId) $q->where('patient_id', $patientId);
                    if ($email) $q->orWhere('email', $email);
                    if ($userId) $q->orWhere('user_id', $userId);
                })
                ->select('id', 'patient_id', 'full_name', 'email', 'contact_number', 'birthdate', 'gender', 'address')
                ->orderBy('id', 'desc')
                ->first();

            $profile = [
                'patient_id'     => $profileRecord->patient_id ?? $patientId,
                'full_name'      => $profileRecord->full_name ?? session('patient_name', 'Patient'),
                'email'          => $profileRecord->email ?? $email,
                'contact_number' => $profileRecord->contact_number ?? '',
                'gender'         => $profileRecord->gender ?? '',
                'birthdate'      => $profileRecord->birthdate ?? '',
                'address'        => $profileRecord->address ?? '',
                'portal_language'=> session('portal_language', 'en')
            ];

            // 2. Exact Query Scopes for Counts
            $upcomingQuery  = Appointment::upcoming($patientId, $email, $userId);
            $pastQuery      = Appointment::past($patientId, $email, $userId);
            $cancelledQuery = Appointment::cancelled($patientId, $email, $userId);

            $upcomingCount  = (clone $upcomingQuery)->count();
            $pastCount      = (clone $pastQuery)->count();
            $cancelledCount = (clone $cancelledQuery)->count();
            $totalCount     = $upcomingCount + $pastCount + $cancelledCount;

            // 3. Next Appointment (Chronologically first upcoming appointment)
            $nextApptModel = (clone $upcomingQuery)->first();

            // 4. Upcoming list (top 5)
            $upcomingList = (clone $upcomingQuery)->take(5)->get();

            return response()->json([
                'success' => true,
                'profile' => $profile,
                'counts'  => [
                    'upcoming'  => $upcomingCount,
                    'scheduled' => $upcomingCount, // same count
                    'past'      => $pastCount,
                    'completed' => $pastCount,
                    'cancelled' => $cancelledCount,
                    'total'     => $totalCount
                ],
                'stats'   => [
                    'scheduled_visits' => $upcomingCount,
                    'completed_visits' => $pastCount,
                    'cancelled_visits' => $cancelledCount,
                    'total_visits'     => $totalCount
                ],
                'next_appointment'     => $nextApptModel ? new AppointmentResource($nextApptModel) : null,
                'upcoming_appointments' => AppointmentResource::collection($upcomingList),
                // Backward-compatible arrays
                'upcoming'  => AppointmentResource::collection($upcomingList),
                'past'      => AppointmentResource::collection((clone $pastQuery)->take(5)->get()),
                'cancelled' => AppointmentResource::collection((clone $cancelledQuery)->take(5)->get()),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Server-side filtered appointment history with pagination.
     */
    public function getAppointmentsList(Request $request): JsonResponse
    {
        [$patientId, $email, $userId] = $this->resolvePatientIdentifiers($request);

        $tab      = strtolower($request->input('tab', 'upcoming'));
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $search   = trim($request->input('search', ''));
        $perPage  = max(1, min(50, (int)$request->input('per_page', 10)));

        // Validate date range
        if (!empty($dateFrom) && !empty($dateTo) && $dateFrom > $dateTo) {
            return response()->json([
                'success' => false,
                'message' => 'The "From" date cannot be after the "To" date.'
            ], 422);
        }

        try {
            // Select base query scope based on tab
            if ($tab === 'past' || $tab === 'completed') {
                $query = Appointment::past($patientId, $email, $userId);
            } elseif ($tab === 'cancelled') {
                $query = Appointment::cancelled($patientId, $email, $userId);
            } else {
                $query = Appointment::upcoming($patientId, $email, $userId);
            }

            // Apply date filters
            if (!empty($dateFrom)) {
                $query->where('appointment_date', '>=', $dateFrom);
            }
            if (!empty($dateTo)) {
                $query->where('appointment_date', '<=', $dateTo);
            }

            // Apply search (department, patient/appointment ID, doctor, chief complaint)
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('patient_id', 'LIKE', "%{$search}%")
                      ->orWhere('department', 'LIKE', "%{$search}%")
                      ->orWhere('assigned_doctor', 'LIKE', "%{$search}%")
                      ->orWhere('chief_complaints', 'LIKE', "%{$search}%")
                      ->orWhere('appointment_date', 'LIKE', "%{$search}%");
                });
            }

            // Get live counts across all 3 tabs for badge sync
            $upcomingCount  = Appointment::upcoming($patientId, $email, $userId)->count();
            $pastCount      = Appointment::past($patientId, $email, $userId)->count();
            $cancelledCount = Appointment::cancelled($patientId, $email, $userId)->count();

            $paginated = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'tab'     => $tab,
                'counts'  => [
                    'upcoming'  => $upcomingCount,
                    'past'      => $pastCount,
                    'cancelled' => $cancelledCount,
                    'total'     => $upcomingCount + $pastCount + $cancelledCount
                ],
                'data'    => AppointmentResource::collection($paginated->items()),
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                    'has_more'     => $paginated->hasMorePages()
                ]
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving appointments list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update patient profile information.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        [$patientId, $email, $userId] = $this->resolvePatientIdentifiers($request);

        $request->validate([
            'contact_number' => ['nullable', 'string', 'max:25'],
            'portal_language'=> ['nullable', 'string', 'in:en,fil,tl'],
            'address'        => ['nullable', 'string', 'max:255'],
        ]);

        $contactNumber  = trim($request->input('contact_number', ''));
        $portalLanguage = $request->input('portal_language', 'en');
        $address        = trim($request->input('address', ''));

        try {
            DB::table('patients')
                ->where(function ($q) use ($patientId, $email, $userId) {
                    if ($patientId) $q->where('patient_id', $patientId);
                    if ($email) $q->orWhere('email', $email);
                    if ($userId) $q->orWhere('user_id', $userId);
                })
                ->update([
                    'contact_number' => $contactNumber,
                    'address'        => $address,
                    'updated_at'     => Carbon::now('Asia/Manila')
                ]);

            session(['portal_language' => $portalLanguage]);

            return response()->json([
                'success' => true,
                'message' => 'Profile preferences updated successfully.'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not save profile changes.'
            ], 500);
        }
    }

    /**
     * Change patient password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        [$patientId, $email, $userId] = $this->resolvePatientIdentifiers($request);

        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:8'],
            'confirm_password' => ['required', 'same:new_password'],
        ]);

        $currentPass = $request->input('current_password');
        $newPass     = $request->input('new_password');

        try {
            $patient = DB::table('patients')
                ->where(function ($q) use ($patientId, $email, $userId) {
                    if ($patientId) $q->where('patient_id', $patientId);
                    if ($email) $q->orWhere('email', $email);
                    if ($userId) $q->orWhere('user_id', $userId);
                })
                ->whereNotNull('password')
                ->orderBy('id', 'desc')
                ->first();

            if ($patient && !empty($patient->password)) {
                if (!Hash::check($currentPass, $patient->password) && $currentPass !== $patient->password) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The current password you entered is incorrect.'
                    ], 422);
                }
            }

            $hashed = Hash::make($newPass);

            DB::table('patients')
                ->where(function ($q) use ($patientId, $email, $userId) {
                    if ($patientId) $q->where('patient_id', $patientId);
                    if ($email) $q->orWhere('email', $email);
                    if ($userId) $q->orWhere('user_id', $userId);
                })
                ->update([
                    'password'   => $hashed,
                    'updated_at' => Carbon::now('Asia/Manila')
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully.'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating your password.'
            ], 500);
        }
    }
}
