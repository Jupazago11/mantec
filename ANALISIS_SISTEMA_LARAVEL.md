# Analisis Del Sistema Laravel - Mantec

Ultima actualizacion: 2026-09-17  
Estado del documento: canonico y de mantenimiento continuo

## 1. Proposito

Mantec es una plataforma Laravel para operacion de mantenimiento preventivo industrial. El sistema cubre:

- Administracion multiempresa por clientes, agrupaciones, areas y activos.
- Registro de inspecciones preventivas desde web y desde app Android.
- Consulta de reportes preventivos por grupo y por tipo de activo.
- Gestion de evidencias multimedia en Cloudflare R2.
- Modulo tecnico de mediciones.
- Modulo tecnico de eventos de banda.
- Dashboard de indicadores y semaforos.

No es un multi-tenant aislado por base de datos. Es un esquema compartido con autorizacion por cliente, grupo, area, tipo de activo y rol.

## 2. Stack Real

- Backend: Laravel 13, PHP 8.3.
- Base de datos: PostgreSQL.
- Auth web: sesion Laravel.
- Auth movil/API: Laravel Sanctum.
- Frontend: Blade + Tailwind CSS 4 + Alpine.js por CDN.
- Build frontend: Vite 8.
- Excel: maatwebsite/excel.
- Storage de archivos: Cloudflare R2 usando league/flysystem-aws-s3-v3.
- Entorno local habitual: WSL + PostgreSQL local del host + contenedores Sail parciales para servicios auxiliares.

Archivos base de infraestructura:

- [composer.json](/home/jupazago/Documentos/mantecv1/mantec/composer.json)
- [package.json](/home/jupazago/Documentos/mantecv1/mantec/package.json)
- [vite.config.js](/home/jupazago/Documentos/mantecv1/mantec/vite.config.js)
- [ARRANCAR_LOCAL.txt](/home/jupazago/Documentos/mantecv1/mantec/ARRANCAR_LOCAL.txt)

## 3. Dimension Actual Del Proyecto

Inventario observado en codigo el 2026-06-19:

- 37 modelos Eloquent.
- 41 controladores.
- 62 migraciones.
- 70 vistas Blade.

Capas principales:

- app/Http/Controllers/Admin: panel administrativo y operativo.
- app/Http/Controllers/Inspector: flujo web del inspector.
- app/Http/Controllers/Api: endpoints para la app Android.
- app/Models: dominio de negocio.
- app/Services: logica reusable de ejecucion, semaforo y configuracion de columnas.
- app/Support: helpers de soporte, especialmente rutas de archivos.
- resources/views: panel admin, inspector, dashboard, reportes, modulos tecnicos.

## 4. Arquitectura Funcional

El sistema hoy se entiende mejor en 5 bloques:

### 4.1 Nucleo Maestro

Catalogos y relaciones de operacion:

- Clientes
- Areas
- Tipos de activo
- Activos
- Componentes
- Diagnosticos
- Condiciones
- Agrupaciones
- Usuarios y permisos

Estos catalogos determinan que puede inspeccionarse y por quien.

### 4.2 Reportes Preventivos

La unidad operativa real es report_details, no reports.

ReportDetail concentra:

- inspector (user_id)
- activo (element_id)
- componente
- diagnostico
- condicion
- semana y anio
- hallazgo (recommendation)
- recomendacion/correctivo (recommendation_2)
- orden
- aviso
- estado de ejecucion
- fecha de ejecucion
- evidencia multimedia

Conclusiones clave:

- Buena parte del sistema trabaja directamente sobre report_details.
- La tabla reports existe, pero el valor operativo y analitico fuerte esta en el detalle.
- El frontend administrativo de reportes, filtros, evidencia y exportaciones se apoya sobre ReportDetail.

### 4.3 App Android / Inspector

Codigo fuente y documentacion detallada (arquitectura MVVM, Room, sync
offline-first, DTOs, riesgos de seguridad, historial de versiones):
https://github.com/Jupazago11/Mantec_ins (ver `DOCUMENTACION_PROYECTO.md`
en ese repo). Verificado el 2026-09-05 contra la version 1.7.6
(versionCode 8) de la app: el streaming de subida de evidencia
(`fopen()` + `Storage::disk('r2')->writeStream()`) y el limite de
`max:1048576` KB en `InspectorSyncFileController` y
`AdminReportEvidenceController` coinciden con lo que la app espera.

La app movil consume API Sanctum y soporta:

- login
- catalogo offline
- sincronizacion de reportes preventivos
- carga de archivos de evidencia
- consulta y sincronizacion de mediciones

El backend valida pertenencia real entre cliente, area, activo, componente, diagnostico y condicion antes de persistir.

### 4.4 Modulos Tecnicos

Hoy hay al menos 3 lineas funcionales distintas:

- Preventivo clasico por inspeccion.
- Mediciones de espesor y estado de banda.
- Eventos de banda con drafts, publicacion y evidencia.

### 4.5 Dashboard e Indicadores

Existen dos ejes:

- Dashboard de acceso a reportes preventivos por agrupacion.
- Dashboard de indicadores con cobertura, distribuciones y semaforo configurable.

## 5. Modelos Mas Importantes

### 5.1 Jerarquia Operativa

- Client
- Group
- Area
- ElementType
- Element
- Component
- Diagnostic
- Condition

Relaciones relevantes:

- Un cliente tiene areas, tipos de activo y grupos.
- Un area pertenece a un cliente.
- Un activo pertenece a un area, a una agrupacion y a un tipo de activo.
- Un activo se relaciona con componentes por tabla pivote element_components.
- Un componente se relaciona con diagnosticos.
- Las condiciones dependen del cliente y del tipo de activo.

### 5.2 Seguridad y Alcance

- User
- Role
- RoleModulePermission
- UserClientGroupArea
- pivotes client_user, group_user, user_client_element_type, user_client_element_type_areas

El modelo User ya contiene helpers de alcance:

- clients()
- allowedElementTypes()
- allowedAreas()
- groups()
- allowedGroupAreas()
- canManageSystemModule()
- canViewSystemModule()
- canCreateInSystemModule()
- hasEnabledModuleForClientAndElementType()

### 5.3 Reporteria Preventiva

- Report
- ReportDetail
- ReportDetailFile
- ExecutionStatus

Detalles relevantes:

- ReportDetail autocompleta execution_status_id en saving() usando ExecutionStatusResolver.
- Si la condicion es OK, la fecha de ejecucion queda en null.
- ReportDetailFile usa SoftDeletes.
- ReportDetailFile maneja dos tipos de evidencia: hallazgo y correccion.

### 5.4 Mediciones

- MeasurementThicknessDraft
- MeasurementThicknessDraftLine
- MeasurementThicknessReport
- MeasurementThicknessReportLine
- BandStateDraft
- BandStateReport

### 5.5 Eventos De Banda

- BandEvent
- BandEventDraft
- BandEventEvidence
- BandEventDraftEvidence

Este modulo tiene drafts, publicacion, evidencia y mantenimiento de historico tecnico.

### 5.6 Indicadores Y Semaforos

- SemaphoreTemplate
- SemaphoreTemplateColumn
- SemaphoreTemplateColumnRule
- SemaphoreBeltChange
- GroupReportConfig
- GroupReportConfigColumn
- Parada

## 6. Roles y Permisos

Roles observados en codigo:

- superadmin
- admin_global
- admin
- admin_cliente
- observador
- observador_cliente
- inspector

Comportamiento general:

- superadmin y admin_global: acceso amplio global.
- admin: acceso administrativo operativo sobre clientes asignados.
- admin_cliente: acceso acotado al cliente y con permisos selectivos de edicion.
- observador y observador_cliente: solo lectura o edicion muy restringida.
- inspector: foco en captura operativa y sincronizacion movil.

Puntos importantes ya implementados:

- El dashboard administrativo permite acceso a superadmin, admin_global, admin, admin_cliente, observador, observador_cliente.
- El acceso a modulos del sistema se resuelve por rol y por RoleModulePermission.
- El modulo mediciones tiene excepciones explicitas de visibilidad en User.

## 7. Rutas Reales Del Sistema

### 7.1 Web

Archivo: [routes/web.php](/home/jupazago/Documentos/mantecv1/mantec/routes/web.php)

#### Publicas

- / redirige a login.
- /login y POST /login.
- Existen dos rutas de debug publicas:
  - /test-r2
  - /php-upload-check

Estas dos rutas deben considerarse tecnicas y potencialmente sensibles para despliegues mas estrictos.

#### Admin autenticado

Bloques reales:

- Dashboard.
- Indicadores.
- Clientes.
- Usuarios gestionados.
- Areas.
- Tipos de activo.
- Diagnosticos.
- Condiciones.
- Componentes.
- Componente-diagnostico.
- Activos.
- Agrupaciones.
- Configuracion de columnas de reporte por agrupacion.
- Paradas.
- Pendientes.
- Plantillas de semaforo.
- Reportes preventivos.
- Evidencias del reporte.
- Configuracion de modulos por cliente/tipo de activo.
- Mediciones.
- AJAX operativo para filtros y updates.

#### Inspector web autenticado

Bloque /inspector:

- formulario y flujo de reporte web
- consultas de grupos, areas, activos, componentes, condiciones y diagnosticos
- estado semanal y pendientes

#### Eventos de banda

Bloque /band-events:

- crear draft
- actualizar draft
- publicar draft
- cargar/eliminar/abrir evidencia de draft
- cargar/eliminar/abrir evidencia de reporte publicado
- actualizar y eliminar reportes

### 7.2 API

Archivo: [routes/api.php](/home/jupazago/Documentos/mantecv1/mantec/routes/api.php)

#### Publica

- POST /api/login

#### Protegida con Sanctum

- POST /api/logout
- /api/inspector/offline-catalog
- endpoints de catalogo por cliente/area/activo/componente/condicion/diagnostico
- POST /api/inspector/reports
- POST /api/inspector/reports/sync
- POST /api/inspector/report-details/{reportDetail}/files
- endpoints de mediciones
- endpoints de estado semanal

Conclusion: cualquier cambio en estructuras de preventivo o evidencia debe evaluarse tambien en API antes de tocar modelos o almacenamiento.

## 8. Flujo Preventivo Real

### 8.1 Captura

Un inspector genera o sincroniza un ReportDetail sobre:

- activo
- componente
- diagnostico
- condicion
- semana/anio

### 8.1.1 Fusion de reportes duplicados el mismo dia (2026-09-17)

Cuando el mismo activo+componente+diagnostico se reporta mas de una vez
dentro de 24 horas, el sistema no crea un ReportDetail independiente:
actualiza el ultimo reporte vigente (`status=true`), sin importar si el
segundo reporte lo hizo un inspector distinto. Este criterio ya existia
solo en el flujo web y ahora es identico en los dos canales:

- Web: `InspectorReportController::store`.
- API/Android (sync offline-first): `InspectorSyncController::store`.

Logica compartida en dos servicios nuevos:

- [app/Services/Reports/ReportDetailMerger.php](/home/jupazago/Documentos/mantecv1/mantec/app/Services/Reports/ReportDetailMerger.php):
  busca el reporte fusionable (misma terna, `status=true`, `created_at`
  dentro de las ultimas 24h, con `lockForUpdate()` para evitar condicion de
  carrera si dos inspectores sincronizan casi al mismo tiempo) y construye
  el texto de trazabilidad.
- [app/Services/Access/ElementAccessService.php](/home/jupazago/Documentos/mantecv1/mantec/app/Services/Access/ElementAccessService.php):
  valida que el inspector autenticado tenga acceso real (cliente + grupo)
  al activo del elemento, reutilizado por `InspectorSyncController` y
  `InspectorSyncFileController`.

Trazabilidad del hallazgo: si quien complementa el reporte es un inspector
distinto al que lo creo, el hallazgo nuevo se agrega debajo del existente
con formato `Nombre — dd/mm/aaaa HH:mm:` seguido del texto, sin limite de
cuantos inspectores se acumulen. Si es el mismo inspector complementando su
propio reporte, se agrega sin ese prefijo (igual que antes). La condicion,
`execution_status` y `execution_date` siempre quedan con el valor de la
ultima evaluacion. En el canal API, `orden`/`aviso` se mantienen sin tocar
(igual que antes); en el canal web se siguen heredando del reporte anterior
a ese, sin cambios de comportamiento ahi.

Correccion de seguridad relacionada: `InspectorSyncController::store` no
validaba que el inspector autenticado tuviera acceso real al
cliente/grupo/activo reportado (solo validaba consistencia entre
area/elemento/componente/diagnostico/condicion). Ahora usa
`ElementAccessService` igual que el flujo web. El catalogo offline
(`InspectorOfflineCatalogController`) ya solo entrega elementos dentro del
alcance del inspector, por lo que en uso normal esta validacion no cambia
nada visible; solo bloquea sincronizar un `element_id` fuera de su alcance
actual (por ejemplo, si se le revoco el grupo entre la descarga del
catalogo offline y la sincronizacion de un reporte capturado sin conexion).

Evidencia multimedia: `InspectorSyncFileController::store` ya no exige que
el inspector sea el creador original del `ReportDetail` (`user_id` igual al
autenticado); ahora exige que tenga acceso real al activo, usando el mismo
`ElementAccessService`. Esto permite que, tras una fusion, el segundo o
tercer inspector tambien pueda subir sus propias fotos/videos al mismo
reporte. Cada archivo ya guardaba `uploaded_by` (no fue necesaria
migracion); la vista de evidencia admin
(`resources/views/admin/preventive-reports/evidence.blade.php`) ahora
muestra "Subido por {nombre}" por archivo para poder identificar de cual
inspector es cada evidencia cuando el reporte tiene aportes de mas de uno.

Cobertura: [tests/Feature/Api/InspectorSyncReportMergeTest.php](/home/jupazago/Documentos/mantecv1/mantec/tests/Feature/Api/InspectorSyncReportMergeTest.php),
[tests/Feature/Api/InspectorSyncFileAccessTest.php](/home/jupazago/Documentos/mantecv1/mantec/tests/Feature/Api/InspectorSyncFileAccessTest.php),
[tests/Feature/Inspector/InspectorReportMergeTraceabilityTest.php](/home/jupazago/Documentos/mantecv1/mantec/tests/Feature/Inspector/InspectorReportMergeTraceabilityTest.php),
[tests/Feature/Admin/ReportEvidenceUploaderTest.php](/home/jupazago/Documentos/mantecv1/mantec/tests/Feature/Admin/ReportEvidenceUploaderTest.php).

### 8.2 Estado de ejecucion

ExecutionStatusResolver determina:

- estado pendiente
- estado realizado/finalizado
- estado OK

Regla importante:

- Si la condicion es OK, el sistema considera que no hay fecha de ejecucion aplicable.
- Si se filtra por fecha de ejecucion, solo deben contemplarse registros con estado visible de ejecucion realizada.

Esto es importante porque ya hubo ajustes recientes en filtros para no mezclar fechas persistidas con estados no visibles.

### 8.3 Reportes administrativos

Hay dos grandes visualizaciones:

- reporte preventivo general por cliente
- reporte preventivo por agrupacion

El reporte por agrupacion usa GroupReportConfigService para:

- definir columnas visibles
- reordenar columnas
- controlar editabilidad por rol

Columnas estructurales siempre visibles:

- area
- element_name
- week

## 9. Subsistema De Evidencias

Archivos principales:

- [app/Http/Controllers/Admin/AdminReportEvidenceController.php](/home/jupazago/Documentos/mantecv1/mantec/app/Http/Controllers/Admin/AdminReportEvidenceController.php)
- [app/Models/ReportDetailFile.php](/home/jupazago/Documentos/mantecv1/mantec/app/Models/ReportDetailFile.php)
- [app/Support/ReportFilePathBuilder.php](/home/jupazago/Documentos/mantecv1/mantec/app/Support/ReportFilePathBuilder.php)
- [resources/views/admin/preventive-reports/evidence.blade.php](/home/jupazago/Documentos/mantecv1/mantec/resources/views/admin/preventive-reports/evidence.blade.php)

Estado actual:

- El sistema ya maneja evidencia separada por hallazgo y correccion.
- Los archivos se suben a R2.
- La metadata se guarda en report_detail_files.
- La eliminacion actual es logica por SoftDeletes.
- Tambien se registra detached_by.

Permisos actuales segun backend:

- superadmin, admin_global, admin: pueden gestionar evidencia.
- admin_cliente: solo puede gestionar evidencia de correccion.
- admin_cliente no puede gestionar evidencia de hallazgo.

Impacto sobre la app Android:

- El frontend administrativo de evidencia no cambia por si solo la API de sincronizacion movil.
- La API movil sigue cargando archivos al flujo existente.
- Cualquier cambio de contrato solo ocurre si se alteran validaciones, nombres de campos, almacenamiento o relaciones.

### 9.1 Limite De Peso De Archivo (2026-08-03)

Se detecto que produccion (Railway) corria sin ningun `php.ini` cargado
(`php_ini_loaded_file()` devolvia `false`), con `upload_max_filesize=0` y
`post_max_size=0`. `post_max_size=0` esta documentado por PHP como "sin
limite", pero `upload_max_filesize=0` no tiene esa garantia documentada;
las subidas de evidencia funcionaban de forma accidental, no por diseno,
y podian romperse con un simple reinicio del contenedor o cambio de
Nixpacks/Railway sin tocar codigo.

Se agrego [php.ini](/home/jupazago/Documentos/mantecv1/mantec/php.ini) en
la raiz del repo (recogido por el provider PHP de Nixpacks en el build de
Railway) fijando un techo alto pero explicito:

- `upload_max_filesize = 1024M`
- `post_max_size = 1100M`
- `max_execution_time = 300`
- `max_input_time = 300`
- `memory_limit = 1024M`

Nota de verificacion en produccion (post-deploy, `/php-upload-check`):
`upload_max_filesize` y `post_max_size` quedaron correctos, pero al existir
un `php.ini` en el repo, Nixpacks dejo de usar su modo "sin ini" (que antes
dejaba `memory_limit` en `-1`, sin limite) y genero su plantilla completa
por defecto, que trae `memory_limit=128M` de fabrica. Se agrego
`memory_limit=1024M` explicito en el mismo `php.ini` para no perder el
margen que existia antes.

La regla `max:` de Laravel se actualizo en los dos puntos de subida de
evidencia para que coincida (de `102400` a `1048576`, en KB = 1024 MB):

- [app/Http/Controllers/Api/InspectorSyncFileController.php](/home/jupazago/Documentos/mantecv1/mantec/app/Http/Controllers/Api/InspectorSyncFileController.php)
- [app/Http/Controllers/Admin/AdminReportEvidenceController.php](/home/jupazago/Documentos/mantecv1/mantec/app/Http/Controllers/Admin/AdminReportEvidenceController.php)

Pendiente: confirmar en `/php-upload-check` despues del proximo deploy a
Railway que Nixpacks efectivamente recogio este `php.ini` (no se pudo
verificar en vivo contra Railway desde este entorno).

Estructura de almacenamiento:

- cliente
- agrupacion
- anio
- semana
- activo
- tipo de evidencia
- archivo

Ruta base construida por ReportFilePathBuilder:

- clientes/{cliente}/agrupaciones/{agrupacion}/{anio}/semana-{n}/{activo}/evidencia-{kind}/{archivo}

## 10. Mediciones

Controlador principal:

- [app/Http/Controllers/Admin/SystemModules/MeasurementController.php](/home/jupazago/Documentos/mantecv1/mantec/app/Http/Controllers/Admin/SystemModules/MeasurementController.php)

Vistas principales:

- [resources/views/admin/system-modules/measurements/index.blade.php](/home/jupazago/Documentos/mantecv1/mantec/resources/views/admin/system-modules/measurements/index.blade.php)
- [resources/views/admin/system-modules/measurements/level-one.blade.php](/home/jupazago/Documentos/mantecv1/mantec/resources/views/admin/system-modules/measurements/level-one.blade.php)
- [resources/views/admin/system-modules/measurements/show.blade.php](/home/jupazago/Documentos/mantecv1/mantec/resources/views/admin/system-modules/measurements/show.blade.php)
- [resources/views/admin/system-modules/measurements/partials/area-summary-modal.blade.php](/home/jupazago/Documentos/mantecv1/mantec/resources/views/admin/system-modules/measurements/partials/area-summary-modal.blade.php)

### 10.1 Navegacion por area (2026-08-26)

El modal "Resumen valores minimos obtenidos" (antes solo en level-one) se
extrajo a un partial compartido, incluido tanto en `level-one.blade.php`
como en `show.blade.php`. Ambas vistas lo abren disparando eventos Alpine
globales en vez de llamar metodos directos, para no acoplar el trigger a
donde vive el componente:

- `open-area-summary` `{ id, name, elementTypeId, clientName, elementTypeName }`
  abre el modal con la tabla de valores minimos por activo.
- `open-siblings-sidebar` agrega `currentElementId` y abre un sidebar lateral
  derecho con los demas activos de la misma area (marca el activo actual).

El header de `show.blade.php` (`@section('header_actions')`, renderizado por
`layouts/measurements.blade.php`) tiene 3 botones: sidebar de activos
hermanos, volver a `level-one` (home), y volver a abrir el modal de resumen
del area sin salir de la vista de detalle.

El modal y el sidebar comparten el mismo array reactivo `areaSummaryItems`
(fetch a la ruta `admin.system-modules.measurements.level-one.area-summary`).
El sidebar filtra siempre `show_in_summary === true` (no lista activos
excluidos del resumen), y como ambos leen el mismo estado, excluir/incluir
un activo desde el modal se refleja en el sidebar sin recargar la pagina.

Capacidades observadas:

- listado del modulo
- vista por activo
- borradores de espesor
- publicacion de reportes de espesor
- borradores de estado de banda
- publicacion de reportes de estado de banda
- consulta de historicos
- actualizacion y eliminacion de reportes publicados

La API movil de mediciones ya contempla:

- tipos de activo habilitados
- areas por tipo
- activos por area y tipo
- lectura de draft y ultimo historico
- sincronizacion de draft

El acceso depende de:

- grupo asignado al inspector
- modulo habilitado para cliente/tipo de activo
- bandera creation_enabled

## 11. Eventos De Banda

Controladores:

- BandEventDraftController
- BandEventReportController
- BandEventEvidenceController

Capacidades observadas:

- crear y editar borradores
- publicar borradores
- manejar evidencia de draft y de reporte
- editar y eliminar reportes publicados

### 11.1 Campo "Avería" en cambio de banda (2026-08-26)

`band_events` y `band_event_drafts` tienen ahora una columna `averia`
(boolean, nullable — mismo patron que `same_reference`). Solo aplica al
`type === 'band'` (Cambio de banda), aunque la columna vive en la misma
tabla compartida con vulcanizado/cambio de tramo, igual que el resto de
campos especificos por tipo.

Checkbox "¿Avería?" visible en dos puntos de `show.blade.php`:

- Wizard de creacion (paso 1, tarjeta "Nuevo cambio de banda"), ligado a
  `bandDraft.averia`. Se guarda como cualquier otro campo del draft
  (`saveBandDraft()`/`publish()` ya envian el objeto completo).
- Modal "Editar evento histórico" (`bandEditForm`), visible solo cuando
  `bandEditForm.type === 'band'`.

Para registros existentes antes de este cambio, `averia` es `null` y la UI
lo trata como "No" (`x-text="selectedBandHistory?.averia ? 'Sí' : 'No'"`);
no hizo falta backfill.

Tambien se agrego una fila "Avería" (Sí/No) en la tabla de solo lectura
"Histórico - Cambio de banda", para poder consultar el valor sin tener que
abrir el modal de edición.

Cobertura: [tests/Feature/Admin/BandEventAveriaTest.php](/home/jupazago/Documentos/mantecv1/mantec/tests/Feature/Admin/BandEventAveriaTest.php).

**Bug encontrado y corregido de paso**: el backdrop del wizard de cambio de
banda usaba `class="fixed inset-0 ..."` sin `h-screen w-screen` explicito,
y por alguna razon (no atribuible a ningun ancestro con `transform`/
`filter`/`contain` — se verifico con Playwright) el alto calculado quedaba
por debajo del viewport real, dejando una franja sin oscurecer en la parte
inferior. Todos los demas modales de este archivo ya usaban el patron
`top-0 left-0 h-screen w-screen`; se alineo este wizard a esa misma
convencion y el problema desaparecio.

El modelo incluye datos tecnicos como:

- referencia de banda
- espesores
- lonas
- largo/ancho
- vulcanizado
- entrega de equipo
- cambio de tramo

Es un modulo separado del preventivo clasico, aunque reutiliza activos, usuarios y storage.

## 12. Dashboard E Indicadores

Archivos clave:

- [app/Http/Controllers/DashboardController.php](/home/jupazago/Documentos/mantecv1/mantec/app/Http/Controllers/DashboardController.php)
- [app/Http/Controllers/Admin/IndicatorController.php](/home/jupazago/Documentos/mantecv1/mantec/app/Http/Controllers/Admin/IndicatorController.php)
- [app/Services/Semaphore/SemaphoreBuilder.php](/home/jupazago/Documentos/mantecv1/mantec/app/Services/Semaphore/SemaphoreBuilder.php)

Funciones actuales:

- acceso a reportes por agrupacion desde dashboard
- cobertura de activos inspeccionados
- total de preventivos
- distribucion de severidad
- distribucion por condicion
- reportes por semana
- resumen por tipo de activo
- distribucion por area
- semaforo configurable por plantilla
- ajuste manual de cambio de banda
- indicadores anuales de cambio de banda y seguridad (tarjetas
  "Si requiere/No requiere" y "OK/Con novedad" del dashboard)

El semaforo ya no es totalmente rigido. Hay una capa legacy y otra configurable por plantilla.

### 12.1 Indicadores anuales respetan execution_status (2026-08-26)

`buildBeltChangeAnnual()` y `buildSecurityAnnual()` en IndicatorController
antes solo miraban `condition_id`/`severity` del ultimo ReportDetail, sin
mirar si la orden correctiva ya fue ejecutada. Un hallazgo en ALTA con la
orden marcada REALIZADO/EJECUTADO seguia contando como "Si requiere" o
"Con novedad" indefinidamente, hasta que existiera una inspeccion nueva.

Ahora, si el registro ganador tiene `execution_status` en estado
EJECUTADO/REALIZADO/FINALIZADO (via `ExecutionStatusResolver::isDoneStatusName()`,
ya inyectado en el controlador), cuenta como resuelto:

- Cambio de banda: pasa a "No requiere".
- Seguridad: esa severidad se trata como 0 al calcular la peor de los 3
  componentes (Guardas, Cubiertas, Plataforma y estructura), y si las 3
  quedan resueltas el activo pasa a "OK".

Los overrides manuales (`SemaphoreBeltChange`) no aplican esta logica: no
tienen `execution_status`, son independientes de ordenes/ReportDetail.

Cobertura: [tests/Feature/Admin/IndicatorAnnualExecutionStatusTest.php](/home/jupazago/Documentos/mantecv1/mantec/tests/Feature/Admin/IndicatorAnnualExecutionStatusTest.php).

## 13. Vistas y Layouts

Layout principal admin:

- [resources/views/layouts/admin.blade.php](/home/jupazago/Documentos/mantecv1/mantec/resources/views/layouts/admin.blade.php)

Caracteristicas:

- Tailwind
- Alpine por CDN
- Lucide por CDN
- sidebar colapsable con estado en localStorage

Conclusiones UI:

- El sistema sigue siendo Blade-first.
- La interactividad se resuelve con Alpine, JS embebido y fetch/AJAX puntuales.
- No hay SPA React/Vue. Cualquier cambio funcional debe pensarse sobre Blade y endpoints JSON auxiliares.

## 14. Base De Datos

Archivo de verdad: database/migrations/

Resumen de evolucion:

- marzo 2026: base del dominio preventivo
- abril 2026: alcance por tipos/areas/grupos y arranque de modulos tecnicos
- mayo 2026: semaforos, configuracion por grupo, paradas, recommendation_2
- junio 2026: evidence_kind y soft deletes para evidencias

Migraciones recientes muy relevantes:

- 2026_05_31_000001_add_recommendation_2_to_report_details_table.php
- 2026_05_31_000002_create_group_report_configs_table.php
- 2026_05_31_000003_create_group_report_config_columns_table.php
- 2026_05_31_000004_create_paradas_table.php
- 2026_05_31_000005_create_parada_areas_table.php
- 2026_06_19_000001_add_evidence_kind_to_report_detail_files_table.php
- 2026_06_19_000002_add_soft_deletes_to_report_detail_files_table.php

## 15. Integraciones Externas

### Cloudflare R2

Uso:

- evidencias de reportes preventivos
- evidencias de eventos de banda

Riesgo practico:

- cualquier cambio en disks, rutas o temporaryUrl() afecta apertura de archivos ya cargados.

### Railway

Uso actual documentado:

- existe un entorno real de pruebas muy cercano a produccion
- se usa como fuente para sincronizar base hacia local

El procedimiento operativo ya quedo documentado en:

- [ARRANCAR_LOCAL.txt](/home/jupazago/Documentos/mantecv1/mantec/ARRANCAR_LOCAL.txt)

## 16. Cambios Relevantes Ya Incorporados Hoy

Estado consolidado al 2026-06-19:

- Se formalizo documentacion de arranque local evitando conflicto con pgsql de Sail.
- Se documento copia de base desde Railway hacia local.
- Se agrego evidencia diferenciada por hallazgo y correccion.
- report_detail_files ahora soporta borrado logico.
- admin_cliente puede gestionar solo evidencia correctiva.
- Se ajustaron filtros de fechas en reportes por agrupacion.
- El filtro de fecha de ejecucion se alineo a estados visibles de ejecucion, no a cualquier fecha persistida.
- El modal/popover de filtros de fecha conserva estado activo.
- La vista individual de evidencia tuvo ajustes de UX y control de permisos.

Estado consolidado al 2026-08-26:

- Indicadores anuales (cambio de banda/seguridad) ahora consideran
  `execution_status`: ver [12.1](#121-indicadores-anuales-respetan-execution_status-2026-08-26).
- Mediciones: navegacion por area en el header de la vista de detalle
  (sidebar de activos hermanos, home, volver a resumen), modal compartido
  entre level-one y show: ver [10.1](#101-navegacion-por-area-2026-08-26).
- Bugfix: `bandEditForm` en `measurementThicknessModule()`
  (show.blade.php) arrancaba en `null`, y el modal de "Editar banda"
  (oculto por `x-show`, pero con `x-model="bandEditForm.report_date"` y
  demas campos) hacia que Alpine lanzara `Cannot read properties of null
  (reading 'report_date')` en consola en *cada* carga de la vista, con o
  sin datos. Se cambio el valor inicial/de reset a `{}` (el mismo patron ya
  usado por `bandStateEditForm`/`historyEditForm`); `openBandEditModal()`
  sigue sobrescribiendo el objeto completo con `emptyBandDraft()` antes de
  mostrar el modal, sin cambios de comportamiento ahi.
- Campo `averia` en cambio de banda (checkbox "¿Avería?" en creacion y
  edicion) y fix del backdrop del wizard que no cubria toda la pantalla:
  ver [11.1](#111-campo-averia-en-cambio-de-banda-2026-08-26).

Estado consolidado al 2026-09-17:

- Fusion de reportes duplicados el mismo dia unificada entre web y API
  movil, con trazabilidad por inspector (nombre + fecha/hora) cuando mas de
  un inspector complementa el mismo hallazgo, y correccion del hueco de
  autorizacion en el endpoint de sync movil: ver
  [8.1.1](#811-fusion-de-reportes-duplicados-el-mismo-dia-2026-09-17).

Estado consolidado al 2026-09-21:

- `AppServiceProvider::boot()` forzaba `https` siempre (`URL::forceScheme('https')`
  sin condicion), lo que rompia local (`php artisan serve` solo habla HTTP)
  cada vez que producción (Railway, HTTPS real) lo necesitaba forzado — el
  flujo habitual era editar esa linea a mano segun donde se estuviera
  trabajando. Ahora es condicional a `APP_ENV`: `if (! $this->app->environment('local')) { URL::forceScheme('https'); }`.
  Verificado en esta sesion simulando ambos entornos: `url('/')` da
  `http://127.0.0.1:8000` en local real y `https://...` simulando
  `production` — ya no hace falta tocar el archivo a mano en ningun caso.

## 17. Riesgos Y Deuda Tecnica Visible

- Las rutas publicas /test-r2 y /php-upload-check deberian evaluarse para deshabilitarse fuera de diagnostico controlado.
- El sistema mezcla varias responsabilidades en algunos controladores grandes, especialmente reportes e indicadores.
- Parte importante de las reglas de negocio esta en controladores y no toda en servicios dedicados.
- La coexistencia de web admin, inspector web y API movil obliga a revisar impacto cruzado antes de tocar modelos nucleares.
- ANALISIS_SISTEMA_LARAVEL.txt ya no debe volver a crecer; el mantenimiento debe concentrarse en este .md.

## 18. Reglas Para Mantener Este Documento

Actualizar este archivo si cambias cualquiera de estos puntos:

- rutas web o API
- roles o permisos
- tablas o migraciones
- columnas configurables de reportes
- flujos de evidencia
- contratos usados por Android
- storage R2
- modulos tecnicos de mediciones o eventos de banda
- arranque local o sincronizacion con Railway

## 19. Recomendacion Operativa

Si el objetivo es que tanto Codex como Claude Code se contextualicen rapido, este formato es mejor que .txt por tres razones:

- permite encabezados claros y busqueda rapida por secciones
- admite enlaces a archivos concretos del repo
- reduce ambiguedad cuando el sistema crece y cambia seguido

Recomendacion final:

- mantener ANALISIS_SISTEMA_LARAVEL.md como fuente canonica
- dejar ANALISIS_SISTEMA_LARAVEL.txt solo como puntero
- cuando pidas contexto del proyecto, referenciar siempre este .md
