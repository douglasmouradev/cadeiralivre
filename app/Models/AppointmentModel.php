<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class AppointmentModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /** @return array<string, mixed>|null */
    public function findByPublicToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM appointments WHERE public_token = :t LIMIT 1');
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findByPublicTokenWithDetails(string $token): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, s.name AS service_name, u.name AS barber_name
             FROM appointments a
             INNER JOIN services s ON s.id = a.service_id AND s.tenant_id = a.tenant_id
             INNER JOIN barbers b ON b.id = a.barber_id AND b.tenant_id = a.tenant_id
             INNER JOIN users u ON u.id = b.user_id
             WHERE a.public_token = :t LIMIT 1'
        );
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findByReviewToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM appointments WHERE review_token = :t LIMIT 1');
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function find(int $tenantId, int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM appointments WHERE tenant_id = :t AND id = :id LIMIT 1');
        $stmt->execute(['t' => $tenantId, 'id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function confirmIfPending(int $tenantId, int $appointmentId, string $code): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE appointments SET status = 'confirmed', updated_at = NOW()
             WHERE tenant_id = :t AND id = :id AND confirmation_code = :c AND status = 'pending'"
        );
        $stmt->execute(['t' => $tenantId, 'id' => $appointmentId, 'c' => $code]);

        return $stmt->rowCount() > 0;
    }

    /** Pendente → confirmado com cliente autenticado no portal (sem código por e-mail). */
    public function confirmPendingForPortalClient(int $tenantId, int $appointmentId, int $clientId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE appointments SET status = 'confirmed', updated_at = NOW()
             WHERE tenant_id = :t AND id = :id AND client_id = :c AND status = 'pending'"
        );
        $stmt->execute(['t' => $tenantId, 'id' => $appointmentId, 'c' => $clientId]);

        return $stmt->rowCount() > 0;
    }

    public function countOverlapping(
        int $tenantId,
        int $barberId,
        string $start,
        string $end,
        ?int $excludeAppointmentId = null,
    ): int {
        $sql = 'SELECT COUNT(*) FROM appointments WHERE tenant_id = :t AND barber_id = :b
                AND status NOT IN (\'cancelled\', \'no_show\')
                AND start_datetime < :end AND end_datetime > :start';
        $params = ['t' => $tenantId, 'b' => $barberId, 'start' => $start, 'end' => $end];
        if ($excludeAppointmentId !== null) {
            $sql .= ' AND id <> :ex';
            $params['ex'] = $excludeAppointmentId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function create(int $tenantId, array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO appointments (tenant_id, client_id, barber_id, service_id, booked_by_user_id,
             start_datetime, end_datetime, status, price, discount, notes, payment_method, payment_note, public_token, confirmation_code, review_token, created_at, updated_at)
             VALUES (:t, :c, :b, :s, :booker, :st, :en, :status, :price, :disc, :notes, :pm, :pn, :pt, :cc, :rt, NOW(), NOW())'
        );
        $stmt->execute([
            't' => $tenantId,
            'c' => $data['client_id'],
            'b' => $data['barber_id'],
            's' => $data['service_id'],
            'booker' => $data['booked_by_user_id'],
            'st' => $data['start_datetime'],
            'en' => $data['end_datetime'],
            'status' => $data['status'],
            'price' => $data['price'],
            'disc' => $data['discount'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'pm' => $data['payment_method'] ?? null,
            'pn' => $data['payment_note'] ?? null,
            'pt' => $data['public_token'],
            'cc' => $data['confirmation_code'],
            'rt' => $data['review_token'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateStatus(int $tenantId, int $id, string $status, ?string $cancellationReason = null): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE appointments SET status = :st, cancellation_reason = :cr, updated_at = NOW()
             WHERE tenant_id = :t AND id = :id'
        );
        $stmt->execute(['st' => $status, 'cr' => $cancellationReason, 't' => $tenantId, 'id' => $id]);
    }

    public function reschedule(int $tenantId, int $id, string $start, string $end): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE appointments SET start_datetime = :st, end_datetime = :en, updated_at = NOW() WHERE tenant_id = :t AND id = :id'
        );
        $stmt->execute(['st' => $start, 'en' => $end, 't' => $tenantId, 'id' => $id]);
    }

    public function markReminderSent(int $tenantId, int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE appointments SET reminder_sent_at = NOW(), updated_at = NOW() WHERE tenant_id = :t AND id = :id');
        $stmt->execute(['t' => $tenantId, 'id' => $id]);
    }

    public function addHistory(int $appointmentId, int $tenantId, ?string $from, string $to, ?int $userId, ?string $note): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO appointment_status_history (appointment_id, tenant_id, from_status, to_status, changed_by_user_id, note, created_at)
             VALUES (:a, :t, :f, :to, :u, :n, NOW())'
        );
        $stmt->execute([
            'a' => $appointmentId,
            't' => $tenantId,
            'f' => $from,
            'to' => $to,
            'u' => $userId,
            'n' => $note,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function history(int $tenantId, int $appointmentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT h.*, u.name AS user_name FROM appointment_status_history h
             LEFT JOIN users u ON u.id = h.changed_by_user_id
             WHERE h.tenant_id = :t AND h.appointment_id = :a ORDER BY h.created_at ASC'
        );
        $stmt->execute(['t' => $tenantId, 'a' => $appointmentId]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function todayUpcoming(int $tenantId, int $limit = 50): array
    {
        $sql = 'SELECT a.*, c.name AS client_name, s.name AS service_name, u.name AS barber_name
                FROM appointments a
                INNER JOIN clients c ON c.id = a.client_id
                INNER JOIN services s ON s.id = a.service_id
                INNER JOIN barbers b ON b.id = a.barber_id
                INNER JOIN users u ON u.id = b.user_id
                WHERE a.tenant_id = :t
                  AND DATE(a.start_datetime) = CURDATE()
                  AND a.status NOT IN (\'cancelled\', \'no_show\', \'completed\')
                ORDER BY a.start_datetime ASC
                LIMIT :lim';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':t', $tenantId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function forBarberDateRange(int $tenantId, int $barberId, string $startDate, string $endDate): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, c.name AS client_name, s.name AS service_name
             FROM appointments a
             INNER JOIN clients c ON c.id = a.client_id
             INNER JOIN services s ON s.id = a.service_id
             WHERE a.tenant_id = :t AND a.barber_id = :b
               AND a.start_datetime >= :sd AND a.start_datetime < DATE_ADD(:ed, INTERVAL 1 DAY)
             ORDER BY a.start_datetime ASC'
        );
        $stmt->execute(['t' => $tenantId, 'b' => $barberId, 'sd' => $startDate, 'ed' => $endDate]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function forTenantDateRange(int $tenantId, string $startDate, string $endDate): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, c.name AS client_name, s.name AS service_name, u.name AS barber_name
             FROM appointments a
             INNER JOIN clients c ON c.id = a.client_id
             INNER JOIN services s ON s.id = a.service_id
             INNER JOIN barbers b ON b.id = a.barber_id
             INNER JOIN users u ON u.id = b.user_id
             WHERE a.tenant_id = :t
               AND a.start_datetime >= :sd AND a.start_datetime < DATE_ADD(:ed, INTERVAL 1 DAY)
             ORDER BY a.start_datetime ASC'
        );
        $stmt->execute(['t' => $tenantId, 'sd' => $startDate, 'ed' => $endDate]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function forClient(int $tenantId, int $clientId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, s.name AS service_name, u.name AS barber_name
             FROM appointments a
             INNER JOIN services s ON s.id = a.service_id
             INNER JOIN barbers b ON b.id = a.barber_id
             INNER JOIN users u ON u.id = b.user_id
             WHERE a.tenant_id = :t AND a.client_id = :c
             ORDER BY a.start_datetime DESC'
        );
        $stmt->execute(['t' => $tenantId, 'c' => $clientId]);

        return $stmt->fetchAll() ?: [];
    }

    public function countPendingConfirmation(int $tenantId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM appointments WHERE tenant_id = :t AND status = 'pending' AND DATE(start_datetime) >= CURDATE()"
        );
        $stmt->execute(['t' => $tenantId]);

        return (int) $stmt->fetchColumn();
    }

    /** Conta agendamentos não cancelados no mês civil (Y-m). */
    public function countBookedInCalendarMonth(int $tenantId, string $yearMonth): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM appointments
             WHERE tenant_id = :t AND status NOT IN ('cancelled','no_show')
             AND DATE_FORMAT(start_datetime, '%Y-%m') = :ym"
        );
        $stmt->execute(['t' => $tenantId, 'ym' => $yearMonth]);

        return (int) $stmt->fetchColumn();
    }

    /** @return array<string, int|float> */
    public function dashboardStats(int $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                (SELECT COUNT(*) FROM appointments WHERE tenant_id = :t1 AND DATE(start_datetime) = CURDATE() AND status NOT IN ('cancelled','no_show')) AS appts_today,
                (SELECT COALESCE(SUM(p.amount),0) FROM payments p INNER JOIN appointments a ON a.id = p.appointment_id
                    WHERE p.tenant_id = :t2 AND p.status = 'paid' AND YEAR(p.paid_at) = YEAR(CURDATE()) AND MONTH(p.paid_at) = MONTH(CURDATE())) AS revenue_month,
                (SELECT COUNT(*) FROM clients WHERE tenant_id = :t3 AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())) AS new_clients_month,
                (SELECT COALESCE(AVG(r.rating),0) FROM reviews r WHERE r.tenant_id = :t4 AND r.is_public = 1) AS avg_rating"
        );
        $stmt->execute(['t1' => $tenantId, 't2' => $tenantId, 't3' => $tenantId, 't4' => $tenantId]);
        $row = $stmt->fetch();

        return [
            'appts_today' => (int) ($row['appts_today'] ?? 0),
            'revenue_month' => (float) ($row['revenue_month'] ?? 0),
            'new_clients_month' => (int) ($row['new_clients_month'] ?? 0),
            'avg_rating' => round((float) ($row['avg_rating'] ?? 0), 2),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function revenueLast30Days(int $tenantId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE(p.paid_at) AS day, SUM(p.amount) AS total
             FROM payments p
             WHERE p.tenant_id = :t AND p.status = 'paid' AND p.paid_at IS NOT NULL
               AND DATE(p.paid_at) >= CURDATE() - INTERVAL 29 DAY
             GROUP BY DATE(p.paid_at)
             ORDER BY day ASC"
        );
        $stmt->execute(['t' => $tenantId]);
        $byDay = [];
        foreach ($stmt->fetchAll() as $row) {
            $byDay[(string) $row['day']] = (float) $row['total'];
        }

        $out = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = (new \DateTimeImmutable('today -' . $i . ' days'))->format('Y-m-d');
            $out[] = ['day' => $day, 'total' => $byDay[$day] ?? 0.0];
        }

        return $out;
    }
}
