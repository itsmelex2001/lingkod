<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAuthSettingsRequest;
use App\Http\Requests\Admin\UpdateGeneralSettingsRequest;
use App\Http\Requests\Admin\UpdateSchedulingSettingsRequest;
use App\Http\Requests\Admin\UpdateSystemSettingsRequest;
use App\Mail\PatientOtpMail;
use App\Models\DepartmentSchedulingRule;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SystemSettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function index(): JsonResponse
    {
        $grouped = $this->settings->getAllGrouped();
        $departments = DepartmentSchedulingRule::orderBy('id')->get();

        $grouped['scheduling']['departments'] = $departments;

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request): JsonResponse
    {
        [$actorId, $actorName] = $this->resolveActor($request);
        $this->settings->updateGroup('general', $request->validated(), $actorId, $actorName);

        return response()->json([
            'success' => true,
            'message' => 'General hospital configuration saved.',
            'data' => $this->settings->getAllGrouped()['general'],
        ]);
    }

    public function updateScheduling(UpdateSchedulingSettingsRequest $request): JsonResponse
    {
        [$actorId, $actorName] = $this->resolveActor($request);
        $data = $request->validated();

        $this->settings->updateGroup('scheduling', [
            'qr_code_expiry_hours' => $data['qr_code_expiry_hours'],
        ], $actorId, $actorName);

        foreach ($data['departments'] as $dept) {
            DepartmentSchedulingRule::where('department_code', $dept['department_code'])->update([
                'daily_capacity_limit' => $dept['daily_capacity_limit'],
                'am_slot_limit' => $dept['am_slot_limit'] ?? null,
                'pm_slot_limit' => $dept['pm_slot_limit'] ?? null,
                'booking_cutoff_time' => $dept['booking_cutoff_time'],
                'qr_expiry_hours_override' => $dept['qr_expiry_hours_override'] ?? null,
                'allow_same_day_booking' => (bool) $dept['allow_same_day_booking'],
                'is_active' => array_key_exists('is_active', $dept) ? (bool) $dept['is_active'] : true,
                'updated_by' => $actorId,
            ]);
        }

        $grouped = $this->settings->getAllGrouped();
        $grouped['scheduling']['departments'] = DepartmentSchedulingRule::orderBy('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Scheduling rules and department capacities saved.',
            'data' => $grouped['scheduling'],
        ]);
    }

    public function updateAuth(UpdateAuthSettingsRequest $request): JsonResponse
    {
        [$actorId, $actorName] = $this->resolveActor($request);
        $this->settings->updateGroup('auth', $request->validated(), $actorId, $actorName);

        return response()->json([
            'success' => true,
            'message' => 'Authentication and OTP settings saved.',
            'data' => $this->settings->getAllGrouped()['auth'],
        ]);
    }

    public function testOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient' => ['required', 'email', 'max:120'],
        ]);

        $auth = $this->settings->getAllGrouped()['auth'] ?? [];
        $code = (string) random_int(100000, 999999);
        $expiry = (int) ($auth['otp_expiry_minutes'] ?? 10);

        if (class_exists(PatientOtpMail::class)) {
            Mail::to($validated['recipient'])->send(new PatientOtpMail($code, 'LINGKOD Admin Test', $expiry));
        } else {
            Mail::raw("Your LINGKOD test OTP is {$code} (valid {$expiry} min).", function ($msg) use ($validated) {
                $msg->to($validated['recipient'])->subject('LINGKOD Test OTP');
            });
        }

        [, $actorName] = $this->resolveActor($request);
        $this->settings->writeAuditLog('info', 'Test OTP Dispatched', $actorName, 'Sent test OTP to ' . $validated['recipient']);

        return response()->json([
            'success' => true,
            'message' => 'Test OTP dispatched to ' . $validated['recipient'] . '.',
        ]);
    }

    public function updateSystem(UpdateSystemSettingsRequest $request): JsonResponse
    {
        [$actorId, $actorName] = $this->resolveActor($request);
        $this->settings->updateGroup('system', $request->validated(), $actorId, $actorName);

        return response()->json([
            'success' => true,
            'message' => 'System preferences saved.',
            'data' => $this->settings->getAllGrouped()['system'],
        ]);
    }

    private function resolveActor(Request $request): array
    {
        $user = $request->user();
        if ($user) {
            return [$user->id, $user->name ?? $user->email ?? 'admin'];
        }
        return [$_SESSION['user_id'] ?? null, $_SESSION['user'] ?? 'admin'];
    }
}
