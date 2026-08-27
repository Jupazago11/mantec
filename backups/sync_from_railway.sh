#!/usr/bin/env bash
set -euo pipefail
cd ~/Documentos/mantecv1/mantec

STAMP="$(date +%Y-%m-%d)"
LOCAL_BACKUP="backups/local_pre_railway_sync_${STAMP}.dump"
RAILWAY_CLEAN="backups/railway_mantec_${STAMP}_clean.sql"

echo "== Paso 1: backup local previo =="
PGPASSWORD='123456' pg_dump -h 127.0.0.1 -p 5432 -U mantec_user -d mantec -F c -f "$LOCAL_BACKUP"
echo "Backup local creado: $LOCAL_BACKUP ($(du -h "$LOCAL_BACKUP" | cut -f1))"

echo "== Paso 2: obtener credenciales frescas de Railway Postgres =="
VARS_JSON="$(~/.local/bin/railway variables --project da43ca49-f6fa-45d3-9c47-876dd467437b --service Postgres --environment production --json)"
RW_HOST="$(echo "$VARS_JSON" | jq -r '.RAILWAY_TCP_PROXY_DOMAIN')"
RW_PORT="$(echo "$VARS_JSON" | jq -r '.RAILWAY_TCP_PROXY_PORT')"
RW_USER="$(echo "$VARS_JSON" | jq -r '.PGUSER')"
RW_DB="$(echo "$VARS_JSON" | jq -r '.PGDATABASE')"
RW_PASS="$(echo "$VARS_JSON" | jq -r '.POSTGRES_PASSWORD // .PGPASSWORD')"

if [ -z "$RW_PASS" ] || [ "$RW_PASS" = "null" ]; then
  echo "ERROR: no se pudo obtener el password de Railway Postgres" >&2
  exit 1
fi
echo "Host Railway: $RW_HOST:$RW_PORT (db=$RW_DB user=$RW_USER)"

echo "== Paso 3: dump limpio de Railway =="
PGPASSWORD="$RW_PASS" pg_dump -h "$RW_HOST" -p "$RW_PORT" -U "$RW_USER" -d "$RW_DB" -Fp --clean --if-exists --no-owner --no-privileges > "$RAILWAY_CLEAN"
sed -i '/^SET transaction_timeout = 0;$/d' "$RAILWAY_CLEAN"
echo "Dump Railway creado: $RAILWAY_CLEAN ($(du -h "$RAILWAY_CLEAN" | cut -f1))"

echo "== Paso 4: restaurar en local =="
PGPASSWORD='123456' psql -v ON_ERROR_STOP=1 -h 127.0.0.1 -p 5432 -U mantec_user -d mantec -f "$RAILWAY_CLEAN" > backups/restore_${STAMP}.log 2>&1 || {
  echo "ERROR durante la restauracion, ver backups/restore_${STAMP}.log" >&2
  tail -n 40 backups/restore_${STAMP}.log
  exit 1
}
echo "Restauracion completada. Ultimas lineas del log:"
tail -n 10 "backups/restore_${STAMP}.log"

echo "== Paso 5: validacion rapida =="
PGPASSWORD='123456' psql -h 127.0.0.1 -p 5432 -U mantec_user -d mantec -Atc "select 'report_details=' || count(*) from report_details; select 'users=' || count(*) from users; select 'report_detail_files=' || count(*) from report_detail_files; select 'reports=' || count(*) from reports;"

echo "== LISTO =="
