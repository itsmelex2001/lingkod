<?php
declare(strict_types=1);

/**
 * api/services/SmsService.php
 * Automated SMS Notification Service for MMWGH LINGKOD.
 * Integrates Semaphore SMS API (Philippines) with simulation and error isolation.
 *
 * CRITICAL PRIVACY RULE:
 * Because this is a mental wellness and general hospital, we strictly comply with data privacy laws.
 * NEVER include department name, doctor's name, chief complaint, or diagnosis in any SMS.
 * Content is strictly logistical (Date, Time, Reference ID, Outpatient Triage instructions).
 */

class SmsService
{
    private const SEMAPHORE_API_URL = 'https://api.semaphore.co/api/v4/messages';

    /**
     * Clean and normalize a Philippine mobile number into 09XXXXXXXXX or 639XXXXXXXXX
     */
    public static function cleanMobileNumber(?string $rawNumber): ?string
    {
        if (!$rawNumber) return null;
        $digits = preg_replace('/[^0-9]/', '', $rawNumber);

        if (strlen($digits) === 11 && str_starts_with($digits, '09')) {
            return $digits;
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '639')) {
            return '0' . substr($digits, 2);
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            return '0' . $digits;
        }

        return null;
    }

    /**
     * Send an SMS message via Semaphore API
     *
     * @param string $mobileNumber
     * @param string $message (Must be <= 160 characters for single SMS credit)
     * @return bool True if successfully sent or simulated, false on failure
     */
    public static function sendSms(string $mobileNumber, string $message): bool
    {
        $cleanNumber = self::cleanMobileNumber($mobileNumber);
        if (!$cleanNumber) {
            error_log("[SmsService] Invalid mobile number: {$mobileNumber}");
            return false;
        }

        // Load .env if not loaded
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                [$k, $v] = array_map('trim', explode('=', $line, 2));
                if (!isset($_ENV[$k])) {
                    $_ENV[$k] = $v;
                    putenv("$k=$v");
                }
            }
        }

        $apiKey     = $_ENV['SMS_API_KEY'] ?? $_ENV['SEMAPHORE_API_KEY'] ?? getenv('SEMAPHORE_API_KEY') ?: '';
        $senderName = $_ENV['SMS_SENDER_NAME'] ?? $_ENV['SEMAPHORE_SENDER_NAME'] ?? getenv('SEMAPHORE_SENDER_NAME') ?: 'MMWGH';
        $apiUrl     = $_ENV['SMS_API_URL'] ?? self::SEMAPHORE_API_URL;
        $enabled    = strtolower($_ENV['SMS_ENABLED'] ?? 'true');

        // Check if simulation / test mode is active
        $isSimulation = ($enabled === 'false' || $enabled === '0')
            || empty($apiKey)
            || str_starts_with($apiKey, 'PLACEHOLDER');

        if ($isSimulation) {
            $logEntry = sprintf(
                "[%s] [SIMULATED SMS] To: %s | Len: %d | Msg: %s",
                date('Y-m-d H:i:s'),
                $cleanNumber,
                strlen($message),
                $message
            );
            if (getenv('VERCEL') === '1') {
                error_log($logEntry);
            } else {
                $logDir = __DIR__ . '/../../logs';
                if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
                @file_put_contents($logDir . '/sms_simulated.log', $logEntry . "\n", FILE_APPEND);
            }
            return true;
        }

        $payload = [
            'apikey'     => $apiKey,
            'number'     => $cleanNumber,
            'message'    => $message,
            'sendername' => $senderName,
        ];

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                error_log("[SmsService] cURL error connecting to Semaphore: {$curlErr}");
                return false;
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                return true;
            }

            error_log("[SmsService] Semaphore returned HTTP {$httpCode}: {$response}");
            return false;
        } catch (\Throwable $e) {
            error_log("[SmsService] Unexpected exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Standard Template: Appointment Confirmed (~135 chars)
     */
    public static function sendConfirmationSms(string $mobileNumber, string $date, string $time, string $refId, string $consultationMode = 'Face-to-Face'): bool
    {
        if (strcasecmp(trim($consultationMode), 'Teleconsultation') === 0) {
            $msg = "LINGKOD MMWGH: Your TELECONSULTATION for {$date} at {$time} is confirmed (Ref: {$refId}). Check your Patient Portal for session details. Do not reply.";
        } else {
            $msg = "LINGKOD MMWGH: Your appointment for {$date} at {$time} is confirmed. Present Ref ID: {$refId} at the Outpatient Triage. Do not reply.";
        }
        return self::sendSms($mobileNumber, $msg);
    }

    /**
     * Standard Template: 1-Day Reminder (~136 chars)
     */
    public static function sendOneDayReminderSms(string $mobileNumber, string $date, string $time, string $refId, string $consultationMode = 'Face-to-Face'): bool
    {
        if (strcasecmp(trim($consultationMode), 'Teleconsultation') === 0) {
            $msg = "LINGKOD MMWGH: Reminder for your TELECONSULTATION TOMORROW ({$date}) at {$time} (Ref: {$refId}). Check your Patient Portal for link/call details. Do not reply.";
        } else {
            $msg = "LINGKOD MMWGH: Reminder for your appointment TOMORROW ({$date}) at {$time}. Present Ref ID: {$refId} at Outpatient Triage. Do not reply.";
        }
        return self::sendSms($mobileNumber, $msg);
    }

    /**
     * Standard Template: Day-Of / 2-Hour Reminder (~133 chars)
     */
    public static function sendDayOfReminderSms(string $mobileNumber, string $time, string $refId, string $consultationMode = 'Face-to-Face'): bool
    {
        if (strcasecmp(trim($consultationMode), 'Teleconsultation') === 0) {
            $msg = "LINGKOD MMWGH: Notice for your TELECONSULTATION TODAY at {$time} (Ref: {$refId}). Open your Patient Portal for session details. Do not reply.";
        } else {
            $msg = "LINGKOD MMWGH: Notice for your appointment TODAY at {$time}. Please arrive at Outpatient Triage with Ref ID: {$refId}. Do not reply.";
        }
        return self::sendSms($mobileNumber, $msg);
    }
}
