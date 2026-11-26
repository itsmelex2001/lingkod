<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingsService
{
    public const CACHE_KEY = 'lingkod.settings.grouped';
    public const SECRET_MASK = '••••••••';

    private const SECRET_KEYS = ['otp_api_key'];
    private const JSON_KEYS = ['clinic_hours_json'];
    private const INT_KEYS = [
        'qr_code_expiry_hours',
        'otp_expiry_minutes',
        'otp_max_attempts',
        'username_min_length',
        'username_max_length',
        'session_timeout_minutes',
        'patient_session_timeout_minutes',
    ];
    private const BOOL_KEYS = ['auto_backup_enabled'];

    public function getAllGrouped(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            $rows = SystemSetting::all();
            $grouped = [
                'general' => [],
                'scheduling' => [],
                'auth' => [],
                'system' => [],
            ];
            $lastUpdated = null;

            foreach ($rows as $row) {
                $val = $this->castForRead($row);
                $grouped[$row->group_key][$row->setting_key] = $val;

                if ($row->value_type === 'secret') {
                    $grouped[$row->group_key]['has_' . $row->setting_key] = !empty($row->setting_value);
                }

                if ($row->updated_at && (!$lastUpdated || $row->updated_at->gt($lastUpdated))) {
                    $lastUpdated = $row->updated_at;
                }
            }

            if (isset($grouped['general']['clinic_hours_json'])) {
                $grouped['general']['clinic_hours'] = $grouped['general']['clinic_hours_json'];
                unset($grouped['general']['clinic_hours_json']);
            }

            $grouped['meta'] = [
                'updated_at' => $lastUpdated?->toIso8601String(),
            ];

            return $grouped;
        });
    }

    public function updateGroup(string $group, array $values, ?int $actorId = null, string $actorName = 'admin'): array
    {
        $changedKeys = [];

        foreach ($values as $key => $raw) {
            $dbKey = $key === 'clinic_hours' ? 'clinic_hours_json' : $key;
            $type = $this->inferType($dbKey);

            if ($type === 'secret') {
                $trimmed = is_string($raw) ? trim($raw) : '';
                if ($trimmed === '' || $trimmed === self::SECRET_MASK) {
                    continue;
                }
                $stored = Crypt::encryptString($trimmed);
            } elseif ($type === 'json') {
                $stored = json_encode($raw);
            } elseif ($type === 'boolean') {
                $stored = $raw ? '1' : '0';
            } elseif ($type === 'integer') {
                $stored = (string) ((int) $raw);
            } else {
                $stored = trim((string) ($raw ?? ''));
            }

            SystemSetting::updateOrCreate(
                ['setting_key' => $dbKey],
                [
                    'group_key' => $group,
                    'setting_value' => $stored,
                    'value_type' => $type,
                    'updated_by' => $actorId,
                ]
            );

            $changedKeys[] = $dbKey;
        }

        Cache::forget(self::CACHE_KEY);

        if (!empty($changedKeys)) {
            $this->writeAuditLog(
                'info',
                'System Settings Updated',
                $actorName,
                sprintf('Updated %s settings: %s', $group, implode(', ', $changedKeys))
            );
        }

        return $changedKeys;
    }

    public function getDecryptedSecret(string $key): ?string
    {
        $row = SystemSetting::where('setting_key', $key)->first();
        if (!$row || empty($row->setting_value)) {
            return null;
        }
        return Crypt::decryptString($row->setting_value);
    }

    public function writeAuditLog(string $level, string $action, string $user, string $details): void
    {
        if (!Schema::hasTable('system_logs')) {
            return;
        }
        DB::table('system_logs')->insert([
            'level' => $level,
            'action' => $action,
            'user' => substr($user, 0, 60),
            'details' => $details,
            'created_at' => now(),
        ]);
    }

    private function castForRead(SystemSetting $row): mixed
    {
        if ($row->value_type === 'secret') {
            return empty($row->setting_value) ? '' : self::SECRET_MASK;
        }
        if ($row->value_type === 'json') {
            return json_decode((string) $row->setting_value, true) ?? [];
        }
        if ($row->value_type === 'integer') {
            return (int) $row->setting_value;
        }
        if ($row->value_type === 'boolean') {
            return $row->setting_value === '1' || $row->setting_value === 'true';
        }
        return (string) ($row->setting_value ?? '');
    }

    private function inferType(string $key): string
    {
        if (in_array($key, self::SECRET_KEYS, true)) return 'secret';
        if (in_array($key, self::JSON_KEYS, true)) return 'json';
        if (in_array($key, self::INT_KEYS, true)) return 'integer';
        if (in_array($key, self::BOOL_KEYS, true)) return 'boolean';
        return 'string';
    }
}
