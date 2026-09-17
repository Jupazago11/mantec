# API Personal — Contrato Para App Android (Supervisores)

Estado del documento: **propuesta de contrato, backend NO implementado
todavia**. Objetivo: que este archivo se pueda compartir tal cual con el
chat/IA que construye la app Android, para que ambos lados (este repo
Laravel y ese repo Android) implementen contra la misma definicion en vez
de inventar cada uno la suya. Mismo patron que ya usa este proyecto con
`Mantec_ins`/`DOCUMENTACION_PROYECTO.md` (ver
[ANALISIS_SISTEMA_LARAVEL.md](ANALISIS_SISTEMA_LARAVEL.md) seccion 4.3).

Contexto de negocio completo: [NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md](NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md),
especialmente seccion 7 (Registro de horas y evidencias) y seccion 14
(Implementacion real — Fases 1, 2a, 2b, 2c).

## 0. Estado Actual Del Backend (leer esto primero)

**Ya existe y esta corriendo en este repo:**

- Guard `personal` (sesion web) + modelo `App\Models\Employee` — ver
  `config/auth.php`, `app/Support/PersonalGuard.php`.
- Tablas `employees`, `companies`, `activities`, `activity_employee`,
  `personal_roles`, `bitacora_entries`, `bitacora_quotas`.
- Programacion, Diario de Campo, Bitacora y CRUD de Empleados/Empresas
  funcionando por **web** (`/personal/*`), con las reglas de primaria
  unica por dia y ventana de edicion ya validadas en codigo real.

**NO existe todavia y este documento lo propone (nada de esto esta
implementado, no asumir que ya funciona):**

- Ningun endpoint bajo `/api/personal/*`.
- `App\Models\Employee` no tiene el trait `Laravel\Sanctum\HasApiTokens`
  (hoy solo `App\Models\User` lo tiene) — sin esto no se pueden emitir
  tokens Sanctum para empleados.
- Ninguna tabla para horas por persona ni evidencia de actividad (ver
  seccion 4 de este documento, propuesta de esquema).
- La columna `activities.reported_hours` existe desde la Fase 2b pero
  hoy queda siempre `null` porque, segun el propio
  `NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md` (seccion 14.2/14.3),
  depende exactamente de esta API que todavia no existe.

Este documento es el punto de partida para que **este repo** construya
esos endpoints, y para que la app Android construya su cliente contra el
mismo contrato. Hasta que ambos lados lo validen, es solo una propuesta.

## 1. Alcance

Basado en la seccion 7 del documento de negocio:

- El supervisor **no crea actividades** desde la app — eso solo pasa en
  Programacion web (`/personal/programacion`).
- El supervisor **consulta** las actividades ya creadas y asignadas a su
  nombre para una fecha.
- El supervisor **reporta** sobre cada actividad: evidencias, comentarios,
  y horas reales trabajadas por cada persona asignada.

Fuera de alcance de este contrato: crear/editar/eliminar actividades,
Bitacora, cierre de Diario de Campo (eso sigue siendo exclusivo de
administrativo/superadmin por web, seccion 14.2 del doc de negocio).

## 2. Autenticacion

Modelo autenticado: `Employee` (no `User`). Mismo patron de
`AuthApiController` (`app/Http/Controllers/Api/AuthApiController.php`),
adaptado al guard `personal`.

**Requisito de backend previo** (pendiente): agregar
`use Laravel\Sanctum\HasApiTokens;` a `app/Models/Employee.php`.

### `POST /api/personal/login`

Request:
```json
{
  "username": "brayan.c",
  "password": "123456"
}
```

Reglas: `Employee::where('username', ...)->where('has_login', true)->where('activo', true)`,
`Hash::check` contra `password`. Mismas condiciones que ya usa
`EmployeeController` y `PersonalAuthController::login()` para el login
web — no se inventa una regla nueva.

Response `200`:
```json
{
  "success": true,
  "token": "1|abcdef...",
  "employee": {
    "id": 12,
    "nombre": "Brayan Cardona",
    "nickname": "Brayan C",
    "categoria": "Administrativos",
    "personal_role": {
      "name": "Supervisor",
      "ver_programacion": true,
      "editar_programacion_sin_limite": false
    }
  }
}
```

Response `401`:
```json
{ "success": false, "message": "Credenciales incorrectas." }
```

**Decision abierta**: hoy cualquier `Employee` con `has_login=true` podria
loguearse aqui, no solo los que tengan rol "Supervisor" (un
administrativo tambien podria, en teoria). ¿Se restringe el login movil a
un permiso especifico (ej. nuevo booleano `acceso_app_movil` en
`personal_roles`) o se deja abierto y simplemente no se les da la
contrasena a los administrativos? Falta decidir con el cliente/usuario.

### `POST /api/personal/logout` (requiere `auth:sanctum`)

Mismo patron que `AuthApiController::logout()`:
```json
{ "success": true, "message": "Sesión cerrada correctamente." }
```

## 3. Mis Actividades Del Dia

### `GET /api/personal/activities?date=YYYY-MM-DD` (requiere `auth:sanctum`)

`date` opcional, default hoy. Filtra `activities` por
`responsible_employee_id = auth()->id()` (el campo "Responsable" de
Programacion — ver seccion 6 del doc de negocio). **Supuesto a confirmar**:
¿el supervisor ve solo las actividades donde el es el responsable, o
tambien las que tiene asignadas como "persona" (tabla `activity_employee`)
sin ser el responsable? El doc de negocio dice "cada supervisor hace este
registro para las distintas actividades asignadas a su nombre" — asumo
"Responsable", pero es una decision que vale la pena confirmar antes de
construir.

Response `200`:
```json
{
  "success": true,
  "date": "2026-09-16",
  "activities": [
    {
      "id": 481,
      "date": "2026-09-16",
      "company": { "id": 3, "name": "ARGOS" },
      "area": "Trituracion",
      "team": "TP1 Trituradora",
      "description": "Realizar cambio de cauchos",
      "activity_type": "P",
      "estimated_hours": 8,
      "shift": "DIURNO",
      "personas": [
        { "id": 12, "nombre": "Brayan Cardona", "nickname": "Brayan C" },
        { "id": 15, "nombre": "Brayan Perez", "nickname": "Brayan P" }
      ],
      "reportada": false
    }
  ]
}
```

`reportada` es un campo derivado propuesto (no existe columna hoy) para
que la app sepa si ya se registro el reporte de esa actividad o sigue
pendiente — se resolveria por ejemplo con `exists()` contra la tabla de
horas propuesta en la seccion 4.

## 4. Registro De Horas Y Evidencias

### `POST /api/personal/activities/{activity}/report` (requiere `auth:sanctum`, `multipart/form-data`)

Reglas de negocio (seccion 7 del doc de negocio, ya confirmadas con el
cliente, sin ambiguedad):

- Si **todos** trabajaron las horas acordadas: un solo flag basta.
- Si no, por persona: checkbox "no trabajo" → horas en `0`; si **si**
  trabajo, se captura **hora de inicio y hora final** (no un numero de
  horas directo) — el backend calcula la duracion.
- La fecha de la Programacion va implicita en `{activity}` (su columna
  `date`), cumpliendo lo confirmado el 2026-09-10 en el doc de negocio.

Request (multipart):
```
all_worked_as_planned: false
persons[0][employee_id]: 12
persons[0][worked]: true
persons[0][hora_inicio]: 07:00
persons[0][hora_fin]: 15:00
persons[1][employee_id]: 15
persons[1][worked]: false
comments: "Se termino antes de lo previsto"
files[]: (evidencia1.jpg)
files[]: (evidencia2.mp4)
```

Response `200`:
```json
{
  "success": true,
  "message": "Reporte guardado correctamente.",
  "activity_id": 481,
  "persons": [
    { "employee_id": 12, "worked": true, "hours": 8.0 },
    { "employee_id": 15, "worked": false, "hours": 0 }
  ],
  "files": [
    { "id": 55, "path": "personal/actividades/481/evidencia/2026-09-16_uuid.jpg", "file_type": "image" }
  ]
}
```

Response `403` (actividad no asignada al empleado autenticado como
responsable):
```json
{ "success": false, "message": "No autorizado sobre esta actividad." }
```

### 4.1 Esquema De Base De Datos Propuesto (pendiente, sin migrar)

Siguiendo el mismo patron ya usado para evidencia de reportes preventivos
(`ReportDetailFile` + `ReportFilePathBuilder`, ver
[ANALISIS_SISTEMA_LARAVEL.md](ANALISIS_SISTEMA_LARAVEL.md) seccion 9):

**Tabla nueva `activity_person_hours`** (horas reales por persona/actividad):
- `id`, `activity_id` FK `activities` cascadeOnDelete, `employee_id` FK
  `employees` cascadeOnDelete, `worked` boolean, `hora_inicio` time
  nullable, `hora_fin` time nullable, `hours` decimal(4,2) nullable
  (calculada al guardar), `timestamps()`. Unique `['activity_id','employee_id']`.

**Tabla nueva `activity_evidences`** (mismo patron que `report_detail_files`):
- `id`, `activity_id` FK `activities` cascadeOnDelete, `uploaded_by_employee_id`
  FK `employees` nullOnDelete, `disk`, `path`, `original_name`,
  `stored_name`, `mime_type`, `extension`, `file_type`, `size_bytes`,
  `timestamps()`, `SoftDeletes` (igual que `ReportDetailFile`, por si se
  necesita borrado logico despues).

**Clase nueva `ActivityFilePathBuilder`** (mismo estilo que
`ReportFilePathBuilder`), path propuesto:
`personal/actividades/{company-slug}-{company_id}/{activity_id}/evidencia/{archivo}`.

**Pregunta abierta sobre `activities.reported_hours`**: hoy es un solo
decimal a nivel de actividad completa (Fase 2b, pensado como total). Si
se construye `activity_person_hours` con el detalle por persona, ¿sigue
existiendo `reported_hours` como el total/suma para no romper Diario de
Campo y Bitacora (que ya lo leen), o se recalcula siempre en vivo desde la
tabla nueva? Recomendacion: mantenerlo como columna cacheada = suma de
`activity_person_hours.hours` de esa actividad, actualizada al guardar el
reporte — evita tocar `FieldDiaryController`/`BitacoraController` que ya
funcionan.

## 5. Sincronizacion Offline

La app Inspector ya existente resuelve reintentos de red con
`/api/inspector/reports/sync` (idempotente). Este modulo es nuevo para
Android (secion 7 del doc de negocio: "se desarrolla en un repositorio
aparte... lo que corresponde construir y documentar desde aqui son las
APIs"), asi que hay que decidir junto con la otra IA:

- ¿El reporte de horas necesita una `idempotency_key`/`client_uuid`
  generada en el dispositivo, para que un reintento de red no cree un
  registro duplicado si el primer intento si llego al servidor pero la
  respuesta se perdio?
- Si el envio incluye evidencia pesada (fotos/video), ¿la app reintenta
  el request completo o sube archivo por archivo? Afecta si `POST
  .../report` debe aceptar guardarse parcialmente (ej. horas sin
  evidencia todavia) o exigir todo junto.

Estas dos preguntas son mejor resueltas conversando directamente con quien
programa el cliente Android (como maneja Room/WorkManager sus reintentos),
no algo que se pueda decidir solo desde el lado Laravel.

## 6. Convenciones Comunes

- Auth: header `Authorization: Bearer {token}` (Sanctum), igual que
  `/api/inspector/*`.
- Fechas: `YYYY-MM-DD`. Horas: `HH:mm` (24h).
- Errores: siempre `{"success": false, "message": "..."}` + codigo HTTP
  (`401` credenciales, `403` autorizacion, `422` validacion, `500` error
  de storage).
- Tamano maximo de archivo: `1048576` KB (1024 MB) — mismo limite que ya
  esta fijado en `php.ini` de este repo y usado por
  `InspectorSyncFileController`, no un numero nuevo.
- Storage: disco `r2` (Cloudflare R2), mismo mecanismo `fopen()` +
  `Storage::disk('r2')->writeStream()` que ya usa el resto del sistema —
  no reinventar el streaming de subida.

## 7. Checklist Para Implementar En Este Repo (antes de que la app lo consuma)

- [ ] Agregar `HasApiTokens` a `app/Models/Employee.php`.
- [ ] Decidir y migrar `activity_person_hours` y `activity_evidences`
      (seccion 4.1), o el esquema alternativo que se acuerde.
- [ ] Controlador `PersonalAuthApiController` (login/logout), mismo
      patron que `AuthApiController`.
- [ ] Controlador `PersonalActivityApiController` (`index`, `report`).
- [ ] Clase `ActivityFilePathBuilder`.
- [ ] Rutas nuevas en `routes/api.php` bajo `Route::prefix('personal')`,
      protegidas por `auth:sanctum`.
- [ ] Feature tests: login rechaza `has_login=false`; `report` rechaza
      actividad no asignada (403); `worked=false` guarda 0 horas;
      evidencia queda en R2; reporte duplicado no crea filas repetidas
      (una vez se resuelva la seccion 5).
- [ ] Actualizar [ANALISIS_SISTEMA_LARAVEL.md](ANALISIS_SISTEMA_LARAVEL.md)
      (seccion 7.2 y 9) y
      [NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md](NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md)
      (secciones 7 y 11) cuando esto pase de propuesta a implementado.

## 8. Decisiones Abiertas (resolver antes o durante la implementacion)

1. ¿Login movil restringido a un permiso especifico o abierto a cualquier
   `has_login=true`? (seccion 2)
2. ¿"Mis actividades" es por `responsible_employee_id` o tambien incluye
   donde el empleado aparece como persona asignada? (seccion 3)
3. ¿`reported_hours` a nivel actividad se mantiene como cache/suma o se
   elimina en favor de leer siempre `activity_person_hours`? (seccion 4.1)
4. Estrategia de idempotencia/reintento para offline-first (seccion 5) —
   requiere alinear con quien construye el cliente Android.
5. Significado real de si una actividad ya cerrada en Diario de Campo
   (`activities.closed_at`) debe seguir aceptando reportes de horas via
   esta API, o si el cierre administrativo tambien bloquea el reporte
   movil (hoy el cierre bloquea edicion en Programacion web, seccion
   14.4 del doc de negocio, pero no esta definido para esta API nueva).
