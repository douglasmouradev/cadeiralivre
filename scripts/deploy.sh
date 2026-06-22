#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
echo "==> Deploy CadeiraLivre em $ROOT"
git pull origin main
composer install --no-dev --optimize-autoloader
php scripts/migrate.php
composer dump-autoload -o
if command -v systemctl >/dev/null 2>&1; then
  systemctl reload php8.3-fpm 2>/dev/null || systemctl reload php-fpm 2>/dev/null || true
fi
echo "==> Deploy concluído."
