<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Helpers\Flash;
use App\Models\AppointmentModel;
use App\Models\ClientModel;
use App\Models\TenantAuditModel;

final class ClientController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): Response
    {
        $tid = $this->tenantId();
        $page = max(1, (int) ($this->request->query()['page'] ?? 1));
        $q = isset($this->request->query()['q']) ? trim((string) $this->request->query()['q']) : null;
        $res = (new ClientModel())->paginate($tid, $page, self::PER_PAGE, $q);
        $totalPages = max(1, (int) ceil($res['total'] / self::PER_PAGE));

        return $this->view('clients/index', [
            'title' => 'Clientes',
            'result' => $res,
            'q' => $q ?? '',
            'page' => $page,
            'totalPages' => $totalPages,
            'perPage' => self::PER_PAGE,
            'currentNav' => 'clients',
        ]);
    }

    public function createForm(): Response
    {
        return $this->view('clients/form', [
            'title' => 'Novo cliente',
            'client' => ['id' => 0, 'name' => '', 'email' => '', 'phone' => '', 'birth_date' => '', 'notes' => ''],
            'isNew' => true,
            'currentNav' => 'clients',
        ]);
    }

    public function create(): Response
    {
        $tid = $this->tenantId();
        $data = [
            'name' => trim((string) $this->request->input('name')),
            'email' => trim((string) $this->request->input('email')),
            'phone' => trim((string) $this->request->input('phone')),
            'birth_date' => trim((string) $this->request->input('birth_date')),
            'notes' => trim((string) $this->request->input('notes')),
        ];
        if ($data['name'] === '') {
            Flash::set('error', 'Informe o nome do cliente.');

            return Response::redirect('/clientes/novo');
        }
        $id = (new ClientModel())->create($tid, $data);
        (new TenantAuditModel())->log($tid, $this->userId(), 'client_create', ['client_id' => $id, 'name' => $data['name']]);
        Flash::set('success', 'Cliente cadastrado.');

        return Response::redirect('/clientes/' . $id);
    }

    public function show(int $id): Response
    {
        $tid = $this->tenantId();
        $c = (new ClientModel())->find($tid, $id);
        if ($c === null) {
            Flash::set('error', 'Cliente não encontrado.');

            return Response::redirect('/clientes');
        }
        $appts = (new AppointmentModel())->forClient($tid, $id);

        return $this->view('clients/show', [
            'title' => 'Cliente',
            'client' => $c,
            'appointments' => $appts,
            'currentNav' => 'clients',
        ]);
    }

    public function editForm(int $id): Response
    {
        $tid = $this->tenantId();
        $c = (new ClientModel())->find($tid, $id);
        if ($c === null) {
            return Response::redirect('/clientes');
        }

        return $this->view('clients/form', [
            'title' => 'Editar cliente',
            'client' => $c,
            'currentNav' => 'clients',
        ]);
    }

    public function update(int $id): Response
    {
        $tid = $this->tenantId();
        $m = new ClientModel();
        if ($m->find($tid, $id) === null) {
            return Response::redirect('/clientes');
        }
        $m->update($tid, $id, [
            'name' => trim((string) $this->request->input('name')),
            'email' => trim((string) $this->request->input('email')),
            'phone' => trim((string) $this->request->input('phone')),
            'birth_date' => trim((string) $this->request->input('birth_date')),
            'notes' => trim((string) $this->request->input('notes')),
        ]);
        Flash::set('success', 'Cliente atualizado.');

        return Response::redirect('/clientes/' . $id);
    }

    public function delete(int $id): Response
    {
        $tid = $this->tenantId();
        $m = new ClientModel();
        $client = $m->find($tid, $id);
        if ($client === null) {
            Flash::set('error', 'Cliente não encontrado.');

            return Response::redirect('/clientes');
        }
        $apptCount = $m->countAppointments($tid, $id);
        try {
            $m->softDelete($tid, $id);
        } catch (\Throwable) {
            Flash::set('error', 'Não foi possível excluir o cliente.');

            return Response::redirect('/clientes/' . $id);
        }
        (new TenantAuditModel())->log($tid, $this->userId(), 'client_delete', [
            'client_id' => $id,
            'name' => (string) ($client['name'] ?? ''),
            'appointments' => $apptCount,
        ]);
        $name = (string) ($client['name'] ?? 'Cliente');
        $msg = $apptCount > 0
            ? "Cliente \"{$name}\" removido da lista ({$apptCount} agendamento(s) no histórico)."
            : "Cliente \"{$name}\" removido.";
        Flash::set('success', $msg);

        return Response::redirect('/clientes');
    }

    public function export(): Response
    {
        $tid = $this->tenantId();
        $res = (new ClientModel())->paginate($tid, 1, 50_000, null);
        $lines = ['nome,email,telefone,aniversario,criado_em'];
        foreach ($res['rows'] as $r) {
            $lines[] = sprintf(
                '"%s","%s","%s","%s","%s"',
                str_replace('"', '""', (string) $r['name']),
                str_replace('"', '""', (string) ($r['email'] ?? '')),
                str_replace('"', '""', (string) ($r['phone'] ?? '')),
                str_replace('"', '""', (string) ($r['birth_date'] ?? '')),
                str_replace('"', '""', (string) $r['created_at'])
            );
        }

        return Response::csv('clientes.csv', implode("\n", $lines));
    }
}
