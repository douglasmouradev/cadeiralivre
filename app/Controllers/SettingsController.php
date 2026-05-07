<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Enums\UserRole;
use App\Helpers\Flash;
use App\Models\TenantModel;
use App\Models\UserModel;
use App\Services\UploadService;

final class SettingsController extends Controller
{
    public function index(): Response
    {
        $tid = $this->tenantId();
        $tenant = (new TenantModel())->findById($tid);
        $userModel = new UserModel();
        $user = $userModel->findById($this->userId());
        $canManageTeam = in_array($this->userRole(), [UserRole::Owner->value, UserRole::Superadmin->value], true);
        $administrativeStaff = $canManageTeam ? $userModel->listAdministrativeByTenant($tid) : [];

        return $this->view('settings/index', [
            'title' => 'Configurações',
            'tenant' => $tenant,
            'user' => $user,
            'currentNav' => 'settings',
            'canManageTeam' => $canManageTeam,
            'administrativeStaff' => $administrativeStaff,
        ]);
    }

    public function updateTenant(): Response
    {
        $tid = $this->tenantId();
        (new TenantModel())->update($tid, [
            'name' => trim((string) $this->request->input('name')),
            'email' => mb_strtolower(trim((string) $this->request->input('email'))),
            'phone' => trim((string) $this->request->input('phone')) ?: null,
            'address' => trim((string) $this->request->input('address')) ?: null,
            'city' => trim((string) $this->request->input('city')) ?: null,
            'state' => trim((string) $this->request->input('state')) ?: null,
            'primary_color' => trim((string) $this->request->input('primary_color')),
            'timezone' => trim((string) $this->request->input('timezone')),
        ]);
        Flash::set('success', 'Dados da barbearia salvos.');

        return Response::redirect('/configuracoes');
    }

    public function uploadLogo(): Response
    {
        $tid = $this->tenantId();
        $files = $this->request->files();
        $file = $files['logo'] ?? null;
        if (!is_array($file)) {
            Flash::set('error', 'Arquivo inválido.');

            return Response::redirect('/configuracoes');
        }
        $cfg = $this->app->config();
        $dir = $this->app->root() . '/storage/uploads/logos';
        $up = new UploadService($dir, (int) ($cfg['upload_max_bytes'] ?? 2_097_152));
        try {
            $name = $up->storeImage($file);
        } catch (\Throwable) {
            Flash::set('error', 'Não foi possível enviar a logo.');

            return Response::redirect('/configuracoes');
        }
        (new TenantModel())->update($tid, ['logo_path' => 'logos/' . $name]);
        Flash::set('success', 'Logo atualizada.');

        return Response::redirect('/configuracoes');
    }

    public function updateProfile(): Response
    {
        $u = new UserModel();
        $u->updateProfile($this->userId(), trim((string) $this->request->input('name')), trim((string) $this->request->input('phone')) ?: null);
        $_SESSION['user_name'] = trim((string) $this->request->input('name'));
        Flash::set('success', 'Perfil atualizado.');

        return Response::redirect('/configuracoes');
    }

    public function uploadAvatar(): Response
    {
        $files = $this->request->files();
        $file = $files['avatar'] ?? null;
        if (!is_array($file)) {
            Flash::set('error', 'Arquivo inválido.');

            return Response::redirect('/configuracoes');
        }
        $cfg = $this->app->config();
        $dir = $this->app->root() . '/storage/uploads/avatars';
        $up = new UploadService($dir, (int) ($cfg['upload_max_bytes'] ?? 2_097_152));
        try {
            $name = $up->storeImage($file);
        } catch (\Throwable) {
            Flash::set('error', 'Não foi possível enviar a foto.');

            return Response::redirect('/configuracoes');
        }
        (new UserModel())->setAvatar($this->userId(), 'avatars/' . $name);
        Flash::set('success', 'Foto atualizada.');

        return Response::redirect('/configuracoes');
    }

    /** Cria recepcionista ou co-dono (role owner) — apenas dono ou superadmin. */
    public function storeStaffUser(): Response
    {
        if (!in_array($this->userRole(), [UserRole::Owner->value, UserRole::Superadmin->value], true)) {
            Flash::set('error', 'Apenas o dono pode criar utilizadores da equipe administrativa.');

            return Response::redirect('/configuracoes');
        }

        $tid = $this->tenantId();
        $name = trim((string) $this->request->input('staff_name'));
        $email = mb_strtolower(trim((string) $this->request->input('staff_email')));
        $password = (string) $this->request->input('staff_password');
        $role = trim((string) $this->request->input('staff_role'));

        if ($name === '' || $email === '') {
            Flash::set('error', 'Nome e e-mail são obrigatórios.');

            return Response::redirect('/configuracoes');
        }
        if (strlen($password) < 8) {
            Flash::set('error', 'A senha deve ter no mínimo 8 caracteres.');

            return Response::redirect('/configuracoes');
        }
        $allowedRoles = [UserRole::Receptionist->value, UserRole::Owner->value];
        if (!in_array($role, $allowedRoles, true)) {
            Flash::set('error', 'Função inválida.');

            return Response::redirect('/configuracoes');
        }

        $users = new UserModel();
        if ($users->findByEmail($email) !== null) {
            Flash::set('error', 'Este e-mail já está cadastrado.');

            return Response::redirect('/configuracoes');
        }

        try {
            $users->create($tid, $name, $email, password_hash($password, PASSWORD_BCRYPT), $role, null);
        } catch (\Throwable) {
            Flash::set('error', 'Não foi possível criar o utilizador.');

            return Response::redirect('/configuracoes');
        }

        Flash::set('success', 'Utilizador criado. Ele pode entrar em /login com o e-mail e a senha definidos.');

        return Response::redirect('/configuracoes');
    }
}
