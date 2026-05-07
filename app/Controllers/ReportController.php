<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\ReportModel;

final class ReportController extends Controller
{
    public function index(): Response
    {
        $tid = $this->tenantId();
        $range = (string) ($this->request->query()['range'] ?? 'month');
        $end = new \DateTimeImmutable('today');
        $start = match ($range) {
            'week' => $end->modify('-6 days'),
            'day' => $end,
            'custom' => \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($this->request->query()['start'] ?? $end->format('Y-m-d'))) ?: $end,
            default => $end->modify('first day of this month'),
        };
        if ($range === 'custom') {
            $end = \DateTimeImmutable::createFromFormat('Y-m-d', (string) ($this->request->query()['end'] ?? $end->format('Y-m-d'))) ?: $end;
        } elseif ($range === 'month') {
            $start = $end->modify('first day of this month');
            $end = $start->modify('last day of this month');
        }
        $rep = new ReportModel();
        $data = [
            'revenueSeries' => $rep->revenueByPeriod($tid, $start->format('Y-m-d'), $end->format('Y-m-d')),
            'byBarber' => $rep->revenueByBarber($tid, $start->format('Y-m-d'), $end->format('Y-m-d')),
            'byService' => $rep->revenueByService($tid, $start->format('Y-m-d'), $end->format('Y-m-d')),
            'commissions' => $rep->commissionsByBarber($tid, $start->format('Y-m-d'), $end->format('Y-m-d')),
            'cancellations' => $rep->cancellationStats($tid, $start->format('Y-m-d'), $end->format('Y-m-d')),
            'peaks' => $rep->peakHours($tid, $start->format('Y-m-d'), $end->format('Y-m-d')),
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'range' => $range,
        ];

        return $this->view('reports/index', [
            'title' => 'Relatórios',
            'data' => $data,
            'currentNav' => 'reports',
        ]);
    }
}
