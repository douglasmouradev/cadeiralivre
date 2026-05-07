<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Helpers\Flash;
use App\Models\ServiceModel;

final class ServiceController extends Controller
{
    public function index(): Response
    {
        $tid = $this->tenantId();
        $services = (new ServiceModel())->allForTenant($tid);

        return $this->view('services/index', [
            'title' => 'Serviços',
            'services' => $services,
            'currentNav' => 'services',
        ]);
    }

    public function createForm(): Response
    {
        return $this->view('services/form', [
            'title' => 'Novo serviço',
            'service' => null,
            'currentNav' => 'services',
        ]);
    }

    public function create(): Response
    {
        $tid = $this->tenantId();
        $m = new ServiceModel();
        $m->create($tid, $this->payload());
        Flash::set('success', 'Serviço criado.');

        return Response::redirect('/servicos');
    }

    public function editForm(int $id): Response
    {
        $tid = $this->tenantId();
        $s = (new ServiceModel())->find($tid, $id);
        if ($s === null) {
            Flash::set('error', 'Serviço não encontrado.');

            return Response::redirect('/servicos');
        }

        return $this->view('services/form', [
            'title' => 'Editar serviço',
            'service' => $s,
            'currentNav' => 'services',
        ]);
    }

    public function update(int $id): Response
    {
        $tid = $this->tenantId();
        $m = new ServiceModel();
        if ($m->find($tid, $id) === null) {
            Flash::set('error', 'Serviço não encontrado.');

            return Response::redirect('/servicos');
        }
        $m->update($tid, $id, $this->payload());
        Flash::set('success', 'Serviço atualizado.');

        return Response::redirect('/servicos');
    }

    public function delete(int $id): Response
    {
        $tid = $this->tenantId();
        (new ServiceModel())->delete($tid, $id);
        Flash::set('success', 'Serviço removido.');

        return Response::redirect('/servicos');
    }

    public function reorder(): Response
    {
        $tid = $this->tenantId();
        $order = $this->request->input('order');
        if (!is_array($order)) {
            return Response::json(['ok' => false], 422);
        }
        $ids = array_map(intval(...), array_values($order));
        (new ServiceModel())->reorder($tid, $ids);

        return Response::json(['ok' => true]);
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'name' => trim((string) $this->request->input('name')),
            'description' => trim((string) $this->request->input('description')),
            'duration_minutes' => (int) $this->request->input('duration_minutes'),
            'price' => (float) str_replace(',', '.', (string) $this->request->input('price')),
            'category' => trim((string) $this->request->input('category')),
            'is_active' => (string) $this->request->input('is_active') === '1',
            'display_order' => (int) $this->request->input('display_order', 0),
        ];
    }
}
