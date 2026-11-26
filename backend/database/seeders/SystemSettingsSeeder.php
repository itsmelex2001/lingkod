<?php

namespace Database\Seeders;

use App\Models\ChatbotFaq;
use App\Models\DepartmentSchedulingRule;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaultClinicHours = [
            'monday'    => ['is_open' => true,  'open' => '08:00', 'close' => '17:00'],
            'tuesday'   => ['is_open' => true,  'open' => '08:00', 'close' => '17:00'],
            'wednesday' => ['is_open' => true,  'open' => '08:00', 'close' => '17:00'],
            'thursday'  => ['is_open' => true,  'open' => '08:00', 'close' => '17:00'],
            'friday'    => ['is_open' => true,  'open' => '08:00', 'close' => '17:00'],
            'saturday'  => ['is_open' => false, 'open' => '08:00', 'close' => '12:00'],
            'sunday'    => ['is_open' => false, 'open' => '08:00', 'close' => '12:00'],
        ];

        $settings = [
            ['general', 'hospital_name', 'Mariveles Mental Wellness and General Hospital', 'string', 'Official facility name'],
            ['general', 'hospital_tagline', 'Localized Integrated Network for Governance, Knowledge, and Operation in Digital Healthcare', 'string', 'Institutional tagline'],
            ['general', 'contact_phone', '(047) 935-4645', 'string', 'Hospital main landline'],
            ['general', 'emergency_hotline', '(047) 935-4138', 'string', '24/7 Emergency Department hotline'],
            ['general', 'contact_email', 'support@mmwgh.gov.ph', 'string', 'Public support email'],
            ['general', 'hospital_address', 'P. Monroe Street, Poblacion, Mariveles, Bataan, Philippines 2105', 'string', 'Hospital physical address'],
            ['general', 'clinic_hours_json', json_encode($defaultClinicHours), 'json', 'Outpatient clinic weekly hours'],
            ['general', 'hospital_holidays', '[]', 'json', 'Hospital holidays and scheduled closed dates'],

            ['scheduling', 'qr_code_expiry_hours', '48', 'integer', 'Default hours before a patient appointment QR slip expires'],

            ['auth', 'otp_provider', 'smtp', 'string', 'Active OTP delivery channel (smtp, semaphore, twilio, log)'],
            ['auth', 'otp_api_url', '', 'string', 'External SMS/OTP API endpoint URL'],
            ['auth', 'otp_api_key', '', 'secret', 'Encrypted OTP API key or SMTP app password'],
            ['auth', 'otp_sender_id', 'MMWGH-LINGKOD', 'string', 'OTP sender name or SMS sender ID'],
            ['auth', 'otp_expiry_minutes', '10', 'integer', 'Minutes before an issued OTP code expires'],
            ['auth', 'otp_max_attempts', '5', 'integer', 'Maximum failed OTP verification attempts before lockout'],
            ['auth', 'username_min_length', '6', 'integer', 'Minimum character length for patient usernames'],
            ['auth', 'username_max_length', '32', 'integer', 'Maximum character length for patient usernames'],
            ['auth', 'username_pattern', 'alphanumeric_dash', 'string', 'Allowed patient username character format'],
            ['auth', 'username_prefix', 'PT', 'string', 'Optional auto-generated patient ID prefix'],

            ['system', 'session_timeout_minutes', '60', 'integer', 'Admin and staff idle session timeout in minutes'],
            ['system', 'patient_session_timeout_minutes', '120', 'integer', 'Patient portal idle session timeout in minutes'],
            ['system', 'auto_backup_enabled', '0', 'boolean', 'Enable scheduled MySQL backup snapshots'],
            ['system', 'auto_backup_frequency', 'daily', 'string', 'Scheduled backup cadence (daily or weekly)'],
        ];

        foreach ($settings as [$group, $key, $value, $type, $desc]) {
            SystemSetting::firstOrCreate(
                ['setting_key' => $key],
                [
                    'group_key' => $group,
                    'setting_value' => $value,
                    'value_type' => $type,
                    'description' => $desc,
                ]
            );
        }

        $departments = [
            [
                'department_code' => 'general',
                'display_name' => 'General Medical',
                'daily_capacity_limit' => 60,
                'am_slot_limit' => 35,
                'pm_slot_limit' => 25,
                'booking_cutoff_time' => '16:00',
                'qr_expiry_hours_override' => null,
                'allow_same_day_booking' => false,
                'is_active' => true,
            ],
            [
                'department_code' => 'neurology',
                'display_name' => 'Neurology',
                'daily_capacity_limit' => 30,
                'am_slot_limit' => 20,
                'pm_slot_limit' => 10,
                'booking_cutoff_time' => '15:00',
                'qr_expiry_hours_override' => null,
                'allow_same_day_booking' => false,
                'is_active' => true,
            ],
            [
                'department_code' => 'psychiatry',
                'display_name' => 'Psychiatry',
                'daily_capacity_limit' => 40,
                'am_slot_limit' => 25,
                'pm_slot_limit' => 15,
                'booking_cutoff_time' => '15:30',
                'qr_expiry_hours_override' => null,
                'allow_same_day_booking' => false,
                'is_active' => true,
            ],
        ];

        foreach ($departments as $dept) {
            DepartmentSchedulingRule::firstOrCreate(
                ['department_code' => $dept['department_code']],
                $dept
            );
        }

        $faqs = [
            [
                'slug' => 'request-appointment',
                'locale' => 'en',
                'persona' => 'both',
                'question' => 'How do I request an appointment/schedule?',
                'answer_html' => "Click <strong>'Book Appointment'</strong> on the homepage, register or sign in, and submit your consultation request. Our clinic staff will review and confirm your schedule.",
                'keywords_json' => ['request', 'appointment', 'schedule', 'book', 'reserve', 'slot'],
                'follow_ups_json' => ['What are the hospital\'s operating hours?', 'What should I bring for my consultation?'],
                'sort_order' => 1,
            ],
            [
                'slug' => 'hospital-hours',
                'locale' => 'en',
                'persona' => 'dr_leo',
                'question' => "What are the hospital's operating hours?",
                'answer_html' => 'The <strong>Emergency Department</strong> operates 24/7. <strong>Outpatient (OPD) specialty clinics</strong> operate Monday to Friday from <strong>8:00 AM to 5:00 PM</strong>.',
                'keywords_json' => ['hours', 'operating', 'schedule', 'time', 'open', 'opd'],
                'follow_ups_json' => ['How do I request an appointment/schedule?', 'What should I bring for my consultation?'],
                'sort_order' => 2,
            ],
            [
                'slug' => 'consultation-requirements',
                'locale' => 'en',
                'persona' => 'nurse_mika',
                'question' => 'What should I bring for my consultation?',
                'answer_html' => 'Please bring a valid ID, your printed or digital <strong>Lingkod QR appointment slip</strong>, and any prior prescriptions or lab results.',
                'keywords_json' => ['bring', 'requirements', 'id', 'qr', 'slip', 'triage'],
                'follow_ups_json' => ['How do I check my appointment status?', "What are the hospital's operating hours?"],
                'sort_order' => 3,
            ],
            [
                'slug' => 'request-appointment',
                'locale' => 'tl',
                'persona' => 'both',
                'question' => 'Paano ako magpapa-appointment?',
                'answer_html' => "I-click ang <strong>'Magpa-appointment'</strong> sa homepage, mag-sign in o magrehistro, at isumite ang form. Susuriin at kukumpirmahin ng aming staff ang iyong iskedyul.",
                'keywords_json' => ['appointment', 'iskedyul', 'book', 'magpa-appointment'],
                'follow_ups_json' => ['Ano ang oras ng operasyon ng ospital?', 'Ano ang dapat kong dalhin sa konsultasyon?'],
                'sort_order' => 1,
            ],
            [
                'slug' => 'hospital-hours',
                'locale' => 'tl',
                'persona' => 'dr_leo',
                'question' => 'Ano ang oras ng operasyon ng ospital?',
                'answer_html' => 'Bukas ang <strong>Emergency Department</strong> 24/7. Ang mga <strong>Outpatient (OPD) clinic</strong> ay bukas Lunes hanggang Biyernes mula <strong>8:00 AM hanggang 5:00 PM</strong>.',
                'keywords_json' => ['oras', 'bukas', 'operasyon', 'opd', 'clinic'],
                'follow_ups_json' => ['Paano ako magpapa-appointment?', 'Ano ang dapat kong dalhin sa konsultasyon?'],
                'sort_order' => 2,
            ],
            [
                'slug' => 'consultation-requirements',
                'locale' => 'tl',
                'persona' => 'nurse_mika',
                'question' => 'Ano ang dapat kong dalhin sa konsultasyon?',
                'answer_html' => 'Magdala ng valid ID, ang iyong <strong>Lingkod QR appointment slip</strong>, at anumang dating reseta o resulta ng laboratoryo.',
                'keywords_json' => ['dalhin', 'requirements', 'id', 'qr', 'reseta'],
                'follow_ups_json' => ['Paano ako magpapa-appointment?', 'Ano ang oras ng operasyon ng ospital?'],
                'sort_order' => 3,
            ],
        ];

        foreach ($faqs as $faq) {
            ChatbotFaq::firstOrCreate(
                ['slug' => $faq['slug'], 'locale' => $faq['locale']],
                $faq + ['is_active' => true]
            );
        }
    }
}
