<?php

declare(strict_types=1);

/**
 * Cria a loja Adriele Cardoso Nail Design (tenant, serviços, profissional e logo).
 * Uso: php scripts/create_adriele_store.php
 */

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';
require $root . '/config/load_env.php';

if (!app_load_dotenv($root)) {
    fwrite(STDERR, "Nenhum .env encontrado.\n");
    exit(1);
}

use App\Core\Database;
use App\Enums\UserRole;
use App\Models\BarberModel;
use App\Models\ServiceModel;
use App\Models\TenantModel;
use App\Models\UserModel;
use App\Models\WorkingHoursModel;

const SLUG = 'adriele-cardoso-nail-design';
const STORE_NAME = 'Adriele Cardoso Nail Design';
const OWNER_EMAIL = 'adriele@adrielecardoso.com.br';
const PRO_EMAIL = 'profissional@adrielecardoso.com.br';
const DEFAULT_PASSWORD = 'Senha1234';
const BRAND_COLOR = '#C4A052';
const LOGO_SOURCE = 'public/assets/img/brands/adriele-cardoso-logo.png';
const LOGO_DEST = 'logos/adriele-cardoso.png';
const AVATAR_BASENAME = 'adriele-cardoso.png';
// Regenerar logo HQ: python scripts/process_adriele_logo.py

function sync_adriele_professional_avatar(string $root, string $logoSourcePath): void
{
    $users = new UserModel();
    $pro = $users->findByEmail(PRO_EMAIL);
    if ($pro === null) {
        return;
    }
    $avatarDir = $root . '/storage/uploads/avatars';
    if (!is_dir($avatarDir) && !mkdir($avatarDir, 0770, true) && !is_dir($avatarDir)) {
        throw new RuntimeException('Não foi possível criar ' . $avatarDir);
    }
    $dest = $avatarDir . '/' . AVATAR_BASENAME;
    if (!copy($logoSourcePath, $dest)) {
        throw new RuntimeException('Não foi possível copiar avatar do profissional.');
    }
    $users->setAvatar((int) $pro['id'], 'avatars/' . AVATAR_BASENAME);
}

$logoSourcePath = $root . '/' . LOGO_SOURCE;
if (!is_readable($logoSourcePath)) {
    fwrite(STDERR, "Logo não encontrada: {$logoSourcePath}\n");
    exit(1);
}

$tenants = new TenantModel();
$existing = $tenants->findBySlug(SLUG);
if ($existing !== null) {
    try {
        tenant_logo_publish($root, SLUG, $logoSourcePath, 'adriele-cardoso.png');
        $tenants->update((int) $existing['id'], ['logo_path' => LOGO_DEST]);
        sync_adriele_professional_avatar($root, $logoSourcePath);
    } catch (Throwable $e) {
        fwrite(STDERR, 'Erro ao sincronizar logo: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    fwrite(STDOUT, "A loja já existe; logo republicada.\n");
    fwrite(STDOUT, "Agendamento: /agendar/" . SLUG . "\n");
    fwrite(STDOUT, "Logo: " . tenant_logo_url(SLUG) . "\n");
    fwrite(STDOUT, "Para garantir acesso admin da Adriele: php scripts/ensure_adriele_admin.php\n");
    exit(0);
}

try {
    tenant_logo_publish($root, SLUG, $logoSourcePath, 'adriele-cardoso.png');
} catch (Throwable $e) {
    fwrite(STDERR, 'Erro ao publicar logo: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$users = new UserModel();
foreach ([OWNER_EMAIL, PRO_EMAIL] as $email) {
    if ($users->findByEmail($email) !== null) {
        fwrite(STDERR, "E-mail já cadastrado: {$email}\n");
        exit(1);
    }
}

$passwordHash = password_hash(DEFAULT_PASSWORD, PASSWORD_BCRYPT);

try {
    Database::transaction(static function () use ($tenants, $users, $passwordHash): void {
        $tenantId = $tenants->create([
        'name' => STORE_NAME,
        'slug' => SLUG,
        'email' => OWNER_EMAIL,
        'phone' => null,
        'address' => null,
        'city' => null,
        'state' => null,
        'timezone' => 'America/Sao_Paulo',
        ]);

        $tenants->update($tenantId, [
            'logo_path' => LOGO_DEST,
            'primary_color' => BRAND_COLOR,
            'city' => 'Salvador',
            'state' => 'BA',
        ]);

        Database::connection()->prepare("UPDATE tenants SET status = 'active', trial_ends_at = NULL WHERE id = :id")
            ->execute(['id' => $tenantId]);

        $users->create(
            $tenantId,
            'Adriele Cardoso',
            OWNER_EMAIL,
            $passwordHash,
            UserRole::Owner->value,
            null,
        );

        $proUserId = $users->create(
            $tenantId,
            'Adriele Cardoso',
            PRO_EMAIL,
            $passwordHash,
            UserRole::Barber->value,
            null,
        );

        $barbers = new BarberModel();
        $barberId = $barbers->create($tenantId, $proUserId, [
            'bio' => 'Especialista em nail design, alongamento em gel e nail art.',
            'specialties' => ['manicure', 'gel', 'nail art', 'spa das mãos'],
            'commission_percent' => 100.0,
            'is_available' => true,
        ]);

        $services = new ServiceModel();
        $catalog = [
            ['Manicure tradicional', 'Cutícula, lixamento e esmaltação.', 45, 45.00, 'Manicure', 0],
            ['Alongamento em gel', 'Aplicação completa com gel builder.', 120, 150.00, 'Alongamento', 1],
            ['Manutenção de alongamento', 'Manutenção periódica do alongamento.', 90, 90.00, 'Alongamento', 2],
            ['Esmaltação em gel', 'Esmaltação com acabamento em gel.', 45, 50.00, 'Manicure', 3],
            ['Nail art', 'Arte personalizada nas unhas.', 30, 35.00, 'Nail art', 4],
            ['Spa das mãos', 'Hidratação profunda e massagem relaxante.', 60, 55.00, 'Spa', 5],
            ['Pedicure spa', 'Cuidados completos para os pés.', 60, 65.00, 'Pedicure', 6],
        ];

        $serviceIds = [];
        foreach ($catalog as [$name, $desc, $minutes, $price, $category, $order]) {
            $serviceIds[] = $services->create($tenantId, [
                'name' => $name,
                'description' => $desc,
                'duration_minutes' => $minutes,
                'price' => $price,
                'category' => $category,
                'is_active' => true,
                'display_order' => $order,
            ]);
        }

        $barbers->syncServices($tenantId, $barberId, $serviceIds, []);

        $week = [];
        for ($dow = 0; $dow <= 6; $dow++) {
            $week[] = [
                'day_of_week' => $dow,
                'start_time' => '09:00:00',
                'end_time' => $dow === 0 ? '00:00:00' : ($dow === 6 ? '14:00:00' : '18:00:00'),
                'is_day_off' => $dow === 0,
            ];
        }
        (new WorkingHoursModel())->replaceWeek($tenantId, $barberId, $week);

        sync_adriele_professional_avatar($root, $logoSourcePath);
    });
} catch (Throwable $e) {
    fwrite(STDERR, 'Erro ao criar loja: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$base = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');

fwrite(STDOUT, "Loja criada com sucesso!\n\n");
fwrite(STDOUT, "Nome: " . STORE_NAME . "\n");
fwrite(STDOUT, "Slug: " . SLUG . "\n");
fwrite(STDOUT, "Agendamento público: {$base}/agendar/" . SLUG . "\n");
fwrite(STDOUT, "Portal cliente: {$base}/cliente/" . SLUG . "/entrar\n\n");
fwrite(STDOUT, "Painel (dona): " . OWNER_EMAIL . " / " . DEFAULT_PASSWORD . "\n");
fwrite(STDOUT, "Agenda (profissional): " . PRO_EMAIL . " / " . DEFAULT_PASSWORD . "\n");
fwrite(STDOUT, "\nAltere as senhas após o primeiro acesso.\n");
