<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh');

        if (! Schema::hasTable('patients')) {
            Schema::create('patients', function (Blueprint $table) {
                $table->id();
                $table->string('patient_id')->nullable();
                $table->string('full_name')->nullable();
                $table->string('username')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->boolean('is_verified')->default(true);
                $table->boolean('is_otp_enabled')->default(false);
                $table->string('otp_code')->nullable();
                $table->string('otp_hash')->nullable();
                $table->timestamp('otp_expires_at')->nullable();
                $table->unsignedTinyInteger('otp_attempts')->default(0);
                $table->string('account_status', 20)->default('active');
                $table->boolean('must_change_password')->default(false);
                $table->string('appointment_recipient')->nullable();
                $table->timestamps();
            });
        }

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'username' => 'sysadmin_test',
            'full_name' => 'System Admin',
            'department' => 'Administration',
            'status' => 'active',
        ]);
        $this->staff = User::factory()->create([
            'role' => 'staff',
            'username' => 'staff_neuro',
            'full_name' => 'Neuro Staff',
            'department' => 'Neurology',
            'status' => 'active',
        ]);
    }

    public function test_non_admin_is_blocked_from_user_management_endpoints(): void
    {
        $this->getJson('/api/admin/users/staff')->assertStatus(401);
        $this->actingAs($this->staff)->getJson('/api/admin/users/staff')->assertStatus(403);
        $this->actingAs($this->staff)->getJson('/api/admin/users/patients')->assertStatus(403);
    }

    public function test_admin_can_create_edit_role_and_reset_password_for_staff(): void
    {
        $create = $this->actingAs($this->admin)->postJson('/api/admin/users/staff', [
            'full_name'  => 'Dr. Marco Reyes',
            'username'   => 'mreyes_psych',
            'role'       => 'staff',
            'department' => 'Psychiatry',
            'password'   => 'secret123',
        ])->assertStatus(201);

        $newId = $create->json('data.id');

        $this->actingAs($this->admin)->putJson("/api/admin/users/staff/{$newId}", [
            'full_name'  => 'Dr. Marco Reyes',
            'role'       => 'admin',
            'department' => 'Administration',
            'status'     => 'active',
        ])->assertOk();

        $reset = $this->actingAs($this->admin)
            ->postJson("/api/admin/users/staff/{$newId}/reset-password")
            ->assertOk()
            ->assertJsonStructure(['success', 'temporary_password']);

        $tempPass = $reset->json('temporary_password');
        $stored = DB::table('users')->where('id', $newId)->first();
        $this->assertEquals('admin', $stored->role);
        $this->assertTrue(Hash::check($tempPass, $stored->password));
    }

    public function test_patient_recovery_temp_password_email_reverify_and_suspend_enforcement(): void
    {
        Mail::fake();

        $patientId = DB::table('patients')->insertGetId([
            'patient_id'     => 'PT-2026-0001',
            'full_name'      => 'Alex B. Gallardo',
            'username'       => 'alex.gallardo',
            'email'          => 'typo@example.com',
            'password'       => Hash::make('oldpass123'),
            'is_verified'    => 1,
            'account_status' => 'active',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // Search walk-in patient by username
        $this->actingAs($this->admin)
            ->getJson('/api/admin/users/patients?search=alex')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.username', 'alex.gallardo');

        // Generate temporary password
        $tempRes = $this->actingAs($this->admin)
            ->postJson("/api/admin/users/patients/{$patientId}/temp-password")
            ->assertOk();
        $tempPassword = $tempRes->json('temporary_password');

        $row = DB::table('patients')->where('id', $patientId)->first();
        $this->assertEquals(1, (int) $row->must_change_password);
        $this->assertTrue(Hash::check($tempPassword, $row->password));

        // Edit email -> resets is_verified to 0 and forces OTP on next login
        $this->actingAs($this->admin)
            ->patchJson("/api/admin/users/patients/{$patientId}/email", ['email' => 'alex.fixed@example.com'])
            ->assertOk()
            ->assertJsonPath('data.is_verified', false);

        $this->postJson('/api/patient/login', [
            'email'    => 'alex.gallardo',
            'password' => $tempPassword,
        ])->assertOk()->assertJsonPath('otp_required', true);

        // Suspend account -> blocks login with 403
        $this->actingAs($this->admin)
            ->patchJson("/api/admin/users/patients/{$patientId}/status", ['account_status' => 'suspended'])
            ->assertOk()
            ->assertJsonPath('data.account_status', 'suspended');

        $this->postJson('/api/patient/login', [
            'email'    => 'alex.gallardo',
            'password' => $tempPassword,
        ])->assertStatus(403)->assertJsonPath('suspended', true);
    }
}
