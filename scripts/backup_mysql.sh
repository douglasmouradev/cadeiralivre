#!/usr/bin/env bash
# Backup MySQL usando variáveis do .env (requer mysql-client no PATH).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
if [[ ! -f .env ]]; then
  echo "Falta .env em $ROOT" >&2
  exit 1
fi
# shellcheck disable=SC1091
set -a
source .env
set +a
TS="$(date +%Y%m%d_%H%M%S)"
OUT="${ROOT}/storage/backups/mysql_${DB_DATABASE:-cadeira_livre_saas}_${TS}.sql.gz"
mkdir -p "${ROOT}/storage/backups"
export MYSQL_PWD="${DB_PASSWORD:-}"
mysqldump -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "${DB_USERNAME:-root}" \
  --single-transaction --quick --routines \
  "${DB_DATABASE:-cadeira_livre_saas}" | gzip > "$OUT"
echo "Backup: $OUT"
