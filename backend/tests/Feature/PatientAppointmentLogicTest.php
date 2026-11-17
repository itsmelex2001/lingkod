<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Appointment;
use Carbon\Carbon;

class PatientAppointmentLogicTest extends TestCase
{
    /**
     * Test appointment scopes and past/upcoming partitioning logic.
     */
    public function test_appointment_filtering_and_status_logic(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 13, 10, 0, 0, 'Asia/Manila'));

        // Case 1: Past appointment from Nov 10, 2026 (raw status: ongoing/approved)
        $pastAppt = new Appointment([
            'patient_id' => 'PT-TEST-001',
            'appointment_date' => '2026-11-10',
            'scheduled_time' => '09:00:00',
            'status' => 'ongoing'
        ]);

        $this->assertEquals('Missed', $pastAppt->computed_status);
        $this->assertFalse($pastAppt->can_reschedule);

        // Case 2: Same-day earlier appointment (8:00 AM on Nov 13, 2026)
        $sameDayPast = new Appointment([
            'patient_id' => 'PT-TEST-001',
            'appointment_date' => '2026-11-13',
            'scheduled_time' => '08:00:00',
            'status' => 'approved'
        ]);

        // Nov 13 8:00 AM is 2 hours before Nov 13 10:00 AM (still within live window of ongoing)
        $this->assertTrue(Carbon::parse($sameDayPast->full_datetime)->lt(Carbon::now()));

        // Case 3: Same-day future appointment (2:00 PM on Nov 13, 2026)
        $sameDayFuture = new Appointment([
            'patient_id' => 'PT-TEST-001',
            'appointment_date' => '2026-11-13',
            'scheduled_time' => '14:00:00',
            'status' => 'approved'
        ]);

        $this->assertEquals('Confirmed', $sameDayFuture->computed_status);
        $this->assertTrue(Carbon::parse($sameDayFuture->full_datetime)->gte(Carbon::now()));

        // Case 4: Future appointment (Nov 16, 2026 at 10:00 AM)
        $futureAppt = new Appointment([
            'patient_id' => 'PT-TEST-001',
            'appointment_date' => '2026-11-16',
            'scheduled_time' => '10:00:00',
            'status' => 'approved'
        ]);

        $this->assertEquals('Confirmed', $futureAppt->computed_status);
        $this->assertEquals('In 3 days', $futureAppt->relative_label);
        $this->assertTrue($futureAppt->can_reschedule);

        // Case 5: Cancelled appointment
        $cancelledAppt = new Appointment([
            'patient_id' => 'PT-TEST-001',
            'appointment_date' => '2026-11-20',
            'scheduled_time' => '10:00:00',
            'status' => 'cancelled'
        ]);

        $this->assertEquals('Cancelled', $cancelledAppt->computed_status);
        $this->assertFalse($cancelledAppt->can_reschedule);

        Carbon::setTestNow();
    }
}

