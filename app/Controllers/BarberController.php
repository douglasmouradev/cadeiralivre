<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Enums\UserRole;
use App\Helpers\Flash;
use App\Models\BarberModel;
use App\Models\BlockedTimeModel;
use App\Models\ServiceModel;
use App\Models\UserModel;
use App\Models\WorkingHoursModel;

final class BarberController extends Controller
{
    public function index(): Response
    {
        $tid = $this->tenantId();
        $rows = (new BarberModel())->listWithUser($tid);

        return $this->view('barbers/index', [
            'title' => 'Barbeiros',
            'barbers' => $rows,
            'currentNav' => 'barbers',
        ]);
    }

    public function createForm(): Response
    {
        $tid = $this->tenantId();
        $services = (new ServiceModel())->allForTenant($tid, true);

        return $this->view('barbers/form', [
            'title' => 'Novo barbeiro',
            'barber' => null,
            'services' => $services,
            'currentNav' => 'barbers',
        ]);
    }

    public function create(): Response
    {
        $tid = $this->tenantId();
        $name = trim((string) $this->request->input('name'));
        $email = mb_strtolower(trim((string) $this->request->input('email')));
        $password = (string) $this->request->input('password');
        $phone = trim((string) $this->request->input('phone'));
        if (strlen($password) < 8) {
            Flash::set('error', 'Senha mínima de 8 caracteres.');

            return Response::redirect('/barbeiros/novo');
        }
        $users = new UserModel();
        if ($users->findByEmail($email) !== null) {
            Flash::set('error', 'E-mail já cadastrado.');

            return Response::redirect('/barbeiros/novo');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $uid = $users->create($tid, $name, $email, password_hash($password, PASSWORD_BCRYPT), UserRole::Barber->value, $phone !== '' ? $phone : null);
            $barbers = new BarberModel();
            $bid = $barbers->create($tid, $uid, [
                'bio' => trim((string) $this->request->input('bio')),
                'specialties' => $this->specialtiesFromRequest(),
                'commission_percent' => (float) str_replace(',', '.', (string) $this->request->input('commission_percent')),
                'is_available' => true,
            ]);
            $this->seedDefaultHours($tid, $bid);
            $sids = array_map(intval(...), (array) $this->request->input('service_ids', []));
            $barbers->syncServices($tid, $bid, $sids, []);
            $pdo->commit();
        } catch (\Throwable) {
            $pdo->rollBack();
            Flash::set('error', 'Não foi possível criar o barbeiro.');

            return Response::redirect('/barbeiros/novo');
        }
        Flash::set('success', 'Barbeiro cadastrado.');

        return Response::redirect('/barbeiros');
    }

    public function editForm(int $id): Response
    {
        $tid = $this->tenantId();
        $b = (new BarberModel())->find($tid, $id);
        if ($b === null) {
            Flash::set('error', 'Barbeiro não encontrado.');

            return Response::redirect('/barbeiros');
        }
        $services = (new ServiceModel())->allForTenant($tid, true);
        $selected = (new BarberModel())->serviceIdsForBarber($id);
        $hours = (new WorkingHoursModel())->forBarber($tid, $id);
        $blocks = (new BlockedTimeModel())->forBarber($tid, $id);

        return $this->view('barbers/edit', [
            'title' => 'Editar barbeiro',
            'barber' => $b,
            'services' => $services,
            'selectedServices' => $selected,
            'hours' => $hours,
            'blocks' => $blocks,
            'currentNav' => 'barbers',
        ]);
    }

    public function update(int $id): Response
    {
        $tid = $this->tenantId();
        $barbers = new BarberModel();
        if ($barbers->find($tid, $id) === null) {
            Flash::set('error', 'Barbeiro não encontrado.');

            return Response::redirect('/barbeiros');
        }
        $barbers->update($tid, $id, [
            'bio' => trim((string) $this->request->input('bio')),
            'specialties' => $this->specialtiesFromRequest(),
            'commission_percent' => (float) str_replace(',', '.', (string) $this->request->input('commission_percent')),
            'is_available' => (string) $this->request->input('is_available') === '1',
        ]);
        $sids = array_map(intval(...), (array) $this->request->input('service_ids', []));
        $barbers->syncServices($tid, $id, $sids, []);
        Flash::set('success', 'Barbeiro atualizado.');

        return Response::redirect('/barbeiros/' . $id . '/editar');
    }

    public function saveHours(int $id): Response
    {
        $tid = $this->tenantId();
        $barbers = new BarberModel();
        if ($barbers->find($tid, $id) === null) {
            return Response::redirect('/barbeiros');
        }
        $rows = [];
        for ($d = 0; $d <= 6; $d++) {
            $st = (string) $this->request->input('start_' . $d);
            $en = (string) $this->request->input('end_' . $d);
            if (strlen($st) === 5) {
                $st .= ':00';
            }
            if (strlen($en) === 5) {
                $en .= ':00';
            }
            $rows[] = [
                'day_of_week' => $d,
                'start_time' => $st,
                'end_time' => $en,
                'is_day_off' => (bool) $this->request->input('off_' . $d),
            ];
        }
        (new WorkingHoursModel())->replaceWeek($tid, $id, $rows);
        Flash::set('success', 'Horários salvos.');

        return Response::redirect('/barbeiros/' . $id . '/editar');
    }

    public function addBlock(int $id): Response
    {
        $tid = $this->tenantId();
        $barbers = new BarberModel();
        if ($barbers->find($tid, $id) === null) {
            return Response::redirect('/barbeiros');
        }
        $start = str_replace('T', ' ', trim((string) $this->request->input('block_start')));
        $end = str_replace('T', ' ', trim((string) $this->request->input('block_end')));
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $start) === 1) {
            $start .= ':00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $end) === 1) {
            $end .= ':00';
        }
        (new BlockedTimeModel())->create(
            $tid,
            $id,
            $start,
            $end,
            trim((string) $this->request->input('block_reason')) ?: null
        );
        Flash::set('success', 'Bloqueio adicionado.');

        return Response::redirect('/barbeiros/' . $id . '/editar');
    }

    public function deleteBlock(int $id, int $blockId): Response
    {
        $tid = $this->tenantId();
        (new BlockedTimeModel())->delete($tid, $blockId);
        Flash::set('success', 'Bloqueio removido.');

        return Response::redirect('/barbeiros/' . $id . '/editar');
    }

    public function toggle(int $id): Response
    {
        $tid = $this->tenantId();
        $on = (bool) $this->request->input('available');
        (new BarberModel())->setAvailability($tid, $id, $on);

        return Response::redirect('/barbeiros');
    }

    public function deactivate(int $id): Response
    {
        $tid = $this->tenantId();
        (new BarberModel())->deactivate($tid, $id);
        Flash::set('success', 'Barbeiro desativado.');

        return Response::redirect('/barbeiros');
    }

    /** @return list<string> */
    private function specialtiesFromRequest(): array
    {
        $text = trim((string) $this->request->input('specialties_text'));
        if ($text === '') {
            return [];
        }
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];

        return array_values(array_filter(array_map(trim(...), $lines)));
    }

    private function seedDefaultHours(int $tenantId, int $barberId): void
    {
        $rows = [];
        for ($d = 0; $d <= 6; $d++) {
            $isOff = $d === 0;
            $rows[] = [
                'day_of_week' => $d,
                'start_time' => $isOff ? '09:00:00' : '09:00:00',
                'end_time' => $isOff ? '18:00:00' : ($d === 6 ? '14:00:00' : '18:00:00'),
                'is_day_off' => $isOff,
            ];
        }
        (new WorkingHoursModel())->replaceWeek($tenantId, $barberId, $rows);
    }
}
