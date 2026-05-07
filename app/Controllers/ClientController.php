<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Helpers\Flash;
use App\Models\AppointmentModel;
use App\Models\ClientModel;

final class ClientController extends Controller
{
    public function index(): Response
    {
        $tid = $this->tenantId();
        $page = max(1, (int) ($this->request->query()['page'] ?? 1));
        $q = isset($this->request->query()['q']) ? trim((string) $this->request->query()['q']) : null;
        $res = (new ClientModel())->paginate($tid, $page, 15, $q);

        return $this->view('clients/index', [
            'title' => 'Clientes',
            'result' => $res,
            'q' => $q ?? '',
            'page' => $page,
            'currentNav' => 'clients',
        ]);
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
