<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class PatientDashboardController extends Controller
{
    /**
     * Get patient portal scheduling and appointment data.
     * All medical history, notes, prescriptions, and lab records are stripped for patient privacy.
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        $patientId    = $request->input('patient_id') ?? session('patient_id', 'PT-2026-082');
        $patientEmail = $request->input('email') ?? session('patient_email', '');

        try {
            $profile = DB::table('patients')
                ->where('patient_id', $patientId)
                ->orWhere('email', $patientEmail)
                ->select('id', 'patient_id', 'full_name', 'email')
                ->orderBy('id', 'desc')
                ->first();

            $today = date('Y-m-d');

            $appointments = DB::table('patients')
                ->where('patient_id', $patientId)
                ->orWhere('email', $patientEmail)
                ->select('id', 'patient_id', 'department', 'appointment_date', 'scheduled_time', 'status', 'assigned_doctor', 'consultation_mode')
                ->orderByRaw("CASE WHEN appointment_date >= CURDATE() THEN 0 ELSE 1 END ASC")
                ->orderBy('appointment_date', 'asc')
                ->orderBy('scheduled_time', 'asc')
                ->get();

            $upcoming  = [];
            $past      = [];
            $cancelled = [];

            foreach ($appointments as $appt) {
                $st           = strtolower($appt->status ?? 'pending');
                $apptDateRaw  = $appt->appointment_date ?? date('Y-m-d');
                $formattedDate= date('M j, Y', strtotime($apptDateRaw));
                $docPart      = !empty($appt->assigned_doctor) ? $appt->assigned_doctor : 'Attending Physician';
                $deptPart     = !empty($appt->department) ? $appt->department : 'General Medical';
                $timePart     = !empty($appt->scheduled_time) ? date('g:i A', strtotime($appt->scheduled_time)) : '8:00 AM';
                $displayStatus= in_array($st, ['approved', 'confirmed']) ? 'Confirmed' : (in_array($st, ['pending', 'for approval']) ? 'For approval' : ucfirst($st));

                $item = [
                    'id'             => $appt->id ?? null,
                    'date'           => $formattedDate,
                    'time'           => $timePart,
                    'department'     => $deptPart,
                    'doctor'         => $docPart,
                    'consult_mode'   => $appt->consultation_mode ?? 'In-person',
                    'status'         => $displayStatus,
                    'can_reschedule' => in_array($st, ['pending', 'for approval']) && $apptDateRaw >= $today
                ];

                if (in_array($st, ['cancelled', 'rejected', 'no-show'])) {
                    $cancelled[] = $item;
                } elseif ($st === 'completed' || $apptDateRaw < $today) {
                    $item['status'] = 'Completed';
                    $past[] = $item;
                } else {
                    $upcoming[] = $item;
                }
            }

            $departments = [
                ['id' => 'general_medicine', 'name' => 'General Medical', 'action' => 'Book a visit'],
                ['id' => 'neurology',        'name' => 'Neurology',       'action' => 'Book a visit'],
                ['id' => 'psychiatry',       'name' => 'Psychiatry',      'action' => 'Book a visit']
            ];

            return response()->json([
                'success'     => true,
                'profile'     => $profile ?? [
                    'patient_id' => $patientId,
                    'full_name'  => 'Patient',
                    'email'      => $patientEmail
                ],
                'stats'       => [
                    'upcoming_count'  => count($upcoming),
                    'past_count'      => count($past),
                    'cancelled_count' => count($cancelled)
                ],
                'departments' => $departments,
                'upcoming'    => $upcoming,
                'past'        => $past,
                'cancelled'   => $cancelled
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard: ' . $e->getMessage()
            ], 500);
        }
    }
}
