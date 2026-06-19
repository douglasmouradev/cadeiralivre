<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AppointmentModel;
use App\Models\BarberModel;
use App\Models\PlanDefinitionModel;

final class SubscriptionService
{
    /** @param array<string, mixed> $tenant */
    public function planRowForTenant(array $tenant): ?array
    {
        $pid = isset($tenant['plan_definition_id']) && $tenant['plan_definition_id'] !== null
            ? (int) $tenant['plan_definition_id']
            : null;
        if ($pid !== null && $pid > 0) {
            return (new PlanDefinitionModel())->findById($pid);
        }
        $slug = (string) ($tenant['plan'] ?? 'free');

        return (new PlanDefinitionModel())->findBySlug($slug);
    }

    /**
     * Barbearia pode usar painel e receber agendamentos (não suspensa e com trial/assinatura válidos).
     *
     * @param array<string, mixed> $tenant
     */
    public function canOperate(array $tenant): bool
    {
        if ((string) ($tenant['status'] ?? '') === 'suspended') {
            return false;
        }
        $trialEnds = $tenant['trial_ends_at'] ?? null;
        if (!is_string($trialEnds) || $trialEnds === '') {
            return true;
        }
        try {
            $end = new \DateTimeImmutable($trialEnds);
        } catch (\Throwable) {
            return true;
        }
        if (new \DateTimeImmutable() <= $end) {
            return true;
        }
        $sub = (string) ($tenant['subscription_status'] ?? 'none');

        return in_array($sub, ['active', 'trialing'], true);
    }

    /** @param array<string, mixed> $tenant */
    public function humanBlockReason(array $tenant): string
    {
        if ((string) ($tenant['status'] ?? '') === 'suspended') {
            return 'Esta barbearia está suspensa. Contacte o suporte.';
        }
        if (!$this->canOperate($tenant)) {
            return 'Período de teste encerrado ou assinatura inativa. Acesse Configurações → Assinatura para regularizar.';
        }

        return '';
    }

    /** @param array<string, mixed> $tenant */
    public function barberLimitMessage(int $tenantId, array $tenant): ?string
    {
        $plan = $this->planRowForTenant($tenant);
        if ($plan === null) {
            return null;
        }
        $max = $plan['max_barbers'] ?? null;
        if ($max === null) {
            return null;
        }
        $max = (int) $max;
        $current = (new BarberModel())->countForTenant($tenantId);
        if ($current >= $max) {
            return 'Limite de profissionais do plano atingido (' . $max . '). Faça upgrade em Configurações → Assinatura.';
        }

        return null;
    }

    /** @param array<string, mixed> $tenant */
    public function monthlyAppointmentLimitMessage(int $tenantId, array $tenant, ?\DateTimeImmutable $forMonth = null): ?string
    {
        $plan = $this->planRowForTenant($tenant);
        if ($plan === null) {
            return null;
        }
        $max = $plan['max_appointments_per_month'] ?? null;
        if ($max === null) {
            return null;
        }
        $max = (int) $max;
        $month = $forMonth ?? new \DateTimeImmutable('first day of this month');
        $ym = $month->format('Y-m');
        $current = (new AppointmentModel())->countBookedInCalendarMonth($tenantId, $ym);
        if ($current >= $max) {
            return 'Limite mensal de agendamentos do plano atingido (' . $max . '). Faça upgrade ou aguarde o próximo mês.';
        }

        return null;
    }
}
