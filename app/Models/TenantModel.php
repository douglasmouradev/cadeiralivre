<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class TenantModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tenants WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tenants WHERE slug = :s LIMIT 1');
        $stmt->execute(['s' => $slug]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findByBillingSubscriptionId(string $subscriptionId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tenants WHERE billing_subscription_id = :s LIMIT 1');
        $stmt->execute(['s' => $subscriptionId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /** Lista barbearias ativas para o cliente escolher (sem dados sensíveis). */
    /** @return list<array{id: int, name: string, slug: string, city: ?string, state: ?string}> */
    public function listPublicDirectory(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, name, slug, city, state FROM tenants
             WHERE status <> 'suspended'
             ORDER BY name ASC"
        );
        if ($stmt === false) {
            return [];
        }
        $rows = $stmt->fetchAll();
        if (!is_array($rows)) {
            return [];
        }
        /** @var list<array<string, mixed>> $rows */
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'slug' => (string) ($row['slug'] ?? ''),
                'city' => isset($row['city']) && $row['city'] !== null && $row['city'] !== '' ? (string) $row['city'] : null,
                'state' => isset($row['state']) && $row['state'] !== null && $row['state'] !== '' ? (string) $row['state'] : null,
            ];
        }

        return $out;
    }

    /**
     * @param array{name: string, slug: string, email: string, phone: ?string, address: ?string, city: ?string, state: ?string, timezone: string} $data
     */
    public function create(array $data): int
    {
        $planDefId = (new PlanDefinitionModel())->idForSignupDefault();
        $stmt = $this->pdo->prepare(
            'INSERT INTO tenants (name, slug, email, phone, address, city, state, timezone, status, trial_ends_at, plan, plan_definition_id, subscription_status, created_at, updated_at)
             VALUES (:name, :slug, :email, :phone, :address, :city, :state, :timezone, \'trial\', DATE_ADD(NOW(), INTERVAL 14 DAY), \'free\', :plan_def, \'trialing\', NOW(), NOW())'
        );
        $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'email' => mb_strtolower($data['email']),
            'phone' => $data['phone'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'timezone' => $data['timezone'],
            'plan_def' => $planDefId > 0 ? $planDefId : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $fields */
    public function update(int $tenantId, array $fields): void
    {
        $allowed = ['name', 'email', 'phone', 'address', 'city', 'state', 'logo_path', 'cover_path', 'public_tagline', 'instagram_url', 'primary_color', 'timezone', 'webhook_url'];
        $sets = [];
        $params = ['tid' => $tenantId];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $fields)) {
                $sets[] = $col . ' = :' . $col;
                $params[$col] = $fields[$col];
            }
        }
        if ($sets === []) {
            return;
        }
        $sets[] = 'updated_at = NOW()';
        $sql = 'UPDATE tenants SET ' . implode(', ', $sets) . ' WHERE id = :tid';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /** @return list<array<string, mixed>> */
    public function listAllForPlatform(): array
    {
        $sql = 'SELECT t.*, p.name AS plan_label, p.slug AS plan_slug
                FROM tenants t
                LEFT JOIN plan_definitions p ON p.id = t.plan_definition_id
                ORDER BY t.id DESC';
        $stmt = $this->pdo->query($sql);
        if ($stmt === false) {
            return [];
        }

        return $stmt->fetchAll() ?: [];
    }

    public function setStatus(int $tenantId, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE tenants SET status = :s, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['s' => $status, 'id' => $tenantId]);
    }

    /**
     * @param array{
     *   subscription_status?: string,
     *   billing_provider?: ?string,
     *   billing_customer_id?: ?string,
     *   billing_subscription_id?: ?string,
     *   plan?: string,
     *   plan_definition_id?: ?int
     * } $fields
     */
    public function updateBilling(int $tenantId, array $fields): void
    {
        $allowed = ['subscription_status', 'billing_provider', 'billing_customer_id', 'billing_subscription_id', 'plan', 'plan_definition_id'];
        $sets = [];
        $params = ['tid' => $tenantId];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $fields)) {
                $sets[] = $col . ' = :' . $col;
                $params[$col] = $fields[$col];
            }
        }
        if ($sets === []) {
            return;
        }
        $sets[] = 'updated_at = NOW()';
        $sql = 'UPDATE tenants SET ' . implode(', ', $sets) . ' WHERE id = :tid';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function isOnboardingComplete(int $tenantId): bool
    {
        $tenant = $this->findById($tenantId);
        if ($tenant === null) {
            return true;
        }
        $col = $tenant['onboarding_completed_at'] ?? null;

        return is_string($col) && $col !== '';
    }

    public function markOnboardingComplete(int $tenantId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE tenants SET onboarding_completed_at = NOW(), updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $tenantId]);
    }

    /** Dispara webhook HTTP configurado pela loja (fire-and-forget). */
    public function dispatchWebhook(int $tenantId, string $event, array $payload): void
    {
        $tenant = $this->findById($tenantId);
        if ($tenant === null) {
            return;
        }
        $url = trim((string) ($tenant['webhook_url'] ?? ''));
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            return;
        }
        $body = json_encode(['event' => $event, 'tenant_id' => $tenantId, 'data' => $payload], JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            return;
        }
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $body,
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);
        @file_get_contents($url, false, $ctx);
    }

    /** @param array<string, mixed> $appointment */
    public function dispatchAppointmentWebhook(int $tenantId, string $event, array $appointment): void
    {
        $this->dispatchWebhook($tenantId, $event, [
            'appointment_id' => (int) ($appointment['id'] ?? 0),
            'status' => (string) ($appointment['status'] ?? ''),
            'start_datetime' => (string) ($appointment['start_datetime'] ?? ''),
            'end_datetime' => (string) ($appointment['end_datetime'] ?? ''),
            'client_id' => (int) ($appointment['client_id'] ?? 0),
            'barber_id' => (int) ($appointment['barber_id'] ?? 0),
            'service_id' => (int) ($appointment['service_id'] ?? 0),
        ]);
    }
}
