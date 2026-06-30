<?php

declare(strict_types=1);

namespace App\Helpers;

use DateTimeImmutable;

/** Verificação de conflito de horários (agendamentos e bloqueios). */
final class AppointmentOverlap
{
    /**
     * @param list<array<string, mixed>> $appointments
     * @param list<array<string, mixed>> $blocks
     */
    public static function slotIsFree(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        array $appointments,
        array $blocks,
    ): bool {
        foreach ($appointments as $a) {
            if (!in_array((string) $a['status'], ['cancelled', 'no_show'], true)) {
                $as = new DateTimeImmutable((string) $a['start_datetime']);
                $ae = new DateTimeImmutable((string) $a['end_datetime']);
                if ($start < $ae && $end > $as) {
                    return false;
                }
            }
        }
        foreach ($blocks as $b) {
            $bs = new DateTimeImmutable((string) $b['start_datetime']);
            $be = new DateTimeImmutable((string) $b['end_datetime']);
            if ($start < $be && $end > $bs) {
                return false;
            }
        }

        return true;
    }

    public static function rangesOverlap(
        string $startA,
        string $endA,
        string $startB,
        string $endB,
    ): bool {
        $aStart = new DateTimeImmutable($startA);
        $aEnd = new DateTimeImmutable($endA);
        $bStart = new DateTimeImmutable($startB);
        $bEnd = new DateTimeImmutable($endB);

        return $aStart < $bEnd && $aEnd > $bStart;
    }
}
