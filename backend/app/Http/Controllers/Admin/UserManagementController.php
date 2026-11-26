<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    private const STAFF_ROLES = ['superadmin', 'admin', 'staff'];

    public function listStaff(Request $request): JsonResponse
    {
        $search = strtolower(trim((string) $request->query('search', '')));
        $dept   = strtolower(trim((string) $request->query('department', '')));
        $status = strtolower(trim((string) $request->query('status', '')));

        $hasFullName = Schema::hasColumn('users', 'full_name');
        $hasUsername = Schema::hasColumn('users', 'username');
        $hasDept     = Schema::hasColumn('users', 'department');
        $hasStatus   = Schema::hasColumn('users', 'status');
        $hasImage    = Schema::hasColumn('users', 'profile_image');

        $query = DB::table('users')
            ->whereIn('role', self::STAFF_ROLES);

        if ($status !== '' && $status !== 'all' && $hasStatus) {
            $query->whereRaw('LOWER(COALESCE(status, "active")) = ?', [$status]);
        }

        if ($dept !== '' && $dept !== 'all' && $hasDept) {
            $query->where(function ($q) use ($dept) {
                if (str_contains($dept, 'psych') || str_contains($dept, 'opu')) {
                    $q->whereRaw('LOWER(department) LIKE ?', ['%psych%'])
                      ->orWhereRaw('LOWER(department) LIKE ?', ['%opu%']);
                } elseif (str_contains($dept, 'neuro')) {
                    $q->whereRaw('LOWER(department) LIKE ?', ['%neuro%']);
                } elseif (str_contains($dept, 'general')) {
                    $q->whereRaw('LOWER(department) LIKE ?', ['%general%'])
                      ->orWhereRaw('LOWER(department) LIKE ?', ['%medical%']);
                } else {
                    $q->whereRaw('LOWER(department) = ?', [$dept]);
                }
            });
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like, $hasFullName, $hasUsername) {
                if ($hasFullName) {
                    $q->orWhereRaw('LOWER(COALESCE(full_name, "")) LIKE ?', [$like]);
                }
                if ($hasUsername) {
                    $q->orWhereRaw('LOWER(COALESCE(username, "")) LIKE ?', [$like]);
                }
                if (Schema::hasColumn('users', 'name')) {
                    $q->orWhereRaw('LOWER(COALESCE(name, "")) LIKE ?', [$like]);
                }
            });
        }

        $rows = $query->orderBy('role')->orderBy('id')->get()->map(function ($u) use ($hasFullName, $hasUsername, $hasDept, $hasStatus, $hasImage) {
            $fullName = $hasFullName ? ($u->full_name ?? '') : '';
            if ($fullName === '' && isset($u->name)) {
                $fullName = (string) $u->name;
            }
            $username = $hasUsername ? ($u->username ?? '') : ($u->email ?? '');

            return [
                'id'            => (int) $u->id,
                'full_name'     => $fullName !== '' ? $fullName : $username,
                'username'      => (string) $username,
                'role'          => strtolower((string) ($u->role ?? 'staff')),
                'department'    => $hasDept ? (string) ($u->department ?: 'Unassigned') : 'Unassigned',
                'status'        => $hasStatus ? strtolower((string) ($u->status ?: 'active')) : 'active',
                'profile_image' => $hasImage ? ($u->profile_image ?? null) : null,
                'created_at'    => $u->created_at ?? null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data'    => $rows,
        ]);
    }

    public function storeStaff(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name'  => ['required', 'string', 'max:150'],
            'username'   => ['required', 'string', 'regex:/^[a-zA-Z0-9_]{4,30}$/'],
            'role'       => ['required', Rule::in(self::STAFF_ROLES)],
            'department' => ['required', 'string', 'max:100'],
            'password'   => ['required', 'string', 'min:6', 'max:128'],
        ]);

        if (Schema::hasColumn('users', 'username')) {
            $exists = DB::table('users')->whereRaw('LOWER(username) = ?', [strtolower($data['username'])])->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Username already exists.',
                    'errors'  => ['username' => ['Username already exists.']],
                ], 422);
            }
        }

        $insert = [
            'password'   => Hash::make($data['password']),
            'role'       => $data['role'],
            'created_at' => now(),
        ];
        if (Schema::hasColumn('users', 'updated_at')) {
            $insert['updated_at'] = now();
        }
        if (Schema::hasColumn('users', 'name')) {
            $insert['name'] = $data['full_name'];
        }
        if (Schema::hasColumn('users', 'email')) {
            $insert['email'] = strtolower($data['username']) . '@mmwgh.local';
        }
        if (Schema::hasColumn('users', 'full_name')) {
            $insert['full_name'] = $data['full_name'];
        }
        if (Schema::hasColumn('users', 'username')) {
            $insert['username'] = $data['username'];
        }
        if (Schema::hasColumn('users', 'department')) {
            $insert['department'] = $data['department'];
        }
        if (Schema::hasColumn('users', 'status')) {
            $insert['status'] = 'active';
        }

        $id = DB::table('users')->insertGetId($insert);
        $this->logAudit($request, 'STAFF_CREATE', "Created {$data['role']} account {$data['username']} ({$data['department']})");

        return response()->json([
            'success' => true,
            'message' => 'Staff account created.',
            'data'    => [
                'id'         => (int) $id,
                'full_name'  => $data['full_name'],
                'username'   => $data['username'],
                'role'       => $data['role'],
                'department' => $data['department'],
                'status'     => 'active',
            ],
        ], 201);
    }

    public function updateStaff(Request $request, int $user): JsonResponse
    {
        $record = DB::table('users')->where('id', $user)->whereIn('role', self::STAFF_ROLES)->first();
        if (! $record) {
            return response()->json(['success' => false, 'message' => 'Staff member not found.'], 404);
        }

        $data = $request->validate([
            'full_name'  => ['sometimes', 'required', 'string', 'max:150'],
            'role'       => ['sometimes', 'required', Rule::in(self::STAFF_ROLES)],
            'department' => ['sometimes', 'required', 'string', 'max:100'],
            'status'     => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
        ]);

        // Prevent demoting or disabling the last active superadmin
        $newRole   = $data['role'] ?? strtolower((string) $record->role);
        $newStatus = $data['status'] ?? strtolower((string) ($record->status ?? 'active'));
        if (strtolower((string) $record->role) === 'superadmin' && ($newRole !== 'superadmin' || $newStatus !== 'active')) {
            $otherSuperAdmins = DB::table('users')
                ->where('role', 'superadmin')
                ->where('id', '<>', $user)
                ->when(Schema::hasColumn('users', 'status'), fn ($q) => $q->whereRaw('LOWER(COALESCE(status, "active")) = "active"'))
                ->count();
            if ($otherSuperAdmins === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot demote or disable the only active Super Administrator.',
                ], 422);
            }
        }

        $update = [];
        if (isset($data['full_name'])) {
            if (Schema::hasColumn('users', 'full_name')) {
                $update['full_name'] = $data['full_name'];
            }
            if (Schema::hasColumn('users', 'name')) {
                $update['name'] = $data['full_name'];
            }
        }
        if (isset($data['role'])) {
            $update['role'] = $data['role'];
        }
        if (isset($data['department']) && Schema::hasColumn('users', 'department')) {
            $update['department'] = $data['department'];
        }
        if (isset($data['status']) && Schema::hasColumn('users', 'status')) {
            $update['status'] = $data['status'];
        }
        if (Schema::hasColumn('users', 'updated_at')) {
            $update['updated_at'] = now();
        }

        if ($update !== []) {
            DB::table('users')->where('id', $user)->update($update);
        }

        $this->logAudit($request, 'STAFF_UPDATE', "Updated staff #{$user} (role={$newRole}, status={$newStatus})");

        return response()->json([
            'success' => true,
            'message' => 'Staff account updated.',
        ]);
    }

    public function resetStaffPassword(Request $request, int $user): JsonResponse
    {
        $record = DB::table('users')->where('id', $user)->whereIn('role', self::STAFF_ROLES)->first();
        if (! $record) {
            return response()->json(['success' => false, 'message' => 'Staff member not found.'], 404);
        }

        $tempPassword = $this->generateReadablePassword('STF');
        $update = ['password' => Hash::make($tempPassword)];
        if (Schema::hasColumn('users', 'updated_at')) {
            $update['updated_at'] = now();
        }

        DB::table('users')->where('id', $user)->update($update);
        $this->logAudit($request, 'STAFF_PASSWORD_RESET', "Reset password for staff #{$user} (" . ($record->username ?? $record->email ?? $user) . ')');

        return response()->json([
            'success'            => true,
            'message'            => 'Staff password has been reset.',
            'temporary_password' => $tempPassword,
        ]);
    }

    public function listPatients(Request $request): JsonResponse
    {
        if (! Schema::hasTable('patients')) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $search = strtolower(trim((string) $request->query('search', '')));
        $hasAccountStatus = Schema::hasColumn('patients', 'account_status');
        $hasMustChange    = Schema::hasColumn('patients', 'must_change_password');
        $hasRecipient     = Schema::hasColumn('patients', 'appointment_recipient');

        $query = DB::table('patients')
            ->whereNotNull('username')
            ->where('username', '<>', '')
            ->whereNotNull('password')
            ->where('password', '<>', '');

        if ($hasRecipient) {
            $query->whereRaw("COALESCE(appointment_recipient, 'Patient (Me)') NOT IN ('Other Patient', 'Someone Else / Dependent')");
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(COALESCE(full_name, "")) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(COALESCE(username, "")) LIKE ?', [$like]);
            });
        }

        $seen = [];
        $rows = [];
        foreach ($query->orderByDesc('is_verified')->orderBy('id')->limit(500)->get() as $p) {
            $key = strtolower(trim((string) $p->username));
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $rows[] = [
                'id'                   => (int) $p->id,
                'patient_id'           => (string) ($p->patient_id ?? ('PT-' . $p->id)),
                'full_name'            => (string) ($p->full_name ?: 'Unnamed Patient'),
                'username'             => (string) $p->username,
                'email'                => (string) ($p->email ?? ''),
                'is_verified'          => (bool) ((int) ($p->is_verified ?? 0) === 1),
                'account_status'       => $hasAccountStatus ? strtolower((string) ($p->account_status ?: 'active')) : 'active',
                'must_change_password' => $hasMustChange ? (bool) ((int) ($p->must_change_password ?? 0) === 1) : false,
                'created_at'           => $p->created_at ?? null,
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => $rows,
        ]);
    }

    public function generatePatientTempPassword(Request $request, int $patient): JsonResponse
    {
        $record = $this->findPatientAccount($patient);
        if (! $record) {
            return response()->json(['success' => false, 'message' => 'Patient account not found.'], 404);
        }

        $tempPassword = $this->generateReadablePassword('LKD');
        $update = ['password' => Hash::make($tempPassword)];
        if (Schema::hasColumn('patients', 'must_change_password')) {
            $update['must_change_password'] = 1;
        }
        if (Schema::hasColumn('patients', 'updated_at')) {
            $update['updated_at'] = now();
        }

        DB::table('patients')->where('id', $patient)->update($update);
        $this->revokePatientRememberTokens($patient);
        $this->logAudit($request, 'PATIENT_TEMP_PASSWORD', "Generated temporary password for patient #{$patient} ({$record->username})");

        return response()->json([
            'success'            => true,
            'message'            => 'Temporary password generated. Provide it directly to the patient.',
            'temporary_password' => $tempPassword,
        ]);
    }

    public function updatePatientEmail(Request $request, int $patient): JsonResponse
    {
        $record = $this->findPatientAccount($patient);
        if (! $record) {
            return response()->json(['success' => false, 'message' => 'Patient account not found.'], 404);
        }

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);
        $newEmail = strtolower(trim($data['email']));

        $conflict = DB::table('patients')
            ->whereRaw('LOWER(email) = ?', [$newEmail])
            ->where('id', '<>', $patient)
            ->whereNotNull('username')
            ->where('username', '<>', '')
            ->whereRaw('LOWER(username) <> ?', [strtolower((string) $record->username)])
            ->exists();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'That email address is already registered to another patient account.',
                'errors'  => ['email' => ['Email address is already in use by another account.']],
            ], 422);
        }

        $update = [
            'email'       => $newEmail,
            'is_verified' => 0,
        ];
        if (Schema::hasColumn('patients', 'otp_code')) {
            $update['otp_code'] = null;
        }
        if (Schema::hasColumn('patients', 'otp_hash')) {
            $update['otp_hash'] = null;
        }
        if (Schema::hasColumn('patients', 'otp_expires_at')) {
            $update['otp_expires_at'] = null;
        }
        if (Schema::hasColumn('patients', 'otp_attempts')) {
            $update['otp_attempts'] = 0;
        }
        if (Schema::hasColumn('patients', 'updated_at')) {
            $update['updated_at'] = now();
        }

        DB::table('patients')->where('id', $patient)->update($update);
        $this->revokePatientRememberTokens($patient);
        $this->logAudit($request, 'PATIENT_EMAIL_UPDATE', "Updated email for patient #{$patient} ({$record->username}); OTP re-verification required on next login");

        return response()->json([
            'success' => true,
            'message' => 'Email updated. Patient must re-verify via OTP on next login.',
            'data'    => [
                'id'          => $patient,
                'email'       => $newEmail,
                'is_verified' => false,
            ],
        ]);
    }

    public function togglePatientStatus(Request $request, int $patient): JsonResponse
    {
        $record = $this->findPatientAccount($patient);
        if (! $record) {
            return response()->json(['success' => false, 'message' => 'Patient account not found.'], 404);
        }

        $data = $request->validate([
            'account_status' => ['sometimes', Rule::in(['active', 'suspended'])],
        ]);

        $current = strtolower((string) ($record->account_status ?? 'active'));
        $next    = $data['account_status'] ?? ($current === 'suspended' ? 'active' : 'suspended');

        $update = ['account_status' => $next];
        if (Schema::hasColumn('patients', 'updated_at')) {
            $update['updated_at'] = now();
        }

        DB::table('patients')->where('id', $patient)->update($update);
        if ($next === 'suspended') {
            $this->revokePatientRememberTokens($patient);
        }
        $this->logAudit($request, 'PATIENT_ACCOUNT_STATUS', "Set patient #{$patient} ({$record->username}) account_status={$next}");

        return response()->json([
            'success' => true,
            'message' => $next === 'suspended' ? 'Patient account suspended.' : 'Patient account restored.',
            'data'    => [
                'id'             => $patient,
                'account_status' => $next,
            ],
        ]);
    }

    private function findPatientAccount(int $id): ?object
    {
        if (! Schema::hasTable('patients')) {
            return null;
        }

        return DB::table('patients')
            ->where('id', $id)
            ->whereNotNull('username')
            ->where('username', '<>', '')
            ->first();
    }

    private function revokePatientRememberTokens(int $patientId): void
    {
        if (Schema::hasTable('patient_remember_tokens')) {
            DB::table('patient_remember_tokens')->where('patient_db_id', $patientId)->delete();
        }
    }

    private function generateReadablePassword(string $prefix): string
    {
        $digits = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $chars  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $suffix = $chars[random_int(0, strlen($chars) - 1)] . $chars[random_int(0, strlen($chars) - 1)];

        return "{$prefix}-{$digits}-{$suffix}";
    }

    private function logAudit(Request $request, string $action, string $details): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }
        try {
            $actor = $request->user()?->username ?? $request->user()?->name ?? ($_SESSION['user'] ?? 'admin');
            DB::table('audit_logs')->insert([
                'user'       => $actor,
                'action'     => $action,
                'details'    => $details,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Non-blocking audit log insert
        }
    }
}
