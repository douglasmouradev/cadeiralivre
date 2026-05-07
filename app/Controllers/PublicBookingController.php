<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Enums\AppointmentStatus;
use App\Helpers\Csrf;
use App\Helpers\Flash;
use App\Helpers\MysqlNamedLock;
use App\Helpers\RateLimiter;
use App\Helpers\Str;
use App\Models\AppointmentModel;
use App\Models\BarberModel;
use App\Models\ClientModel;
use App\Models\ServiceModel;
use App\Models\TenantModel;
use App\Services\MailService;
use App\Services\SlotService;
use Throwable;

final class PublicBookingController extends Controller
{
    private function tenantFromSlug(string $slug): ?array
    {
        $t = (new TenantModel())->findBySlug($slug);
        if ($t === null || (string) $t['status'] === 'suspended') {
            return null;
        }

        return $t;
    }

    /** Cliente com sessão do portal (mesma tenant). */
    private function portalClientForTenant(int $tenantId): ?array
    {
        $pid = (int) ($_SESSION['portal_client_id'] ?? 0);
        $ptid = (int) ($_SESSION['portal_tenant_id'] ?? 0);
        if ($pid <= 0 || $ptid !== $tenantId) {
            return null;
        }
        $c = (new ClientModel())->find($tenantId, $pid);
        if ($c === null || empty($c['portal_password_hash'])) {
            return null;
        }

        return $c;
    }

    /** Monta texto de observações (forma de pagamento) para appointments.notes. */
    private function buildPublicBookingNotes(string $method, string $freeNote): ?string
    {
        $method = trim($method);
        $freeNote = trim($freeNote);
        $labels = [
            'pix' => 'Pix',
            'cash' => 'Dinheiro',
            'card' => 'Cartão (crédito ou débito)',
            'on_site' => 'Pagar no local (combinar na barbearia)',
            'unsure' => 'A definir depois',
        ];
        $parts = [];
        if ($method !== '' && isset($labels[$method])) {
            $parts[] = 'Forma de pagamento: ' . $labels[$method];
        }
        if ($freeNote !== '') {
            $parts[] = 'Obs. pagamento / outras observações: ' . mb_substr($freeNote, 0, 800);
        }
        if ($parts === []) {
            return null;
        }
        $out = implode("\n", $parts);

        return mb_substr($out, 0, 4000);
    }

    private function bookingRedirectWithError(string $slug, string $message): Response
    {
        Flash::set('error', $message);

        return Response::redirect('/agendar/' . rawurlencode($slug));
    }

    private function normalizePublicPaymentMethod(string $raw): ?string
    {
        $raw = trim($raw);
        $allowed = ['pix', 'cash', 'card', 'on_site', 'unsure'];

        return in_array($raw, $allowed, true) ? $raw : null;
    }

    public function index(string $slug): Response
    {
        $tenant = $this->tenantFromSlug($slug);
        if ($tenant === null) {
            return Response::html('<!DOCTYPE html><html><body><p>Barbearia não encontrada.</p></body></html>', 404);
        }
        $tid = (int) $tenant['id'];
        $services = (new ServiceModel())->allForTenant($tid, true);
        $barbers = (new BarberModel())->availableBarbersForTenant($tid);

        return $this->view('public/booking', [
            'title' => 'Agendar — ' . $tenant['name'],
            'tenant' => $tenant,
            'services' => $services,
            'barbers' => $barbers,
            'slug' => $slug,
            'csrf' => Csrf::token(),
            'portal_client' => $this->portalClientForTenant($tid),
        ]);
    }

    public function myAppointments(string $slug): Response
    {
        $tenant = $this->tenantFromSlug($slug);
        if ($tenant === null) {
            return Response::html('<!DOCTYPE html><html><body><p>Barbearia não encontrada.</p></body></html>', 404);
        }
        $tid = (int) $tenant['id'];
        $portal = $this->portalClientForTenant($tid);
        if ($portal === null) {
            Flash::set('error', 'Entre como cliente para ver seus agendamentos.');

            return Response::redirect('/cliente/' . rawurlencode($slug) . '/entrar');
        }
        $rows = (new AppointmentModel())->forClient($tid, (int) $portal['id']);

        return $this->view('public/my_appointments', [
            'title' => 'Meus agendamentos — ' . $tenant['name'],
            'tenant' => $tenant,
            'slug' => $slug,
            'portal_client' => $portal,
            'appointments' => $rows,
            'timezone' => (string) ($tenant['timezone'] ?? 'America/Sao_Paulo'),
        ]);
    }

    public function portalAppointmentConfirm(string $slug): Response
    {
        $tenant = $this->tenantFromSlug($slug);
        if ($tenant === null) {
            return Response::redirect('/');
        }
        $tid = (int) $tenant['id'];
        $portal = $this->portalClientForTenant($tid);
        if ($portal === null) {
            Flash::set('error', 'Entre como cliente para confirmar o agendamento.');

            return Response::redirect('/cliente/' . rawurlencode($slug) . '/entrar');
        }
        $appointmentId = (int) $this->request->input('appointment_id');
        if ($appointmentId <= 0) {
            Flash::set('error', 'Agendamento inválido.');

            return Response::redirect('/agendar/' . rawurlencode($slug) . '/meus-agendamentos');
        }
        $ap = new AppointmentModel();
        $row = $ap->find($tid, $appointmentId);
        if ($row === null || (int) $row['client_id'] !== (int) $portal['id']) {
            Flash::set('error', 'Agendamento não encontrado.');

            return Response::redirect('/agendar/' . rawurlencode($slug) . '/meus-agendamentos');
        }
        if ((string) $row['status'] !== AppointmentStatus::Pending->value) {
            Flash::set('error', 'Este agendamento não está pendente de confirmação.');

            return Response::redirect('/agendar/' . rawurlencode($slug) . '/meus-agendamentos');
        }
        if ($ap->confirmPendingForPortalClient($tid, $appointmentId, (int) $portal['id'])) {
            $ap->addHistory(
                $appointmentId,
                $tid,
                AppointmentStatus::Pending->value,
                AppointmentStatus::Confirmed->value,
                null,
                'Confirmado pelo cliente (portal)',
            );
            Flash::set('success', 'Agendamento confirmado.');
        } else {
            Flash::set('error', 'Não foi possível confirmar. Tente novamente.');
        }

        return Response::redirect('/agendar/' . rawurlencode($slug) . '/meus-agendamentos');
    }

    public function portalAppointmentCancel(string $slug): Response
    {
        $tenant = $this->tenantFromSlug($slug);
        if ($tenant === null) {
            return Response::redirect('/');
        }
        $tid = (int) $tenant['id'];
        $portal = $this->portalClientForTenant($tid);
        if ($portal === null) {
            Flash::set('error', 'Entre como cliente para cancelar o agendamento.');

            return Response::redirect('/cliente/' . rawurlencode($slug) . '/entrar');
        }
        $appointmentId = (int) $this->request->input('appointment_id');
        if ($appointmentId <= 0) {
            Flash::set('error', 'Agendamento inválido.');

            return Response::redirect('/agendar/' . rawurlencode($slug) . '/meus-agendamentos');
        }
        $ap = new AppointmentModel();
        $row = $ap->find($tid, $appointmentId);
        if ($row === null || (int) $row['client_id'] !== (int) $portal['id']) {
            Flash::set('error', 'Agendamento não encontrado.');

            return Response::redirect('/agendar/' . rawurlencode($slug) . '/meus-agendamentos');
        }
        $from = (string) $row['status'];
        if (!in_array($from, [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value], true)) {
            Flash::set('error', 'Este agendamento não pode ser cancelado aqui.');

            return Response::redirect('/agendar/' . rawurlencode($slug) . '/meus-agendamentos');
        }
        $ap->updateStatus($tid, $appointmentId, AppointmentStatus::Cancelled->value, 'Cancelado pelo cliente (portal)');
        $ap->addHistory($appointmentId, $tid, $from, AppointmentStatus::Cancelled->value, null, 'Portal cliente');
        Flash::set('success', 'Agendamento cancelado.');

        return Response::redirect('/agendar/' . rawurlencode($slug) . '/meus-agendamentos');
    }

    public function slots(string $slug): Response
    {
        $tenant = $this->tenantFromSlug($slug);
        if ($tenant === null) {
            return Response::json(['error' => 'not_found'], 404);
        }
        $tid = (int) $tenant['id'];
        $tz = (string) ($tenant['timezone'] ?? 'America/Sao_Paulo');
        $ip = $this->request->ip() ?? '0.0.0.0';
        if (!RateLimiter::allow('slots_get:' . $ip . ':' . $slug, 90, 300)) {
            return Response::json(['error' => 'rate_limited'], 429);
        }
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

    public function book(string $slug): Response
    {
        $ip = $this->request->ip() ?? '0.0.0.0';
        if (
            !RateLimiter::allow('book_post:' . $ip . ':' . $slug, 15, 3600)
            || !RateLimiter::allow('book_burst:' . $ip . ':' . $slug, 5, 120)
        ) {
            return $this->bookingRedirectWithError($slug, 'Muitas tentativas de agendamento. Aguarde um pouco e tente novamente.');
        }

        $tenant = $this->tenantFromSlug($slug);
        if ($tenant === null) {
            return Response::redirect('/');
        }
        $tid = (int) $tenant['id'];
        $tz = (string) ($tenant['timezone'] ?? 'America/Sao_Paulo');
        $clients = new ClientModel();
        $portal = $this->portalClientForTenant($tid);
        $clientId = 0;
        $phone = '';
        $phoneInputPortal = '';
        if ($portal !== null) {
            $clientId = (int) $portal['id'];
            $name = trim((string) $portal['name']);
            $email = mb_strtolower(trim((string) ($portal['email'] ?? '')));
            $phoneInputPortal = trim((string) $this->request->input('client_phone'));
            if ($name === '') {
                return $this->bookingRedirectWithError($slug, 'Conta sem nome. Atualize seus dados com a barbearia.');
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->bookingRedirectWithError($slug, 'E-mail da conta inválido. Entre em contato com a barbearia.');
            }
        } else {
            $name = trim((string) $this->request->input('client_name'));
            $email = mb_strtolower(trim((string) $this->request->input('client_email')));
            $phone = trim((string) $this->request->input('client_phone'));
            if ($name === '') {
                return $this->bookingRedirectWithError($slug, 'Informe seu nome.');
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->bookingRedirectWithError($slug, 'Informe um e-mail válido para receber a confirmação.');
            }
        }
        $serviceId = (int) $this->request->input('service_id');
        $barberId = (int) $this->request->input('barber_id');
        $start = (string) $this->request->input('start_datetime');
        $barberModeRaw = trim((string) $this->request->input('barber_mode'));
        $barberMode = $barberModeRaw === 'any' ? 'any' : 'one';
        $payMethodKey = $this->normalizePublicPaymentMethod((string) $this->request->input('payment_method'));
        $payNoteRaw = trim((string) $this->request->input('payment_note'));
        if ($payNoteRaw !== '') {
            $payNoteRaw = mb_substr($payNoteRaw, 0, 800);
        }
        $apptNotes = $this->buildPublicBookingNotes($payMethodKey ?? '', $payNoteRaw);
        if ($serviceId <= 0 || $barberId <= 0 || $start === '') {
            return $this->bookingRedirectWithError($slug, 'Dados incompletos. Escolha serviço, profissional e horário novamente.');
        }
        $svc = (new ServiceModel())->find($tid, $serviceId);
        if ($svc === null || !(bool) $svc['is_active']) {
            return $this->bookingRedirectWithError($slug, 'Serviço inválido ou indisponível.');
        }
        $barberRow = (new BarberModel())->find($tid, $barberId);
        if ($barberRow === null || !(bool) ($barberRow['is_available'] ?? false)) {
            return $this->bookingRedirectWithError($slug, 'Profissional inválido ou indisponível.');
        }
        $duration = (int) $svc['duration_minutes'];
        $startDt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $start) ?: \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $start);
        if ($startDt === false) {
            return $this->bookingRedirectWithError($slug, 'Data ou horário inválidos.');
        }
        $startStr = $startDt->format('Y-m-d H:i:s');
        $slotSvc = new SlotService();
        if (!$slotSvc->isPublicSlotValid($tid, $serviceId, $barberId, $startStr, $tz, $barberMode)) {
            return $this->bookingRedirectWithError($slug, 'Este horário não está mais disponível. Escolha outro.');
        }
        $endStr = $startDt->add(new \DateInterval('PT' . $duration . 'M'))->format('Y-m-d H:i:s');

        $lockKey = substr('bk_' . $tid . '_' . $barberId . '_' . hash('sha256', $startStr), 0, 64);
        $pdo = Database::connection();
        $lockHeld = false;
        try {
            if (!MysqlNamedLock::acquire($pdo, $lockKey, 10)) {
                return $this->bookingRedirectWithError($slug, 'Não foi possível reservar neste momento. Tente de novo em instantes.');
            }
            $lockHeld = true;

            $guestName = $name;
            $guestEmail = $email;
            $guestPhone = $portal === null ? $phone : '';

            $appointmentId = Database::transaction(function () use (
                $tid,
                $tz,
                $portal,
                $clientId,
                $phoneInputPortal,
                $guestName,
                $guestEmail,
                $guestPhone,
                $clients,
                $serviceId,
                $barberId,
                $startStr,
                $endStr,
                $barberMode,
                $apptNotes,
                $payMethodKey,
                $payNoteRaw,
                $svc,
                $slotSvc,
            ): int {
                $resolvedClientId = $clientId;
                if ($portal !== null) {
                    if ($phoneInputPortal !== '') {
                        $clients->updatePhone($tid, $resolvedClientId, $phoneInputPortal);
                    }
                } else {
                    $existing = $clients->findByTenantEmail($tid, $guestEmail);
                    if ($existing !== null) {
                        $resolvedClientId = (int) $existing['id'];
                        $clients->update($tid, $resolvedClientId, [
                            'name' => $guestName,
                            'email' => $guestEmail,
                            'phone' => $guestPhone,
                            'birth_date' => (string) ($existing['birth_date'] ?? ''),
                            'notes' => (string) ($existing['notes'] ?? ''),
                        ]);
                    } else {
                        try {
                            $resolvedClientId = $clients->create($tid, [
                                'name' => $guestName,
                                'email' => $guestEmail,
                                'phone' => $guestPhone,
                                'birth_date' => '',
                                'notes' => '',
                            ]);
                        } catch (\PDOException $e) {
                            $dup = str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'uk_clients_tenant_email');
                            if (!$dup) {
                                throw $e;
                            }
                            $race = $clients->findByTenantEmail($tid, $guestEmail);
                            if ($race === null) {
                                throw $e;
                            }
                            $resolvedClientId = (int) $race['id'];
                            $clients->update($tid, $resolvedClientId, [
                                'name' => $guestName,
                                'email' => $guestEmail,
                                'phone' => $guestPhone,
                                'birth_date' => (string) ($race['birth_date'] ?? ''),
                                'notes' => (string) ($race['notes'] ?? ''),
                            ]);
                        }
                    }
                }

                if (!$slotSvc->isPublicSlotValid($tid, $serviceId, $barberId, $startStr, $tz, $barberMode)) {
                    throw new \RuntimeException('BOOKING_SLOT');
                }
                $ap = new AppointmentModel();
                if ($ap->countOverlapping($tid, $barberId, $startStr, $endStr) > 0) {
                    throw new \RuntimeException('BOOKING_OVERLAP');
                }
                $price = (float) $svc['price'];
                $public = Str::randomToken(32);
                $review = Str::randomToken(32);
                $code = Str::confirmationCode();
                $id = $ap->create($tid, [
                    'client_id' => $resolvedClientId,
                    'barber_id' => $barberId,
                    'service_id' => $serviceId,
                    'booked_by_user_id' => null,
                    'start_datetime' => $startStr,
                    'end_datetime' => $endStr,
                    'status' => AppointmentStatus::Pending->value,
                    'price' => $price,
                    'discount' => 0,
                    'notes' => $apptNotes,
                    'payment_method' => $payMethodKey,
                    'payment_note' => $payNoteRaw !== '' ? $payNoteRaw : null,
                    'public_token' => $public,
                    'confirmation_code' => $code,
                    'review_token' => $review,
                ]);
                $ap->addHistory($id, $tid, null, AppointmentStatus::Pending->value, null, $portal !== null ? 'Agendamento portal cliente' : 'Agendamento público');

                return $id;
            });
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if ($msg === 'BOOKING_SLOT') {
                return $this->bookingRedirectWithError($slug, 'Este horário não está mais disponível. Escolha outro.');
            }
            if ($msg === 'BOOKING_OVERLAP') {
                return $this->bookingRedirectWithError($slug, 'Este horário acabou de ser reservado. Escolha outro.');
            }
            error_log('PublicBookingController::book transaction failed: ' . $msg);

            return $this->bookingRedirectWithError($slug, 'Não foi possível concluir o agendamento. Tente novamente.');
        } finally {
            if ($lockHeld) {
                MysqlNamedLock::release($pdo, $lockKey);
            }
        }

        $apRow = (new AppointmentModel())->find($tid, $appointmentId);
        $public = is_array($apRow) ? (string) $apRow['public_token'] : '';

        $cfg = require $this->app->root() . '/config/mail.php';
        $mail = new MailService($cfg);
        $base = rtrim((string) ($this->app->config()['url'] ?? ''), '/');
        $cancel = $base . '/agendar/cancelar?token=' . urlencode($public);
        $confirm = $base . '/agendar/' . rawurlencode($slug) . '/confirmar?token=' . urlencode($public);
        $reviewLink = $base . '/avaliar?token=' . urlencode(is_array($apRow) ? (string) $apRow['review_token'] : '');
        $html = '<p>Olá ' . e($name) . ',</p><p>Seu agendamento na ' . e((string) $tenant['name']) . ' está pendente de confirmação.</p>'
            . '<p><strong>Código de confirmação:</strong> ' . e(is_array($apRow) ? (string) $apRow['confirmation_code'] : '') . '</p>'
            . '<p>Data/hora: ' . e(format_datetime_in_tenant_tz($startStr, $tz)) . '</p>';
        if ($apptNotes !== null && $apptNotes !== '') {
            $html .= '<p><strong>Observações do agendamento:</strong><br>' . nl2br(e($apptNotes)) . '</p>';
        }
        $html .= '<p><a href="' . e($confirm) . '">Confirmar agendamento</a></p>'
            . '<p><a href="' . e($cancel) . '">Cancelar agendamento</a></p>'
            . '<p>Após o atendimento, avalie: <a href="' . e($reviewLink) . '">' . e($reviewLink) . '</a></p>';
        if ($email !== '') {
            try {
                $mail->send($email, $name, 'Confirmação de agendamento', $html);
            } catch (Throwable $e) {
                error_log('MailService booking confirmation failed: ' . $e->getMessage());
            }
        }

        return Response::redirect('/agendar/' . rawurlencode($slug) . '/obrigado?token=' . urlencode($public));
    }

    public function thanks(string $slug): Response
    {
        $token = (string) ($this->request->query()['token'] ?? '');
        $row = $token !== '' ? (new AppointmentModel())->findByPublicTokenWithDetails($token) : null;
        $tenant = $this->tenantFromSlug($slug);
        if ($tenant === null && $row !== null) {
            $tenant = (new TenantModel())->findById((int) $row['tenant_id']);
        }

        return $this->view('public/thanks', [
            'title' => 'Agendamento recebido',
            'appointment' => $row,
            'slug' => $slug,
            'tenant' => $tenant,
        ]);
    }

    public function confirmForm(string $slug): Response
    {
        $tenant = $this->tenantFromSlug($slug);
        if ($tenant === null) {
            return Response::html('<!DOCTYPE html><html><body><p>Barbearia não encontrada.</p></body></html>', 404);
        }
        $token = (string) ($this->request->query()['token'] ?? '');

        return $this->view('public/confirm', [
            'title' => 'Confirmar agendamento',
            'tenant' => $tenant,
            'slug' => $slug,
            'token' => $token,
            'csrf' => Csrf::token(),
        ]);
    }

    public function confirm(string $slug): Response
    {
        $tenant = $this->tenantFromSlug($slug);
        if ($tenant === null) {
            return Response::redirect('/');
        }
        $tid = (int) $tenant['id'];
        $token = (string) $this->request->input('token');
        $code = trim((string) $this->request->input('code'));
        $ap = new AppointmentModel();
        $row = $ap->findByPublicToken($token);
        if ($row === null || (int) $row['tenant_id'] !== $tid) {
            return Response::redirect('/agendar/' . rawurlencode($slug) . '/confirmar?token=' . urlencode($token));
        }
        $from = (string) $row['status'];
        if ($ap->confirmIfPending($tid, (int) $row['id'], $code)) {
            $ap->addHistory((int) $row['id'], $tid, $from, AppointmentStatus::Confirmed->value, null, 'Confirmado pelo cliente');
        }

        return Response::redirect('/agendar/' . rawurlencode($slug) . '/obrigado?token=' . urlencode($token));
    }

    public function cancelForm(): Response
    {
        $token = (string) ($this->request->query()['token'] ?? '');

        return $this->view('public/cancel', [
            'title' => 'Cancelar agendamento',
            'token' => $token,
            'csrf' => Csrf::token(),
        ]);
    }

    public function cancel(): Response
    {
        $token = (string) $this->request->input('token');
        $ap = new AppointmentModel();
        $row = $ap->findByPublicToken($token);
        if ($row === null) {
            return Response::redirect('/agendar/cancelar');
        }
        $tid = (int) $row['tenant_id'];
        $id = (int) $row['id'];
        $from = (string) $row['status'];
        $ap->updateStatus($tid, $id, AppointmentStatus::Cancelled->value, 'Cancelado pelo cliente');
        $ap->addHistory($id, $tid, $from, AppointmentStatus::Cancelled->value, null, 'Portal público');

        return $this->view('public/cancel_done', ['title' => 'Cancelado']);
    }

    public function reviewForm(): Response
    {
        $token = (string) ($this->request->query()['token'] ?? '');
        $row = $token !== '' ? (new AppointmentModel())->findByReviewToken($token) : null;
        if ($row !== null && (string) $row['status'] !== 'completed') {
            $row = null;
        }

        $tenant = null;
        if ($row !== null) {
            $tenant = (new TenantModel())->findById((int) $row['tenant_id']);
        }

        return $this->view('public/review', [
            'title' => 'Avaliar atendimento',
            'appointment' => $row,
            'token' => $token,
            'csrf' => Csrf::token(),
            'tenant' => $tenant,
        ]);
    }

    public function review(): Response
    {
        $token = (string) $this->request->input('token');
        $ap = new AppointmentModel();
        $row = $ap->findByReviewToken($token);
        if ($row === null) {
            return Response::redirect('/avaliar');
        }
        $tid = (int) $row['tenant_id'];
        $id = (int) $row['id'];
        $reviews = new \App\Models\ReviewModel();
        if ($reviews->findByAppointment($tid, $id) !== null) {
            return $this->view('public/review_done', ['title' => 'Já avaliado']);
        }
        $rating = (int) $this->request->input('rating');
        $rating = max(1, min(5, $rating));
        $reviews->create($tid, $id, (int) $row['client_id'], $rating, trim((string) $this->request->input('comment')), true);

        return $this->view('public/review_done', ['title' => 'Obrigado']);
    }
}
