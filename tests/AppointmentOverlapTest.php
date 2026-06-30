<?php

declare(strict_types=1);

use App\Helpers\AppointmentOverlap;
use PHPUnit\Framework\TestCase;

final class AppointmentOverlapTest extends TestCase
{
    public function testDetectsOverlappingAppointments(): void
    {
        $start = new \DateTimeImmutable('2025-06-20 10:00:00');
        $end = new \DateTimeImmutable('2025-06-20 10:30:00');
        $appointments = [[
            'status' => 'confirmed',
            'start_datetime' => '2025-06-20 10:15:00',
            'end_datetime' => '2025-06-20 10:45:00',
        ]];

        $this->assertFalse(AppointmentOverlap::slotIsFree($start, $end, $appointments, []));
    }

    public function testIgnoresCancelledAppointments(): void
    {
        $start = new \DateTimeImmutable('2025-06-20 10:00:00');
        $end = new \DateTimeImmutable('2025-06-20 10:30:00');
        $appointments = [[
            'status' => 'cancelled',
            'start_datetime' => '2025-06-20 10:00:00',
            'end_datetime' => '2025-06-20 10:30:00',
        ]];

        $this->assertTrue(AppointmentOverlap::slotIsFree($start, $end, $appointments, []));
    }

    public function testRangesOverlapHelper(): void
    {
        $this->assertTrue(AppointmentOverlap::rangesOverlap(
            '2025-06-20 10:00:00',
            '2025-06-20 10:30:00',
            '2025-06-20 10:15:00',
            '2025-06-20 10:45:00',
        ));
        $this->assertFalse(AppointmentOverlap::rangesOverlap(
            '2025-06-20 10:00:00',
            '2025-06-20 10:30:00',
            '2025-06-20 10:30:00',
            '2025-06-20 11:00:00',
        ));
    }
}
