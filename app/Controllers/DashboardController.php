<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Enums\UserRole;
use App\Models\AppointmentModel;
use App\Models\BarberModel;
use App\Models\TenantModel;
use App\Services\SubscriptionService;

final class DashboardController extends Controller
{
    public function index(): Response
    {
        if ($this->userRole() === UserRole::Barber->value) {
            return Response::redirect('/agenda');
        }
        $tid = $this->tenantId();
        $appts = new AppointmentModel();
        $stats = $appts->dashboardStats($tid);
        $chart = $appts->revenueLast30Days($tid);
        $upcoming = $appts->todayUpcoming($tid);
        $pending = $appts->countPendingConfirmation($tid);
        $barbers = new BarberModel();
        $barberCount = count($barbers->listWithUser($tid));
        $todayCount = (int) $stats['appts_today'];
        $alerts = [];
        if ($pending > 0) {
            $alerts[] = 'Existem ' . $pending . ' agendamento(s) pendentes de confirmação.';
        }
        if ($barberCount > 0 && $todayCount < $barberCount) {
            $alerts[] = 'Possíveis horários vagos hoje: há barbeiros com pouca ocupação na agenda.';
        }
        $tenant = (new TenantModel())->findById($tid);
        if (is_array($tenant)) {
            $sub = new SubscriptionService();
            if (!$sub->canOperate($tenant)) {
                $alerts[] = $sub->humanBlockReason($tenant);
            }
            $bm = $sub->barberLimitMessage($tid, $tenant);
            if ($bm !== null) {
                $alerts[] = $bm;
            }
            $am = $sub->monthlyAppointmentLimitMessage($tid, $tenant);
            if ($am !== null) {
                $alerts[] = $am;
            }
        }

        return $this->view('dashboard/index', [
            'title' => 'Painel',
            'stats' => $stats,
            'chart' => $chart,
            'upcoming' => $upcoming,
            'alerts' => $alerts,
            'tenant' => $tenant,
            'currentNav' => 'dashboard',
        ]);
    }

    public function todayJson(): Response
    {
        $tid = $this->tenantId();
        $appts = new AppointmentModel();

        return Response::json([
            'upcoming' => $appts->todayUpcoming($tid),
            'pending' => $appts->countPendingConfirmation($tid),
        ]);
    }
}
