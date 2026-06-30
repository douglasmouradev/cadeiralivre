<?php

declare(strict_types=1);

use App\Models\AppointmentModel;
use App\Services\SlotService;
use PHPUnit\Framework\TestCase;

/**
 * Requer MySQL com migrations + seed demo (CI).
 */
final class SlotServiceIntegrationTest extends TestCase
{
    private static bool $dbReady = false;

    public static function setUpBeforeClass(): void
    {
        $root = dirname(__DIR__);
        require $root . '/config/load_env.php';
        if (!app_load_dotenv($root)) {
            self::$dbReady = false;

            return;
        }
        try {
            \App\Core\Database::connection()->query('SELECT 1 FROM tenants WHERE slug = \'demo-barbearia\' LIMIT 1');
            self::$dbReady = true;
        } catch (\Throwable) {
            self::$dbReady = false;
        }
    }

    public function testCountOverlappingDetectsConflict(): void
    {
        if (!self::$dbReady) {
            $this->markTestSkipped('Banco demo indisponível');
        }
        $ap = new AppointmentModel();
        $count = $ap->countOverlapping(
            1,
            1,
            '2099-01-15 10:00:00',
            '2099-01-15 10:30:00',
        );
        $this->assertSame(0, $count);
    }

    public function testIsPublicSlotValidRejectsInvalidDatetime(): void
    {
        if (!self::$dbReady) {
            $this->markTestSkipped('Banco demo indisponível');
        }
        $svc = new SlotService();
        $this->assertFalse($svc->isPublicSlotValid(1, 1, 1, 'invalid', 'America/Sao_Paulo', 'one'));
    }

    public function testSlotsForBarberOnSundayReturnsEmptyForDemo(): void
    {
        if (!self::$dbReady) {
            $this->markTestSkipped('Banco demo indisponível');
        }
        $svc = new SlotService();
        // Próximo domingo a partir de hoje
        $dow = (int) date('w');
        $daysUntilSunday = (7 - $dow) % 7;
        if ($daysUntilSunday === 0) {
            $daysUntilSunday = 7;
        }
        $sunday = date('Y-m-d', strtotime("+{$daysUntilSunday} days"));
        $slots = $svc->slotsForBarber(1, 1, 1, $sunday, 'America/Sao_Paulo');
        $this->assertSame([], $slots);
    }
}
