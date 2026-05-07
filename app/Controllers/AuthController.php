<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Enums\UserRole;
use App\Helpers\Csrf;
use App\Helpers\Flash;
use App\Helpers\Str;
use App\Middleware\LoginRateLimitMiddleware;
use App\Models\PasswordResetModel;
use App\Models\SessionModel;
use App\Models\TenantModel;
use App\Models\UserModel;
use App\Services\MailService;
use App\Core\Database;

final class AuthController extends Controller
{
    public function showLogin(): Response
    {
        if (!empty($_SESSION['user_id'])) {
            return Response::redirect($this->postAuthHomePath());
        }

        return $this->view('auth/login', [
            'title' => 'Entrar',
            'csrf' => Csrf::token(),
        ]);
    }

    public function login(): Response
    {
        $email = trim((string) $this->request->input('email'));
        $password = (string) $this->request->input('password');
        $remember = (bool) $this->request->input('remember');

        $users = new UserModel();
        $user = $users->findByEmail($email);
        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            LoginRateLimitMiddleware::recordFailure();
            Flash::set('error', 'Credenciais inválidas.');

            return Response::redirect('/login');
        }
        if (!(bool) $user['is_active']) {
            Flash::set('error', 'Conta desativada.');

            return Response::redirect('/login');
        }

        LoginRateLimitMiddleware::clear();
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['tenant_id'] = $user['tenant_id'] !== null ? (int) $user['tenant_id'] : null;
        $_SESSION['user_role'] = (string) $user['role'];
        $_SESSION['user_name'] = (string) $user['name'];
        $_SESSION['user_email'] = (string) $user['email'];

        if ($remember) {
            $plain = bin2hex(random_bytes(32));
            $hash = hash('sha256', $plain);
            $expires = (new \DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');
            $sessions = new SessionModel();
            $sessions->create((int) $user['id'], $hash, $this->request->ip(), $this->request->userAgent(), $expires);
            $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            setcookie('remember', $plain, [
                'expires' => time() + 30 * 86400,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        Flash::set('success', 'Bem-vindo de volta.');

        return Response::redirect($this->postAuthHomePath());
    }

    public function showRegister(): Response
    {
        return $this->view('auth/register', [
            'title' => 'Criar conta',
            'csrf' => Csrf::token(),
        ]);
    }

    public function register(): Response
    {
        $name = trim((string) $this->request->input('shop_name'));
        $slug = Str::slug(trim((string) $this->request->input('slug')));
        $email = mb_strtolower(trim((string) $this->request->input('email')));
        $phone = trim((string) $this->request->input('phone'));
        $owner = trim((string) $this->request->input('owner_name'));
        $password = (string) $this->request->input('password');

        if (strlen($password) < 8) {
            Flash::set('error', 'A senha deve ter no mínimo 8 caracteres.');

            return Response::redirect('/registrar');
        }
        $tenants = new TenantModel();
        if ($tenants->findBySlug($slug) !== null) {
            Flash::set('error', 'Este identificador (slug) já está em uso.');

            return Response::redirect('/registrar');
        }
        $users = new UserModel();
        if ($users->findByEmail($email) !== null) {
            Flash::set('error', 'Este e-mail já está cadastrado.');

            return Response::redirect('/registrar');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $tenantId = $tenants->create([
                'name' => $name,
                'slug' => $slug,
                'email' => $email,
                'phone' => $phone !== '' ? $phone : null,
                'address' => null,
                'city' => null,
                'state' => null,
                'timezone' => 'America/Sao_Paulo',
            ]);
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $userId = $users->create(
                $tenantId,
                $owner,
                $email,
                $hash,
                UserRole::Owner->value,
                $phone !== '' ? $phone : null
            );
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            Flash::set('error', 'Não foi possível concluir o cadastro. Tente novamente.');

            return Response::redirect('/registrar');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['tenant_id'] = $tenantId;
        $_SESSION['user_role'] = UserRole::Owner->value;
        $_SESSION['user_name'] = $owner;
        $_SESSION['user_email'] = $email;
        Flash::set('success', 'Barbearia criada com sucesso.');

        return Response::redirect('/painel');
    }

    public function logout(): Response
    {
        if (isset($_COOKIE['remember']) && is_string($_COOKIE['remember']) && preg_match('/^[a-f0-9]{64}$/', $_COOKIE['remember']) === 1) {
            $hash = hash('sha256', $_COOKIE['remember']);
            (new SessionModel())->deleteByTokenHash($hash);
        }
        setcookie('remember', '', ['expires' => time() - 3600, 'path' => '/']);
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        return Response::redirect('/login');
    }

    public function showForgot(): Response
    {
        return $this->view('auth/forgot', [
            'title' => 'Recuperar senha',
            'csrf' => Csrf::token(),
        ]);
    }

    public function forgot(): Response
    {
        $email = mb_strtolower(trim((string) $this->request->input('email')));
        $users = new UserModel();
        $user = $users->findByEmail($email);
        if ($user !== null) {
            $plain = bin2hex(random_bytes(32));
            $hash = hash('sha256', $plain);
            (new PasswordResetModel())->upsert($email, $hash);
            $cfg = require $this->app->root() . '/config/mail.php';
            $mail = new MailService($cfg);
            $base = rtrim((string) ($this->app->config()['url'] ?? ''), '/');
            $link = $base . '/redefinir-senha?token=' . urlencode($plain);
            $body = '<p>Olá ' . e((string) $user['name']) . ',</p><p>Para redefinir sua senha, acesse o link abaixo (válido por 1 hora):</p>'
                . '<p><a href="' . e($link) . '">' . e($link) . '</a></p>';
            $mail->send($email, (string) $user['name'], 'Redefinição de senha', $body);
        }
        Flash::set('success', 'Se o e-mail existir, enviaremos instruções em instantes.');

        return Response::redirect('/esqueci-senha');
    }

    public function showReset(): Response
    {
        $token = (string) ($this->request->query()['token'] ?? '');
        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            Flash::set('error', 'Token inválido.');

            return Response::redirect('/esqueci-senha');
        }

        return $this->view('auth/reset', [
            'title' => 'Nova senha',
            'csrf' => Csrf::token(),
            'token' => $token,
        ]);
    }

    public function reset(): Response
    {
        $token = (string) $this->request->input('token');
        $password = (string) $this->request->input('password');
        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            Flash::set('error', 'Token inválido.');

            return Response::redirect('/esqueci-senha');
        }
        if (strlen($password) < 8) {
            Flash::set('error', 'Senha muito curta.');

            return Response::redirect('/redefinir-senha?token=' . urlencode($token));
        }
        $hash = hash('sha256', $token);
        $pr = new PasswordResetModel();
        $row = $pr->findByTokenHash($hash);
        if ($row === null) {
            Flash::set('error', 'Token inválido ou expirado.');

            return Response::redirect('/esqueci-senha');
        }
        $created = new \DateTimeImmutable((string) $row['created_at']);
        if ($created < new \DateTimeImmutable('-1 hour')) {
            Flash::set('error', 'Token expirado. Solicite novamente.');

            return Response::redirect('/esqueci-senha');
        }
        $email = (string) $row['email'];
        $users = new UserModel();
        $user = $users->findByEmail($email);
        if ($user === null) {
            Flash::set('error', 'Usuário não encontrado.');

            return Response::redirect('/login');
        }
        $users->updatePassword((int) $user['id'], password_hash($password, PASSWORD_BCRYPT));
        $pr->delete($email);
        Flash::set('success', 'Senha atualizada. Faça login.');

        return Response::redirect('/login');
    }
}
