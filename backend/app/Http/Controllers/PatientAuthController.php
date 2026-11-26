<?php

namespace App\Http\Controllers;

use App\Mail\PatientOtpMail;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PatientAuthController extends Controller
{
    private const OTP_TTL_MINUTES = 5;
    private const RESEND_COOLDOWN_SECONDS = 60;

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identifier = strtolower(trim($credentials['email']));

        $patient = DB::table('patients')
            ->where(function ($q) use ($identifier) {
                $q->whereRaw('LOWER(email) = ?', [$identifier])
                  ->orWhereRaw('LOWER(username) = ?', [$identifier]);
            })
            ->whereNotNull('password')
            ->where('password', '<>', '')
            ->orderByDesc('is_verified')
            ->orderBy('id')
            ->first();

        if (! $patient || ! Hash::check($credentials['password'], $patient->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 422);
        }

        if (strtolower((string) ($patient->account_status ?? 'active')) === 'suspended') {
            return response()->json([
                'success'   => false,
                'suspended' => true,
                'message'   => 'This patient account has been suspended. Please contact the hospital administration desk.',
            ], 403);
        }

        if (empty($patient->is_verified) || ! empty($patient->is_otp_enabled)) {
            if (empty($patient->email) || ! filter_var($patient->email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email OTP verification is required, but no valid email is on file.',
                ], 403);
            }

            $otpToken = $this->createAndSendOtp($patient);

            return response()->json([
                'success'         => false,
                'otp_required'    => true,
                'otp_token'       => $otpToken,
                'masked_email'    => $this->maskEmail($patient->email),
                'expires_in'      => self::OTP_TTL_MINUTES * 60,
                'resend_cooldown' => self::RESEND_COOLDOWN_SECONDS,
            ]);
        }

        return $this->issueAuthResponse($request, $patient);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'otp_token' => ['required', 'string'],
            'otp'       => ['required', 'digits:6'],
        ]);

        $challenge = Cache::get($this->cacheKey($data['otp_token']));
        $patientId = is_array($challenge) ? ($challenge['patient_id'] ?? null) : null;
        $patient   = $patientId ? DB::table('patients')->where('id', $patientId)->first() : null;

        $storedHash = $patient->otp_code ?? ($patient->otp_hash ?? null);
        $expiresAt  = ! empty($patient->otp_expires_at) ? Carbon::parse($patient->otp_expires_at) : null;
        $isExpired  = ! $expiresAt || now()->greaterThanOrEqualTo($expiresAt) || ((int) ($challenge['expires_at'] ?? 0) <= time());

        if (! $patient || ! $storedHash || $isExpired) {
            if ($patient) {
                DB::table('patients')->where('id', $patient->id)->update([
                    'otp_code'       => null,
                    'otp_expires_at' => null,
                    'updated_at'     => now(),
                ]);
            }

            return response()->json([
                'success' => false,
                'expired' => true,
                'message' => 'Your verification code has expired. Please click Resend Code to request a new one.',
            ], 422);
        }

        if (! Hash::check($data['otp'], $storedHash)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code.',
            ], 422);
        }

        DB::table('patients')->where('id', $patient->id)->update([
            'is_verified'    => 1,
            'otp_code'       => null,
            'otp_expires_at' => null,
            'updated_at'     => now(),
        ]);
        Cache::forget($this->cacheKey($data['otp_token']));

        return $this->issueAuthResponse($request, $patient);
    }

    public function resendOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'otp_token' => ['required', 'string'],
        ]);

        $challenge = Cache::get($this->cacheKey($data['otp_token']));
        if (! is_array($challenge) || empty($challenge['patient_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Verification session expired. Please sign in again.',
            ], 422);
        }

        $elapsed = time() - (int) ($challenge['sent_at'] ?? 0);
        if ($elapsed < self::RESEND_COOLDOWN_SECONDS) {
            $wait = self::RESEND_COOLDOWN_SECONDS - $elapsed;
            return response()->json([
                'success'     => false,
                'retry_after' => $wait,
                'message'     => "Please wait {$wait}s before requesting another code.",
            ], 429);
        }

        $patient = DB::table('patients')->where('id', $challenge['patient_id'])->first();
        if (! $patient) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
            ], 404);
        }

        $this->createAndSendOtp($patient, $data['otp_token']);

        return response()->json([
            'success'         => true,
            'message'         => 'A new verification code has been sent.',
            'expires_in'      => self::OTP_TTL_MINUTES * 60,
            'resend_cooldown' => self::RESEND_COOLDOWN_SECONDS,
        ]);
    }

    private function createAndSendOtp(object $patient, ?string $existingToken = null): string
    {
        $otp       = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpToken  = $existingToken ?? Str::random(64);
        $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);

        DB::table('patients')->where('id', $patient->id)->update([
            'otp_code'       => Hash::make($otp),
            'otp_expires_at' => $expiresAt,
            'updated_at'     => now(),
        ]);

        // Keep challenge token valid for 15 minutes so patient can resend after a 5-minute OTP expires.
        Cache::put($this->cacheKey($otpToken), [
            'patient_id' => $patient->id,
            'sent_at'    => time(),
            'expires_at' => $expiresAt->timestamp,
        ], now()->addMinutes(15));

        Mail::to($patient->email)->send(new PatientOtpMail(
            $otp,
            $patient->full_name ?: 'Patient',
            self::OTP_TTL_MINUTES
        ));

        return $otpToken;
    }

    private function issueAuthResponse(Request $request, object $patient): JsonResponse
    {
        $token = Str::random(64);
        Cache::put("patient_api_token:{$token}", [
            'patient_id' => $patient->patient_id,
            'email'      => $patient->email,
            'user_id'    => $patient->user_id ?? null,
        ], now()->addHours(12));

        if ($request->hasSession()) {
            $request->session()->regenerate();
            $request->session()->put([
                'patient_logged_in' => true,
                'patient_id'        => $patient->patient_id,
                'patient_name'      => $patient->full_name,
                'patient_email'     => $patient->email,
                'user_id'           => $patient->user_id ?? null,
            ]);
        }

        return response()->json([
            'success'      => true,
            'otp_required' => false,
            'token'        => $token,
            'patient'      => [
                'id'         => $patient->id,
                'patient_id' => $patient->patient_id,
                'name'       => $patient->full_name,
                'email'      => $patient->email,
            ],
            'redirect' => '/sdn/pages/patient-dashboard.php',
        ]);
    }

    private function cacheKey(string $token): string
    {
        return "patient_otp_challenge:{$token}";
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, min(2, strlen($local)));

        return "{$visible}***@{$domain}";
    }
}
