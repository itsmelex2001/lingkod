<?php
/**
 * api/services/SmsService.php
 * Automated & Manual SMS Appointment Reminder Service for MMWGH Linkod.
 * Integrates third-party SMS API gateway (e.g. Semaphore, PhilSMS, Twilio)
 * with robust local simulation, detailed logging, and database synchronization.
 */

class SmsService {
    private PDO $pdo;
    private string $apiKey;
    private string $apiUrl;
    private string $senderName;
    private bool $isPlaceholder;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->loadConfig();
    }

    /**
     * Load SMS credentials and configuration from .env or system environment
     */
    private function loadConfig(): void {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                [$key, $value] = array_map('trim', explode('=', $line, 2));
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }

        $this->apiKey     = $_ENV['SMS_API_KEY'] ?? getenv('SMS_API_KEY') ?: 'PLACEHOLDER_MMWGH_KEY';
        $this->apiUrl     = $_ENV['SMS_API_URL'] ?? getenv('SMS_API_URL') ?: 'https://api.semaphore.co/api/v4/messages';
        $this->senderName = $_ENV['SMS_SENDER_NAME'] ?? getenv('SMS_SENDER_NAME') ?: 'MMWGH';
        
        $enabled = strtolower($_ENV['SMS_ENABLED'] ?? getenv('SMS_ENABLED') ?: 'false');

        // Check if in placeholder / simulation mode
        $this->isPlaceholder = ($enabled === 'false' || $enabled === '0')
            || empty($this->apiKey) 
            || str_starts_with($this->apiKey, 'PLACEHOLDER');
    }

    /**
     * Format a polite, professional SMS message for patient appointment reminder
     */
    public function formatReminderMessage(array $patient): string {
        $name = trim($patient['full_name'] ?? 'Patient');
        
        $date = !empty($patient['appointment_date']) 
            ? date('l, F j, Y', strtotime($patient['appointment_date'])) 
            : 'your scheduled date';

        $time = !empty($patient['scheduled_time']) 
            ? date('g:i A', strtotime($patient['scheduled_time'])) 
            : 'Clinic Hours';
        
        $doctorRaw = trim($patient['assigned_doctor'] ?? '');
        if (!empty($doctorRaw) && $doctorRaw !== 'None') {
            $doctor = preg_match('/^(dr\.|doctor)/i', $doctorRaw) ? $doctorRaw : "Dr. " . $doctorRaw;
        } else {
            $doctor = $patient['department'] ?? 'Outpatient Department';
        }

        return "MMWGH Linkod: Good day, {$name}! This is a reminder for your upcoming medical consultation on {$date} at {$time} ({$doctor}) at Mariveles Mental Wellness and General Hospital. Please arrive 15 minutes before your schedule and bring a valid ID. For inquiries, call (047) 935-4610. Thank you!";
    }

    /**
     * Clean and format Philippine mobile number (e.g. 09171234567 or +639171234567)
     */
    public function cleanPhoneNumber(?string $number): string {
        if (!$number) return '';
        $cleaned = preg_replace('/[^0-9+]/', '', $number);
        
        if (str_starts_with($cleaned, '+63')) {
            $cleaned = '0' . substr($cleaned, 3);
        } elseif (str_starts_with($cleaned, '63') && strlen($cleaned) === 12) {
            $cleaned = '0' . substr($cleaned, 2);
        }
        return $cleaned;
    }

    /**
     * Validate Philippine mobile number (11 digits starting with 09)
     */
    public function isValidPhoneNumber(string $number): bool {
        return (bool) preg_match('/^09\d{9}$/', $number);
    }

    /**
     * Send an SMS via third-party SMS API (or placeholder sandbox simulation)
     */
    public function sendSms(string $recipient, string $message): array {
        $cleanPhone = $this->cleanPhoneNumber($recipient);
        
        if (empty($cleanPhone)) {
            return [
                'success' => false,
                'error'   => 'Patient has no contact number registered.'
            ];
        }

        if (!$this->isValidPhoneNumber($cleanPhone)) {
            return [
                'success' => false,
                'error'   => "Invalid Philippine contact number format: '$cleanPhone'. Expected 11 digits starting with 09."
            ];
        }

        // ── Placeholder / Simulated Dispatch Mode ──
        if ($this->isPlaceholder) {
            $timestamp = date('Y-m-d H:i:s');
            $simId = 'SIM-SMS-' . strtoupper(bin2hex(random_bytes(6)));

            // Log to dedicated logs/sms.log
            $logEntry = sprintf(
                "[%s] [SMS SIMULATOR] ID: %s | To: %s | Sender: %s | Message: \"%s\"\n",
                $timestamp,
                $simId,
                $cleanPhone,
                $this->senderName,
                str_replace(["\r", "\n"], ' ', $message)
            );
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
            @file_put_contents($logDir . '/sms.log', $logEntry, FILE_APPEND);

            return [
                'success'     => true,
                'simulated'   => true,
                'provider'    => 'MMWGH SMS Gateway (Simulated / Placeholder Mode)',
                'recipient'   => $cleanPhone,
                'message_id'  => $simId,
                'sender_name' => $this->senderName,
                'sent_at'     => $timestamp,
                'message'     => $message
            ];
        }

        // ── Real HTTP cURL Dispatch (e.g. Semaphore / PhilSMS API) ──
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'apikey'     => $this->apiKey,
                'number'     => $cleanPhone,
                'message'    => $message,
                'sendername' => $this->senderName
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return ['success' => false, 'error' => "cURL Network Error: $curlError"];
            }

            $decoded = json_decode($response, true);
            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success'     => true,
                    'simulated'   => false,
                    'provider'    => 'Semaphore API',
                    'recipient'   => $cleanPhone,
                    'response'    => $decoded,
                    'sent_at'     => date('Y-m-d H:i:s')
                ];
            } else {
                return [
                    'success' => false,
                    'error'   => "SMS Gateway returned HTTP $httpCode: " . ($decoded['message'] ?? $response)
                ];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send reminder to a single patient by patient_id
     */
    public function sendAppointmentReminder(string $patientId, string $actor = 'Staff'): array {
        $stmt = $this->pdo->prepare(
            "SELECT patient_id, full_name, department, status, appointment_date, 
                    scheduled_time, contact_number, assigned_doctor, reminder_status
             FROM patients WHERE patient_id = ? LIMIT 1"
        );
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch();

        if (!$patient) {
            return ['success' => false, 'error' => "Patient $patientId not found."];
        }

        $message = $this->formatReminderMessage($patient);
        $smsResult = $this->sendSms($patient['contact_number'] ?? '', $message);

        if ($smsResult['success']) {
            $sentAt = $smsResult['sent_at'] ?? date('Y-m-d H:i:s');
            $upd = $this->pdo->prepare(
                "UPDATE patients SET reminder_status = 'Sent', reminder_sent_at = ?, updated_at = NOW() WHERE patient_id = ?"
            );
            $upd->execute([$sentAt, $patientId]);

            $phone = $smsResult['recipient'] ?? ($patient['contact_number'] ?? '');
            $this->logActivity('info', 'SMS Reminder Sent', $actor, 
                "SMS appointment reminder sent to {$patient['full_name']} ($phone) for appointment on {$patient['appointment_date']}.");

            return array_merge($smsResult, [
                'patient_id'   => $patientId,
                'patient_name' => $patient['full_name'],
                'sent_at'      => $sentAt
            ]);
        } else {
            $upd = $this->pdo->prepare(
                "UPDATE patients SET reminder_status = 'Failed', updated_at = NOW() WHERE patient_id = ?"
            );
            $upd->execute([$patientId]);

            $this->logActivity('warning', 'SMS Reminder Failed', $actor, 
                "Failed to send SMS reminder to {$patient['full_name']} ($patientId): {$smsResult['error']}");

            return $smsResult;
        }
    }

    /**
     * Query patients with 'Approved' status whose scheduled dates are exactly 2 to 3 days in the future
     *
     * @param int $minDays (default 2)
     * @param int $maxDays (default 3)
     * @param bool $onlyPending Whether to filter out patients who have already been sent a reminder
     * @return array
     */
    public function queryDueAppointments(int $minDays = 2, int $maxDays = 3, bool $onlyPending = true): array {
        $pendingClause = $onlyPending ? "AND (reminder_status IS NULL OR reminder_status != 'Sent')" : "";
        
        $sql = "SELECT id, patient_id, full_name, department, status, appointment_date, 
                       scheduled_time, contact_number, assigned_doctor, specialty, 
                       reminder_status, reminder_sent_at
                FROM patients
                WHERE LOWER(status) = 'approved'
                  AND appointment_date BETWEEN DATE_ADD(CURDATE(), INTERVAL ? DAY) AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                  $pendingClause
                ORDER BY appointment_date ASC, scheduled_time ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$minDays, $maxDays]);
        return $stmt->fetchAll();
    }

    /**
     * Run daily automated reminders (intended for scheduled CRON task)
     */
    public function runDailyReminders(string $actor = 'CRON Task', bool $force = false): array {
        $startTime = microtime(true);
        $duePatients = $this->queryDueAppointments(2, 3, !$force);
        
        $results = [
            'timestamp'       => date('Y-m-d H:i:s'),
            'execution_mode'  => $this->isPlaceholder ? 'Placeholder / Sandbox' : 'Live Gateway',
            'min_date'        => date('Y-m-d', strtotime('+2 days')),
            'max_date'        => date('Y-m-d', strtotime('+3 days')),
            'total_found'     => count($duePatients),
            'sent_count'      => 0,
            'failed_count'    => 0,
            'processed'       => []
        ];

        foreach ($duePatients as $p) {
            $sendRes = $this->sendAppointmentReminder($p['patient_id'], $actor);
            
            $item = [
                'patient_id'       => $p['patient_id'],
                'full_name'        => $p['full_name'],
                'contact_number'   => $p['contact_number'],
                'appointment_date' => $p['appointment_date'],
                'scheduled_time'   => $p['scheduled_time'],
                'department'       => $p['department'],
                'status'           => $sendRes['success'] ? 'Sent' : 'Failed',
                'details'          => $sendRes['success'] ? ($sendRes['provider'] ?? 'Sent') : ($sendRes['error'] ?? 'Error')
            ];

            if ($sendRes['success']) {
                $results['sent_count']++;
            } else {
                $results['failed_count']++;
            }

            $results['processed'][] = $item;
        }

        $results['duration_seconds'] = round(microtime(true) - $startTime, 3);
        
        // Log overall summary to system_logs
        $summary = "Automated daily SMS CRON completed: {$results['sent_count']} sent, {$results['failed_count']} failed out of {$results['total_found']} eligible appointments.";
        $this->logActivity('info', 'Daily SMS CRON Run', $actor, $summary);

        return $results;
    }

    private function logActivity(string $level, string $action, string $user, string $details): void {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO system_logs (level, action, user, details) VALUES (?, ?, ?, ?)");
            $stmt->execute([$level, $action, $user, $details]);
        } catch (Exception $e) {
            // ignore logging error
        }
    }
}
