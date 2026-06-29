#!/usr/bin/env bash
# Sincroniza la base de datos local con Railway (producción).
# Uso: bash scripts/sync_db_from_railway.sh

set -euo pipefail

DATE=$(date +%Y-%m-%d)
BACKUP_DIR="backups"

# ─── Credenciales locales ─────────────────────────────────────────────────────
LOCAL_HOST="127.0.0.1"
LOCAL_PORT="5432"
LOCAL_USER="mantec_user"
LOCAL_PASS="123456"
LOCAL_DB="mantec"

# ─── Credenciales Railway ─────────────────────────────────────────────────────
RAILWAY_HOST="hopper.proxy.rlwy.net"
RAILWAY_PORT="31829"
RAILWAY_USER="postgres"
RAILWAY_PASS="BIrZZqsMOGAUFweDBNlaUGmEFPrVGrRu"
RAILWAY_DB="railway"

DUMP_FILE="$BACKUP_DIR/railway_mantec_${DATE}_clean.sql"
LOCAL_BACKUP="$BACKUP_DIR/local_pre_railway_sync_${DATE}.dump"

echo "=== Sync Railway → Local | $DATE ==="

# 1. Respaldar estado actual local
echo "[1/4] Backup local → $LOCAL_BACKUP"
PGPASSWORD="$LOCAL_PASS" pg_dump \
  -h "$LOCAL_HOST" -p "$LOCAL_PORT" \
  -U "$LOCAL_USER" -d "$LOCAL_DB" \
  -F c -f "$LOCAL_BACKUP"

# 2. Bajar dump de Railway (SQL limpio, sin owner, sin permisos)
echo "[2/4] Descargando dump de Railway → $DUMP_FILE"
PGPASSWORD="$RAILWAY_PASS" pg_dump \
  -h "$RAILWAY_HOST" -p "$RAILWAY_PORT" \
  -U "$RAILWAY_USER" -d "$RAILWAY_DB" \
  -Fp --clean --if-exists --no-owner --no-privileges \
  > "$DUMP_FILE"

# 3. Eliminar línea incompatible con PostgreSQL 16 local
echo "[3/4] Limpiando dump (SET transaction_timeout)..."
sed -i '/^SET transaction_timeout = 0;$/d' "$DUMP_FILE"

# 4. Restaurar en local
echo "[4/4] Restaurando en local..."
PGPASSWORD="$LOCAL_PASS" psql \
  -v ON_ERROR_STOP=1 \
  -h "$LOCAL_HOST" -p "$LOCAL_PORT" \
  -U "$LOCAL_USER" -d "$LOCAL_DB" \
  -f "$DUMP_FILE"

# Validación rápida
echo ""
echo "=== Validación ==="
PGPASSWORD="$LOCAL_PASS" psql \
  -h "$LOCAL_HOST" -p "$LOCAL_PORT" \
  -U "$LOCAL_USER" -d "$LOCAL_DB" \
  -Atc "select 'report_details=' || count(*) from report_details;
        select 'users=' || count(*) from users;
        select 'report_detail_files=' || count(*) from report_detail_files;"

echo ""
echo "✓ Sync completado. Backup local guardado en: $LOCAL_BACKUP"
