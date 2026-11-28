<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PatientDashboardAuthTest extends TestCase
{
    public function test_unauthenticated_requests_are_rejected_even_with_patient_id_param(): void
    {
        $this->getJson('/api/patient/dashboard?patient_id=PT-2026-082&email=maria.santos@gmail.com')
            ->assertStatus(401)
            ->assertJson(['success' => false]);

        $this->getJson('/api/patient/appointments?patient_id=PT-2026-082')
            ->assertStatus(401)
            ->assertJson(['success' => false]);

        $this->postJson('/api/patient/profile', [
            'patient_id' => 'PT-2026-082',
            'contact_number' => '+639111111111',
        ])->assertStatus(401);

        $this->postJson('/api/patient/change-password', [
            'patient_id' => 'PT-2026-082',
            'current_password' => 'anything',
            'new_password' => 'NewPass123!',
            'confirm_password' => 'NewPass123!',
        ])->assertStatus(401);

        $this->postJson('/api/patient/appointments/1/cancel', [
            'patient_id' => 'PT-2026-082',
        ])->assertStatus(401);
    }

    public function test_invalid_bearer_token_is_rejected(): void
    {
        $this->withHeader('Authorization', 'Bearer invalid-token-123')
            ->getJson('/api/patient/dashboard?patient_id=PT-2026-082')
            ->assertStatus(401);
    }
}
