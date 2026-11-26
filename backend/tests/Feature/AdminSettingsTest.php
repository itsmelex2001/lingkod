<?php

namespace Tests\Feature;

use App\Models\ChatbotFaq;
use App\Models\SystemBackupLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh');
        (new SystemSettingsSeeder())->run();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->staff = User::factory()->create(['role' => 'staff']);
    }

    public function test_unauthenticated_and_non_admin_users_are_blocked(): void
    {
        $this->getJson('/api/admin/settings')->assertStatus(401);
        $this->actingAs($this->staff)->getJson('/api/admin/settings')->assertStatus(403);
        $this->actingAs($this->staff)->postJson('/api/admin/system/backups')->assertStatus(403);
    }

    public function test_admin_can_fetch_and_update_general_settings_with_clinic_hours_validation(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/admin/settings')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.general.hospital_name', 'Mariveles Mental Wellness and General Hospital');

        // Reject closing time before opening time
        $invalidHours = [
            'monday'    => ['is_open' => true, 'open' => '17:00', 'close' => '08:00'],
            'tuesday'   => ['is_open' => true, 'open' => '08:00', 'close' => '17:00'],
            'wednesday' => ['is_open' => true, 'open' => '08:00', 'close' => '17:00'],
            'thursday'  => ['is_open' => true, 'open' => '08:00', 'close' => '17:00'],
            'friday'    => ['is_open' => true, 'open' => '08:00', 'close' => '17:00'],
            'saturday'  => ['is_open' => false, 'open' => '08:00', 'close' => '12:00'],
            'sunday'    => ['is_open' => false, 'open' => '08:00', 'close' => '12:00'],
        ];

        $this->actingAs($this->admin)->putJson('/api/admin/settings/general', [
            'hospital_name' => 'MMWGH',
            'contact_phone' => '(047) 935-4645',
            'emergency_hotline' => '(047) 935-4138',
            'contact_email' => 'support@mmwgh.gov.ph',
            'hospital_address' => 'Mariveles, Bataan',
            'clinic_hours' => $invalidHours,
        ])->assertStatus(422)->assertJsonValidationErrors(['clinic_hours.monday.close']);

        $validHours = $invalidHours;
        $validHours['monday'] = ['is_open' => true, 'open' => '07:30', 'close' => '16:30'];

        $this->actingAs($this->admin)->putJson('/api/admin/settings/general', [
            'hospital_name' => 'MMWGH Updated',
            'contact_phone' => '(047) 935-4645',
            'emergency_hotline' => '(047) 935-4138',
            'contact_email' => 'support@mmwgh.gov.ph',
            'hospital_address' => 'Mariveles, Bataan',
            'clinic_hours' => $validHours,
        ])->assertOk()->assertJsonPath('data.hospital_name', 'MMWGH Updated');
    }

    public function test_scheduling_rules_update_department_capacities_and_qr_expiry(): void
    {
        $this->actingAs($this->admin)->putJson('/api/admin/settings/scheduling', [
            'qr_code_expiry_hours' => 72,
            'departments' => [
                [
                    'department_code' => 'general',
                    'daily_capacity_limit' => 80,
                    'am_slot_limit' => 45,
                    'pm_slot_limit' => 35,
                    'booking_cutoff_time' => '15:30',
                    'qr_expiry_hours_override' => 24,
                    'allow_same_day_booking' => true,
                    'is_active' => true,
                ],
            ],
        ])->assertOk()->assertJsonPath('data.qr_code_expiry_hours', 72);

        $this->assertDatabaseHas('department_scheduling_rules', [
            'department_code' => 'general',
            'daily_capacity_limit' => 80,
            'qr_expiry_hours_override' => 24,
        ]);
    }

    public function test_otp_secret_is_encrypted_masked_on_read_and_preserved_when_masked_submitted(): void
    {
        $payload = [
            'otp_provider' => 'semaphore',
            'otp_api_url' => 'https://api.semaphore.co/api/v4/otp',
            'otp_api_key' => 'sk_live_super_secret_123',
            'otp_sender_id' => 'MMWGH',
            'otp_expiry_minutes' => 8,
            'otp_max_attempts' => 4,
            'username_min_length' => 6,
            'username_max_length' => 24,
            'username_pattern' => 'alphanumeric_dash',
            'username_prefix' => 'PT',
        ];

        $this->actingAs($this->admin)
            ->putJson('/api/admin/settings/auth', $payload)
            ->assertOk()
            ->assertJsonPath('data.otp_api_key', SettingsService::SECRET_MASK)
            ->assertJsonPath('data.has_otp_api_key', true);

        $rawDbValue = SystemSetting::where('setting_key', 'otp_api_key')->value('setting_value');
        $this->assertNotEquals('sk_live_super_secret_123', $rawDbValue);
        $this->assertEquals('sk_live_super_secret_123', app(SettingsService::class)->getDecryptedSecret('otp_api_key'));

        // Re-submitting masked value preserves the stored secret
        $payload['otp_api_key'] = SettingsService::SECRET_MASK;
        $payload['otp_expiry_minutes'] = 12;
        $this->actingAs($this->admin)->putJson('/api/admin/settings/auth', $payload)->assertOk();
        $this->assertEquals('sk_live_super_secret_123', app(SettingsService::class)->getDecryptedSecret('otp_api_key'));

        Mail::fake();
        $this->actingAs($this->admin)
            ->postJson('/api/admin/settings/auth/test-otp', ['recipient' => 'admin@mmwgh.gov.ph'])
            ->assertOk();
    }

    public function test_chatbot_faq_crud_sanitizes_html_and_updates_public_feed(): void
    {
        $created = $this->actingAs($this->admin)->postJson('/api/admin/faqs', [
            'slug' => 'qr-slip-help',
            'locale' => 'en',
            'persona' => 'nurse_mika',
            'question' => 'How long is my QR slip valid?',
            'answer_html' => 'Valid for <strong>48 hours</strong>.<script>alert(1)</script>',
            'keywords' => ['qr', 'slip', 'expiry'],
            'follow_ups' => ['How do I book?'],
            'is_active' => true,
        ])->assertStatus(201)->json('data');

        $this->assertEquals('Valid for <strong>48 hours</strong>.', $created['answer_html']);

        $this->getJson('/api/public/chatbot-faqs')
            ->assertOk()
            ->assertJsonFragment(['id' => 'qr-slip-help', 'persona' => 'nurse_mika']);

        $this->actingAs($this->admin)
            ->deleteJson('/api/admin/faqs/' . $created['id'])
            ->assertOk();

        $this->assertDatabaseMissing('chatbot_faqs', ['id' => $created['id']]);
    }

    public function test_system_settings_and_backup_trigger_and_download(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin)->putJson('/api/admin/settings/system', [
            'session_timeout_minutes' => 45,
            'patient_session_timeout_minutes' => 90,
            'auto_backup_enabled' => true,
            'auto_backup_frequency' => 'weekly',
        ])->assertOk()->assertJsonPath('data.session_timeout_minutes', 45);

        $backup = $this->actingAs($this->admin)
            ->postJson('/api/admin/system/backups')
            ->assertStatus(201)
            ->json('data');

        $this->assertEquals('completed', $backup['status']);
        $this->assertGreaterThan(0, $backup['size_bytes']);
        Storage::disk('local')->assertExists($backup['disk_path']);

        $this->actingAs($this->admin)
            ->get('/api/admin/system/backups/' . $backup['id'] . '/download')
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($this->admin)
            ->getJson('/api/admin/system/backups')
            ->assertOk()
            ->assertJsonPath('data.total_backups', 1);
    }
}
