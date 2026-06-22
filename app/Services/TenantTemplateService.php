<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BarberModel;
use App\Models\ServiceModel;
use App\Models\WorkingHoursModel;

final class TenantTemplateService
{
    /** @return list<string> */
    public static function slugs(): array
    {
        return ['empty', 'barbershop', 'nail_design'];
    }

    public static function label(string $slug): string
    {
        return match ($slug) {
            'barbershop' => 'Barbearia (corte, barba, serviços clássicos)',
            'nail_design' => 'Nail design (manicure, gel, nail art)',
            default => 'Vazio (sem serviços pré-configurados)',
        };
    }

    public function apply(int $tenantId, int $barberId, string $template): void
    {
        $slug = strtolower(trim($template));
        if ($slug === '' || $slug === 'empty') {
            return;
        }

        $catalog = match ($slug) {
            'barbershop' => [
                ['Corte masculino', 'Corte na máquina e tesoura.', 30, 45.00, 'Corte', 0],
                ['Barba', 'Barba com toalha quente e finalização.', 25, 35.00, 'Barba', 1],
                ['Corte + barba', 'Combo completo.', 50, 70.00, 'Combo', 2],
                ['Sobrancelha', 'Design e acabamento.', 15, 20.00, 'Estética', 3],
            ],
            'nail_design' => [
                ['Manicure tradicional', 'Cutícula, lixamento e esmaltação.', 45, 45.00, 'Manicure', 0],
                ['Alongamento em gel', 'Aplicação completa com gel builder.', 120, 150.00, 'Alongamento', 1],
                ['Esmaltação em gel', 'Esmaltação com acabamento em gel.', 45, 50.00, 'Manicure', 2],
                ['Nail art', 'Arte personalizada nas unhas.', 30, 35.00, 'Nail art', 3],
            ],
            default => [],
        };
        if ($catalog === []) {
            return;
        }

        $services = new ServiceModel();
        $serviceIds = [];
        foreach ($catalog as [$name, $desc, $minutes, $price, $category, $order]) {
            $serviceIds[] = $services->create($tenantId, [
                'name' => $name,
                'description' => $desc,
                'duration_minutes' => $minutes,
                'price' => $price,
                'category' => $category,
                'is_active' => true,
                'display_order' => $order,
            ]);
        }
        (new BarberModel())->syncServices($tenantId, $barberId, $serviceIds, []);

        $week = [];
        for ($dow = 0; $dow <= 6; $dow++) {
            $week[] = [
                'day_of_week' => $dow,
                'start_time' => '09:00:00',
                'end_time' => $dow === 0 ? '00:00:00' : ($dow === 6 ? '14:00:00' : '18:00:00'),
                'is_day_off' => $dow === 0,
            ];
        }
        (new WorkingHoursModel())->replaceWeek($tenantId, $barberId, $week);
    }
}
