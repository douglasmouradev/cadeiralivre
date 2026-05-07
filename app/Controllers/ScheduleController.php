<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Helpers\Flash;
use App\Helpers\MysqlNamedLock;
use App\Helpers\RateLimiter;
use App\Helpers\Str;
use App\Models\AppointmentModel;
use App\Models\BarberModel;
use App\Models\ClientModel;
use App\Models\ServiceModel;
use App\Models\TenantModel;
use App\Services\SlotService;
use Throwable;

final class ScheduleController extends Controller
{
    public function index(): Response
    {
        $tid = $this->tenantId();
        $mode = (string) ($this->request->query()['mode'] ?? 'week');
        $anchor = (string) ($this->request->query()['date'] ?? date('Y-m-d'));
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $anchor) ?: new \DateTimeImmutable('today');
        if ($mode === 'day') {
            $start = $dt->format('Y-m-d');
            $end = $dt->format('Y-m-d');
        } else {
            $dow = (int) $dt->format('N');
            $mon = $dt->modify('-' . ($dow - 1) . ' days');
            $start = $mon->format('Y-m-d');
            $end = $mon->modify('+6 days')->format('Y-m-d');
        }
        $barberFilter = null;
        if ($this->userRole() === UserRole::Barber->value) {
            $b = (new BarberModel())->findByUserId($tid, $this->userId());
            $barberFilter = $b !== null ? (int) $b['id'] : -1;
        }
        $appts = new AppointmentModel();
        $rows = $appts->forTenantDateRange($tid, $start, $end);
        if ($barberFilter !== null && $barberFilter > 0) {
            $rows = array_values(array_filter($rows, static fn (array $a): bool => (int) $a['barber_id'] === $barberFilter));
        }
        $barbers = (new BarberModel())->listWithUser($tid);
        $services = (new ServiceModel())->allForTenant($tid, true);
        $clients = (new ClientModel())->searchQuick($tid, '', 200);

        return $this->view('schedule/index', [
            'title' => 'Agenda',
            'mode' => $mode,
            'start' => $start,
            'end' => $end,
            'appointments' => $rows,
            'barbers' => $barbers,
            'services' => $services,
            'clients' => $clients,
            'currentNav' => 'schedule',
            'barberFilter' => $barberFilter,
        ]);
    }

    public function store(): Response
    {
        $tid = $this->tenantId();
        if (!RateLimiter::allow('schedule_store:' . $tid . ':' . $this->userId(), 60, 600)) {
            Flash::set('error', 'Muitas criações em sequência. Aguarde um minuto e tente de novo.');

            return Response::redirect('/agenda');
        }
        $tenantRow = (new TenantModel())->findById($tid);
        if ($tenantRow === null) {
            Flash::set('error', 'Configuração da conta inválida.');

            return Response::redirect('/agenda');
        }
        $tz = (string) ($tenantRow['timezone'] ?? 'America/Sao_Paulo');

        try {
            Database::transaction(function () use ($tid, $tz): void {
                $c = new ClientModel();
                $clientId = (int) $this->request->input('client_id');
                if ($clientId === 0) {
                    $name = trim((string) $this->request->input('new_client_name'));
                    if ($name === '') {
                        throw new \RuntimeException('NEW_CLIENT_NAME');
                    }
                    $clientId = $c->create($tid, [
                        'name' => $name,
                        'email' => trim((string) $this->request->input('new_client_email')),
                        'phone' => trim((string) $this->request->input('new_client_phone')),
                        'birth_date' => '',
                        'notes' => '',
                    ]);
                } elseif ($c->find($tid, $clientId) === null) {
                    throw new \RuntimeException('BAD_CLIENT');
                }

                $serviceId = (int) $this->request->input('service_id');
                $barberId = (int) $this->request->input('barber_id');
                $start = (string) $this->request->input('start_datetime');

                $svc = (new ServiceModel())->find($tid, $serviceId);
                if ($svc === null || !(bool) $svc['is_active']) {
                    throw new \RuntimeException('BAD_SERVICE');
                }
                $barberRow = (new BarberModel())->find($tid, $barberId);
                if ($barberRow === null || !(bool) ($barberRow['is_available'] ?? false)) {
                    throw new \RuntimeException('BAD_BARBER');
                }

                $duration = (int) $svc['duration_minutes'];
                $startDt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $start) ?: \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $start);
                if ($startDt === false) {
                    throw new \RuntimeException('BAD_DATETIME');
                }
                $startStr = $startDt->format('Y-m-d H:i:s');
                $endStr = $startDt->add(new \DateInterval('PT' . $duration . 'M'))->format('Y-m-d H:i:s');

                $slotSvc = new SlotService();
                if (!$slotSvc->isPublicSlotValid($tid, $serviceId, $barberId, $startStr, $tz, 'one')) {
                    throw new \RuntimeException('BAD_SLOT');
                }

                $ap = new AppointmentModel();
                if ($ap->countOverlapping($tid, $barberId, $startStr, $endStr) > 0) {
                    throw new \RuntimeException('OVERLAP');
                }
                $bs = (new BarberModel())->serviceIdsForBarber($barberId);
                if ($bs !== [] && !in_array($serviceId, $bs, true)) {
                    throw new \RuntimeException('BARBER_SERVICE');
                }

                $price = (float) $svc['price'];
                $public = Str::randomToken(32);
                $review = Str::randomToken(32);
                $code = Str::confirmationCode();
                $newId = $ap->create($tid, [
                    'client_id' => $clientId,
                    'barber_id' => $barberId,
                    'service_id' => $serviceId,
                    'booked_by_user_id' => $this->userId(),
                    'start_datetime' => $startStr,
                    'end_datetime' => $endStr,
                    'status' => AppointmentStatus::Confirmed->value,
                    'price' => $price,
                    'discount' => 0,
                    'notes' => trim((string) $this->request->input('notes')) ?: null,
                    'payment_method' => null,
                    'payment_note' => null,
                    'public_token' => $public,
                    'confirmation_code' => $code,
                    'review_token' => $review,
                ]);
                $ap->addHistory($newId, $tid, null, AppointmentStatus::Confirmed->value, $this->userId(), 'Criado pelo painel');
            });
        } catch (\Throwable $e) {
            $map = [
                'NEW_CLIENT_NAME' => 'Informe o nome do novo cliente ou selecione um existente.',
                'BAD_CLIENT' => 'Cliente inválido.',
                'BAD_SERVICE' => 'Serviço inválido ou inativo.',
                'BAD_BARBER' => 'Profissional inválido ou indisponível.',
                'BAD_DATETIME' => 'Data/hora inválida.',
                'BAD_SLOT' => 'Horário fora da grade disponível para este profissional.',
                'OVERLAP' => 'Horário indisponível para este barbeiro.',
                'BARBER_SERVICE' => 'Barbeiro não realiza este serviço.',
            ];
            $code = $e->getMessage();
            $msg = $map[$code] ?? null;
            if ($msg === null && (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'uk_clients_tenant_email'))) {
                $msg = 'Já existe um cliente com este e-mail. Selecione-o na lista.';
            }
            Flash::set('error', $msg ?? 'Não foi possível criar o agendamento.');

            return Response::redirect('/agenda');
        }

        Flash::set('success', 'Agendamento criado.');

        return Response::redirect('/agenda');
    }

    public function updateStatus(): Response
    {
        $tid = $this->tenantId();
        $id = (int) $this->request->input('appointment_id');
        $to = (string) $this->request->input('status');
        $ap = new AppointmentModel();
        $row = $ap->find($tid, $id);
        if ($row === null) {
            Flash::set('error', 'Agendamento não encontrado.');

            return Response::redirect('/agenda');
        }
        $from = (string) $row['status'];
        $ap->updateStatus($tid, $id, $to, $to === AppointmentStatus::Cancelled->value ? trim((string) $this->request->input('cancellation_reason')) : null);
        $ap->addHistory($id, $tid, $from, $to, $this->userId(), null);
        if ($to === AppointmentStatus::Completed->value) {
            $pm = new \App\Models\PaymentModel();
            if (!$pm->hasPaidForAppointment($tid, $id)) {
                $pm->create($tid, $id, (float) $row['price'], 'cash', 'paid');
            }
        }
        Flash::set('success', 'Status atualizado.');

        return Response::redirect('/agenda');
    }

    public function reschedule(): Response
    {
        $tid = $this->tenantId();
        $id = (int) $this->request->input('appointment_id');
        $start = (string) $this->request->input('start_datetime');
        $ap = new AppointmentModel();
        $row = $ap->find($tid, $id);
        if ($row === null) {
            Flash::set('error', 'Agendamento não encontrado.');

            return Response::redirect('/agenda');
        }
        $tenantRow = (new TenantModel())->findById($tid);
        if ($tenantRow === null) {
            Flash::set('error', 'Configuração inválida.');

            return Response::redirect('/agenda');
        }
        $tz = (string) ($tenantRow['timezone'] ?? 'America/Sao_Paulo');
        $svcRow = (new ServiceModel())->find($tid, (int) $row['service_id']);
        if ($svcRow === null || !(bool) $svcRow['is_active']) {
            Flash::set('error', 'Serviço não encontrado ou inativo.');

            return Response::redirect('/agenda');
        }
        $minutes = (int) $svcRow['duration_minutes'];
        $startDt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $start) ?: \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $start);
        if ($startDt === false) {
            Flash::set('error', 'Data inválida.');

            return Response::redirect('/agenda');
        }
        $startStr = $startDt->format('Y-m-d H:i:s');
        $endStr = $startDt->add(new \DateInterval('PT' . $minutes . 'M'))->format('Y-m-d H:i:s');
        $barberId = (int) $row['barber_id'];
        $serviceId = (int) $row['service_id'];
        $slotSvc = new SlotService();
        if (!$slotSvc->isPublicSlotValid($tid, $serviceId, $barberId, $startStr, $tz, 'one')) {
            Flash::set('error', 'Horário fora da grade disponível.');

            return Response::redirect('/agenda');
        }

        $lockKey = substr('rs_' . $tid . '_' . $barberId . '_' . hash('sha256', $startStr), 0, 64);
        $pdo = Database::connection();
        $lockHeld = false;
        try {
            if (!MysqlNamedLock::acquire($pdo, $lockKey, 10)) {
                Flash::set('error', 'Não foi possível reagendar neste momento. Tente de novo em instantes.');

                return Response::redirect('/agenda');
            }
            $lockHeld = true;

            try {
                Database::transaction(function () use ($tid, $id, $tz, $barberId, $serviceId, $startStr, $endStr, $slotSvc, $ap, $row): void {
                    if (!$slotSvc->isPublicSlotValid($tid, $serviceId, $barberId, $startStr, $tz, 'one')) {
                        throw new \RuntimeException('BAD_SLOT');
                    }
                    if ($ap->countOverlapping($tid, $barberId, $startStr, $endStr, $id) > 0) {
                        throw new \RuntimeException('OVERLAP');
                    }
                    $from = (string) $row['start_datetime'];
                    $ap->reschedule($tid, $id, $startStr, $endStr);
                    $ap->addHistory($id, $tid, (string) $row['status'], (string) $row['status'], $this->userId(), 'Reagendado de ' . $from . ' para ' . $startStr);
                });
            } catch (Throwable $e) {
                $map = [
                    'BAD_SLOT' => 'Horário fora da grade disponível.',
                    'OVERLAP' => 'Conflito de horário.',
                ];
                $msg = $map[$e->getMessage()] ?? 'Não foi possível reagendar.';
                Flash::set('error', $msg);

                return Response::redirect('/agenda');
            }
        } finally {
            if ($lockHeld) {
                MysqlNamedLock::release($pdo, $lockKey);
            }
        }

        Flash::set('success', 'Reagendado.');

        return Response::redirect('/agenda');
    }

    public function slotsJson(): Response
    {
        $tid = $this->tenantId();
        if (!RateLimiter::allow('schedule_slots_json:' . $tid . ':' . $this->userId(), 120, 300)) {
            return Response::json(['error' => 'rate_limited'], 429);
        }
        $tenant = (new TenantModel())->findById($tid);
        if ($tenant === null) {
            return Response::json(['error' => 'tenant'], 500);
        }
        $tz = (string) ($tenant['timezone'] ?? 'America/Sao_Paulo');
        $serviceId = (int) ($this->request->query()['service_id'] ?? 0);
        $barberId = (int) ($this->request->query()['barber_id'] ?? 0);
        $date = (string) ($this->request->query()['date'] ?? date('Y-m-d'));
        $any = ($this->request->query()['any'] ?? '') === '1';
        $slot = new SlotService();
        if ($any) {
            $data = $slot->slotsAnyBarber($tid, $serviceId, $date, $tz);
        } else {
            $data = $slot->slotsForBarber($tid, $serviceId, $barberId, $date, $tz);
        }

        return Response::json(['slots' => $data]);
    }
}
