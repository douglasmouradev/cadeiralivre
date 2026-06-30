<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AppointmentModel;
use App\Models\BarberDateHoursModel;
use App\Models\BarberModel;
use App\Models\BlockedTimeModel;
use App\Models\ServiceModel;
use App\Models\WorkingHoursModel;
use App\Helpers\AppointmentOverlap;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

final class SlotService
{
    public function __construct(
        private readonly ServiceModel $services = new ServiceModel(),
        private readonly BarberModel $barbers = new BarberModel(),
        private readonly WorkingHoursModel $hours = new WorkingHoursModel(),
        private readonly BarberDateHoursModel $dateHours = new BarberDateHoursModel(),
        private readonly BlockedTimeModel $blocked = new BlockedTimeModel(),
        private readonly AppointmentModel $appointments = new AppointmentModel(),
    ) {
    }

    /**
     * @return list<array{start: string, barber_id: int}>
     */
    public function slotsForBarber(int $tenantId, int $serviceId, int $barberId, string $dateYmd, string $tenantTimezone): array
    {
        $service = $this->services->find($tenantId, $serviceId);
        if ($service === null || !(bool) $service['is_active']) {
            return [];
        }
        $duration = (int) $service['duration_minutes'];
        $tz = new DateTimeZone($tenantTimezone);
        $day = DateTimeImmutable::createFromFormat('Y-m-d', $dateYmd, $tz);
        if ($day === false) {
            return [];
        }
        $dow = (int) $day->format('w');
        $dateOverride = $this->dateHours->findForDate($tenantId, $barberId, $dateYmd);
        if ($dateOverride !== null) {
            if ((bool) $dateOverride['is_closed']) {
                return [];
            }
            $workStart = $day->setTime(
                (int) substr((string) $dateOverride['start_time'], 0, 2),
                (int) substr((string) $dateOverride['start_time'], 3, 2),
                0
            );
            $workEnd = $day->setTime(
                (int) substr((string) $dateOverride['end_time'], 0, 2),
                (int) substr((string) $dateOverride['end_time'], 3, 2),
                0
            );
        } else {
            $rows = $this->hours->forBarber($tenantId, $barberId);
            $wh = null;
            foreach ($rows as $r) {
                if ((int) $r['day_of_week'] === $dow) {
                    $wh = $r;
                    break;
                }
            }
            if ($wh === null || (bool) $wh['is_day_off']) {
                return [];
            }
            $workStart = $day->setTime(
                (int) substr((string) $wh['start_time'], 0, 2),
                (int) substr((string) $wh['start_time'], 3, 2),
                0
            );
            $workEnd = $day->setTime(
                (int) substr((string) $wh['end_time'], 0, 2),
                (int) substr((string) $wh['end_time'], 3, 2),
                0
            );
        }
        if ($workEnd <= $workStart) {
            return [];
        }

        $blocks = $this->blocked->forBarber($tenantId, $barberId);
        $dayStart = $day->setTime(0, 0, 0);
        $dayEnd = $day->setTime(23, 59, 59);

        $appts = $this->appointments->forBarberDateRange(
            $tenantId,
            $barberId,
            $dayStart->format('Y-m-d H:i:s'),
            $dayEnd->format('Y-m-d H:i:s')
        );

        $step = 15;
        $out = [];
        $cursor = $workStart;
        $lastStart = $workEnd->sub(new DateInterval('PT' . $duration . 'M'));
        while ($cursor <= $lastStart) {
            $slotEnd = $cursor->add(new DateInterval('PT' . $duration . 'M'));
            if (AppointmentOverlap::slotIsFree($cursor, $slotEnd, $appts, $blocks)) {
                $out[] = [
                    'start' => $cursor->format('Y-m-d H:i:s'),
                    'barber_id' => $barberId,
                ];
            }
            $cursor = $cursor->add(new DateInterval('PT' . $step . 'M'));
        }

        return $out;
    }

    /**
     * @return list<array{start: string, barber_ids: list<int>}>
     */
    public function slotsAnyBarber(int $tenantId, int $serviceId, string $dateYmd, string $tenantTimezone): array
    {
        $list = $this->barbers->barbersForService($tenantId, $serviceId);
        if ($list === []) {
            $list = $this->barbers->availableBarbersForTenant($tenantId);
        }
        $byTime = [];
        foreach ($list as $b) {
            $bid = (int) $b['id'];
            foreach ($this->slotsForBarber($tenantId, $serviceId, $bid, $dateYmd, $tenantTimezone) as $s) {
                $t = $s['start'];
                if (!isset($byTime[$t])) {
                    $byTime[$t] = [];
                }
                $byTime[$t][] = $bid;
            }
        }
        ksort($byTime);
        $merged = [];
        foreach ($byTime as $t => $ids) {
            $merged[] = ['start' => $t, 'barber_ids' => array_values(array_unique($ids))];
        }

        return $merged;
    }

    /**
     * Confirma que o horário coincide com a grade pública (evita POST adulterado).
     *
     * @param 'one'|'any' $barberMode
     */
    public function isPublicSlotValid(
        int $tenantId,
        int $serviceId,
        int $barberId,
        string $startYmdHis,
        string $tenantTimezone,
        string $barberMode,
    ): bool {
        $tz = new DateTimeZone($tenantTimezone);
        $startDt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startYmdHis, $tz);
        if ($startDt === false) {
            $startDt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startYmdHis);
        }
        if ($startDt === false) {
            return false;
        }
        $dateYmd = $startDt->format('Y-m-d');
        if ($barberMode === 'any') {
            foreach ($this->slotsAnyBarber($tenantId, $serviceId, $dateYmd, $tenantTimezone) as $s) {
                if ($s['start'] === $startYmdHis && in_array($barberId, $s['barber_ids'], true)) {
                    return true;
                }
            }

            return false;
        }
        foreach ($this->slotsForBarber($tenantId, $serviceId, $barberId, $dateYmd, $tenantTimezone) as $s) {
            if ($s['start'] === $startYmdHis) {
                return true;
            }
        }

        return false;
    }
}
