# Sincronizar base de datos local desde Railway

Ejecuta el script de un solo comando:

```bash
bash scripts/sync_db_from_railway.sh
```

El script hace automáticamente:

1. **Backup local previo** → `backups/local_pre_railway_sync_FECHA.dump`
2. **Dump de Railway** → `backups/railway_mantec_FECHA_clean.sql` (sin owner, sin permisos)
3. **Limpieza** → elimina `SET transaction_timeout = 0;` (incompatible con PG 16 local)
4. **Restauración** en la base local `mantec`
5. **Validación** rápida de conteos (report_details, users, report_detail_files)

## Requisitos

- PostgreSQL local corriendo: `sudo service postgresql start`
- `pg_dump` y `psql` disponibles en WSL
- Railway: no requiere login; usa conexión directa al proxy público

## Revertir si algo sale mal

```bash
PGPASSWORD='123456' pg_restore \
  -h 127.0.0.1 -p 5432 -U mantec_user -d mantec \
  --clean --if-exists \
  backups/local_pre_railway_sync_FECHA.dump
```

## Credenciales actuales

| Entorno | Host | Puerto | Usuario | DB |
|---------|------|--------|---------|-----|
| Local | 127.0.0.1 | 5432 | mantec_user | mantec |
| Railway | hopper.proxy.rlwy.net | 31829 | postgres | railway |

> Si el proxy de Railway cambia de host/puerto, actualizar en `scripts/sync_db_from_railway.sh`.
