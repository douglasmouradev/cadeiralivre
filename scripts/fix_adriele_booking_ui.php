<?php

declare(strict_types=1);

/**
 * Corrige card do profissional Adriele no agendamento (logo + especialidades no banco).
 * Uso: php scripts/fix_adriele_booking_ui.php
 */

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';
require $root . '/config/load_env.php';

if (!app_load_dotenv($root)) {
    fwrite(STDERR, "Nenhum .env encontrado.\n");
    exit(1);
}

use App\Models\BarberModel;
use App\Models\TenantModel;
use App\Models\UserModel;

const SLUG = 'adriele-cardoso-nail-design';
const PRO_EMAIL = 'profissional@adrielecardoso.com.br';
const LOGO_SOURCE = 'public/assets/img/brands/adriele-cardoso-logo.png';

$logoPath = $root . '/' . LOGO_SOURCE;
if (!is_readable($logoPath)) {
    fwrite(STDERR, "Logo não encontrada: {$logoPath}\n");
    exit(1);
}

$tenant = (new TenantModel())->findBySlug(SLUG);
if ($tenant === null) {
    fwrite(STDERR, "Loja não encontrada. Rode: php scripts/create_adriele_store.php\n");
    exit(1);
}

$tid = (int) $tenant['id'];
tenant_logo_publish($root, SLUG, $logoPath, 'adriele-cardoso.png');
(new TenantModel())->update($tid, ['logo_path' => 'logos/adriele-cardoso.png']);

$pro = (new UserModel())->findByEmail(PRO_EMAIL);
if ($pro !== null) {
    $avatarDir = $root . '/storage/uploads/avatars';
    if (!is_dir($avatarDir)) {
        mkdir($avatarDir, 0770, true);
    }
    copy($logoPath, $avatarDir . '/adriele-cardoso.png');
    (new UserModel())->setAvatar((int) $pro['id'], 'avatars/adriele-cardoso.png');
}

$barber = $pro !== null ? (new BarberModel())->findByUserId($tid, (int) $pro['id']) : null;
if ($barber !== null) {
    $specs = ['manicure', 'gel', 'nail art', 'spa das mãos'];
    (new BarberModel())->update($tid, (int) $barber['id'], [
        'bio' => 'Especialista em nail design, alongamento em gel e nail art.',
        'specialties' => $specs,
        'commission_percent' => (float) ($barber['commission_percent'] ?? 100),
        'is_available' => (bool) ($barber['is_available'] ?? true),
    ]);
}

$head = trim((string) shell_exec('git -C ' . escapeshellarg($root) . ' log -1 --oneline 2>/dev/null'));

fwrite(STDOUT, "Card do profissional atualizado.\n");
fwrite(STDOUT, "Commit local: " . ($head !== '' ? $head : '(git não disponível)') . "\n");
fwrite(STDOUT, "Logo: " . tenant_logo_url(SLUG) . "\n");
fwrite(STDOUT, "Agendamento: " . rtrim((string) ($_ENV['APP_URL'] ?? ''), '/') . '/agendar/' . SLUG . "\n");
fwrite(STDOUT, "\nReinicie o PHP-FPM e use Ctrl+Shift+R no navegador.\n");
