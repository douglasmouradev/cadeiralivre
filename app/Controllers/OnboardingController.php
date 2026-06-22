<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Enums\UserRole;
use App\Helpers\Flash;
use App\Models\BarberModel;
use App\Models\ServiceModel;
use App\Models\TenantModel;
use App\Models\WorkingHoursModel;

final class OnboardingController extends Controller
{
    public function index(): Response
    {
        if ($this->userRole() !== UserRole::Owner->value) {
            return Response::redirect('/painel');
        }
        $tid = $this->tenantId();
        $tenant = (new TenantModel())->findById($tid);
        if ($tenant === null) {
            return Response::redirect('/painel');
        }
        if ((new TenantModel())->isOnboardingComplete($tid)) {
            return Response::redirect('/painel');
        }

        $services = (new ServiceModel())->allForTenant($tid, true);
        $barbers = (new BarberModel())->listWithUser($tid);
        $hasHours = false;
        if ($barbers !== []) {
            $hours = (new WorkingHoursModel())->forBarber($tid, (int) $barbers[0]['id']);
            foreach ($hours as $h) {
                if ((int) ($h['is_day_off'] ?? 0) === 0 && (string) ($h['start_time'] ?? '') !== '') {
                    $hasHours = true;
                    break;
                }
            }
        }
        $slug = (string) ($tenant['slug'] ?? '');
        $bookingUrl = $slug !== '' ? app_base_url() . '/agendar/' . rawurlencode($slug) : '';

        return $this->view('onboarding/index', [
            'title' => 'Primeiros passos',
            'tenant' => $tenant,
            'hasLogo' => !empty($tenant['logo_path']),
            'hasServices' => $services !== [],
            'hasHours' => $hasHours,
            'bookingUrl' => $bookingUrl,
            'currentNav' => 'dashboard',
        ]);
    }

    public function complete(): Response
    {
        if ($this->userRole() !== UserRole::Owner->value) {
            return Response::redirect('/painel');
        }
        $tid = $this->tenantId();
        (new TenantModel())->markOnboardingComplete($tid);
        Flash::set('success', 'Configuração inicial concluída. Boa gestão!');

        return Response::redirect('/painel');
    }
}
