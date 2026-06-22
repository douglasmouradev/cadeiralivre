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
            'inactiveClients' => $rep->inactiveClients($tid, 90),
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

    public function exportCsv(): Response
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
        $series = $rep->revenueByPeriod($tid, $start->format('Y-m-d'), $end->format('Y-m-d'));
        $lines = ['dia;total'];
        foreach ($series as $row) {
            $lines[] = (string) ($row['day'] ?? '') . ';' . str_replace('.', ',', (string) ($row['total'] ?? '0'));
        }
        $csv = "\xEF\xBB\xBF" . implode("\n", $lines);
        $fn = 'receita_' . $start->format('Y-m-d') . '_' . $end->format('Y-m-d') . '.csv';

        return Response::csv($fn, $csv);
    }
}
