# Nueva Funcionalidad: Gestion De Personal Y Programacion De Actividades

Estado del documento: **borrador vivo, en levantamiento**. Se ira completando a
medida que lleguen Excel, pantallazos y detalles adicionales. No implementar
codigo a partir de este documento hasta que el alcance quede cerrado (ver
seccion 11, Pendientes).

Fecha de inicio del levantamiento: 2026-09-05
Ultima actualizacion: 2026-09-07 (se incorporan `bitacora.pdf`, `diario
sept.pdf` y pantallazos de Excel reales como referencia de formato actual)

## 1. Resumen

Modulo nuevo de caracter **administrativo** (no operativo/campo de
inspeccion), aunque relacionado con el sistema existente porque reutiliza
infraestructura ya construida (Laravel, PostgreSQL, R2, la app Android como
base tecnica para la nueva capacidad de supervisor).

**Nota de terminologia importante**: la palabra "empresa" en este modulo NO
es el mismo concepto que `Client` en el sistema actual de reportes
preventivos. Aqui "empresa" es el **sitio/cliente donde se presta mano de
obra de mantenimiento** (ej. Argos, Corona, Calidra — clientes reales
observados en `diario sept.pdf`), en el contexto de una operacion de
contratistas (mecanicos, auxiliares, supervisores) que trabajan en sitio para
esas empresas. Es un dominio de negocio distinto al de inspecciones
preventivas, aunque coexista en la misma plataforma.

El modulo reemplaza/formaliza en software un proceso que **hoy ya existe en
Excel**: una "Bitacora" mensual de horas por empleado y un "Diario de Campo"
de actividades tecnicas por dia/empresa/equipo. Ambos archivos fueron
compartidos como referencia real de formato y contenido (ver seccion 10).

Cubre estos frentes:

1. **Gestion de personal**: CRUD de empleados con datos personales y
   documentos (contratos, certificaciones/inducciones) almacenados en R2, con
   fechas de vencimiento y alertas configurables.
2. **Catalogo dinamico de empresas e inducciones/certificaciones**: que
   empresas existen, que inducciones/certificados exige cada una, y si son
   obligatorias — todo configurable sin tocar backend.
3. **Programacion de actividades**: los supervisores crean actividades desde
   la web, organizadas por grupos, con empleados filtrados por elegibilidad
   segun sus certificaciones vigentes.
4. **Registro de campo (app Android, supervisores)**: evidencias, comentarios
   y horas reales trabajadas por actividad, comparadas contra lo programado.
5. **Bitacora mensual**: consolidado de horas por empleado, con
   correccion auditable por parte de administrativos.
6. **Diario de Campo**: el registro tecnico detallado de cada actividad
   (mismo objeto que la actividad de programacion, enriquecido a medida que
   avanza su ciclo de vida — ver seccion 5).
7. **Dashboards comparativos**: programado vs. reportado (aun superficial).

## 2. Flujo General (Como Encajan Las Piezas)

Aclarado explicitamente por el usuario el 2026-09-07 — este es el flujo real
que conecta todas las secciones de este documento:

```
1. Supervisor (web) crea una ACTIVIDAD en la Programacion:
   - la asigna a un grupo
   - selecciona empresa, area, empleados (filtrados por elegibilidad,
     ver seccion 4)
   - marca si es actividad PRIMARIA o SECUNDARIA para cada persona
   - define horas estimadas, supervisor responsable, etc.

2. Esa misma actividad la completa el SUPERVISOR desde la app Android:
   - evidencias, comentarios
   - horas reales trabajadas (o checkbox de inasistencia = 0,
     o rango hora inicio/hora fin si trabajo)

3. El ADMINISTRATIVO (rol nuevo, web) revisa y TERMINA de llenar la
   actividad: campos administrativos/tecnicos adicionales (ver Diario de
   Campo, seccion 5), corrobora horas, corrige si hace falta (sin borrar el
   dato original del supervisor), agrega comentarios.

4. Con la Programacion + lo reportado por los supervisores, se alimenta
   automaticamente la BITACORA mensual (seccion 6) y el DIARIO DE CAMPO
   (seccion 5) — no son formularios de captura independientes, son vistas/
   consolidados sobre las mismas actividades.
```

Es decir: **la actividad es una sola entidad que atraviesa un ciclo de vida
de 3 actores** (supervisor-crea -> supervisor-ejecuta -> administrativo-
cierra), y tanto la Bitacora como el Diario de Campo son proyecciones/
reportes sobre esas actividades, no tablas separadas que se llenan a mano
por aparte.

## 3. Autenticacion Y Roles

- Habra un **login administrativo diferente** al login actual.
- El rol `superadmin` es el unico rol existente que se mantiene con acceso a
  esta area nueva.
- Se crearan **roles nuevos**, exclusivos de este modulo administrativo. Al
  menos dos ya se pueden inferir del flujo descrito: **supervisor** (crea
  programacion en web, registra ejecucion en app Android) y **administrativo/
  auxiliar administrativa** (corrobora, corrige horas, cierra el Diario de
  Campo, ve la Bitacora).
  **Pendiente**: nombres formales de los roles y permisos exactos de cada
  uno (ej. ¿el supervisor puede corregir su propio reporte despues de
  enviarlo, o solo el administrativo puede corregir?).

## 4. CRUD De Empleados

Datos por empleado:

- Informacion personal (**pendiente**: campos exactos, vendran en Excel).
- Estado activo/inactivo (activar/inactivar trabajadores, sin necesariamente
  eliminar el registro).
- Iconos visuales indicando que certificaciones/inducciones vigentes tiene
  cada empleado (ver seccion 4.4) — se observan en `diario sept.pdf` columnas
  abreviadas por persona/actividad (ej. `siso`, `Rescatista`, `Sup`, `Ing`,
  y otras sin encabezado completo visible) que apuntan a este mismo concepto
  de roles/certificaciones marcadas por persona.

### 4.1 Documentos adjuntos (R2)

Cada empleado puede tener varios documentos adjuntos, subidos a Cloudflare R2
(reutiliza el patron ya existente en el sistema para evidencias). Tipos de
archivo: jpg, pdf u otros. Ejemplos de documentos mencionados:

- Copia del contrato.
- Certificado de trabajo en alturas.
- Certificado de espacios confinados.
- (posiblemente otros — a confirmar; ver tambien seccion 4.4, que generaliza
  esto a un catalogo configurable de inducciones por empresa)

Cada documento tiene:

- **Fecha de obtencion.**
- **Fecha de vencimiento.**
- Un **tiempo de alerta configurable por columna** (es decir, por tipo de
  documento): define con cuanta anticipacion se debe mostrar una notificacion
  de que ese documento especifico esta por vencer. Ejemplo mencionado: avisar
  cuando un contrato esta por finalizar.

### 4.2 Historico de documentos

Al subir un nuevo documento de un tipo que ya tenia uno anterior (ej. un
nuevo contrato o una recertificacion), el anterior **no se borra**: se
conserva como historico. Habra un **modal para consultar el historico de
documentos/contratos anteriores de un empleado**, ordenado por fecha de
obtencion.

### 4.3 Mini-CRUDs de catalogos de apoyo

CRUDs simples en modal para catalogos que usa el CRUD de empleados, por
ejemplo:

- Roles (de empleado — **a confirmar si es el mismo concepto que los "roles
  nuevos" de la seccion 3, o un catalogo distinto**, ej. cargo/puesto:
  mecanico, auxiliar, supervisor, SISO, etc. — estas categorias de cargo SI
  se ven reflejadas en `diario sept.pdf`, columnas `mec`/`aux`/`sup y siso x
  hora`, que ademas sugieren una tarifa de costo por hora asociada a cada
  categoria de cargo — **pendiente confirmar si el calculo de costo por
  actividad/rol entra en el alcance de este modulo o es un tema aparte**).
- Planta de trabajo.
- Posiblemente mas catalogos similares (**pendiente**, el usuario indico
  "quiza mas con sus propios minicruds" sin especificar cuales).

### 4.4 Empresas Y Catalogo Dinamico De Inducciones/Certificaciones

Aclarado por el usuario el 2026-09-07 — esta es una pieza central del modulo,
mas compleja que un catalogo simple:

- Existe un catalogo de **empresas** (sitios cliente de mano de obra: Argos,
  Corona, Calidra, etc. — ver nota de terminologia en seccion 1).
- Cada empresa exige **inducciones/certificaciones obligatorias** para poder
  trabajar alli (ej. induccion de seguridad de Argos, induccion de Corona).
  Estas son **distintas** de los certificados tecnicos generales del
  empleado (alturas, espacios confinados) — o al menos deben poder
  configurarse independientemente por empresa, aunque compartan el mismo
  mecanismo de fecha de obtencion/vencimiento que la seccion 4.1.
- **Requisito explicito de diseño**: este catalogo NO puede ser un conjunto
  fijo de columnas en el backend. Debe ser **completamente configurable**
  desde la interfaz:
  - Crear una empresa nueva sin tocar codigo (ej. si mañana contratan con
    una empresa nueva).
  - Crear un tipo de induccion/certificacion nuevo sin tocar codigo (ej. si
    mañana empiezan a trabajar en un area nueva que exige "rescate en
    aguas").
  - Marcar cada induccion como **obligatoria o no** para cada empresa.
  - Esto aplica tanto a las inducciones **por empresa** como a los
    certificados **generales** de la seccion 4.1 — en efecto, el catalogo de
    "tipos de documento/certificacion" completo (sea generico del empleado o
    exigido por una empresa especifica) debe vivir en una tabla de catalogo
    editable, no en columnas fijas.
- **Regla de elegibilidad (bloqueo automatico)**: en el selector de
  empleados de la Programacion (seccion 5), un empleado **no es
  seleccionable** para una actividad de una empresa determinada si le falta
  alguna induccion obligatoria de esa empresa, o si la tiene vencida. Esto
  es una validacion de negocio real (cumplimiento/seguridad), no solo un
  filtro visual.
- Se muestran **iconos** junto a cada empleado indicando que certificaciones
  tiene vigentes (ej. icono de alturas, icono de espacios confinados),
  tanto en el CRUD de empleados como, presumiblemente, en el selector de la
  Programacion.

**Implicacion tecnica** (para cuando se implemente, no ejecutar aun): esto
sugiere un modelo de datos tipo catalogo-configurable (algo como
`companies`, `certification_types`, `company_required_certifications`
pivote con bandera `is_mandatory`, y `employee_certifications` con fechas de
obtencion/vencimiento) en vez de columnas fijas — es la pieza de mayor
complejidad de negocio de todo el modulo, ver impacto en costo en seccion 9.

## 5. Diario De Campo (Objeto "Actividad" Enriquecido)

Con base en `diario sept.pdf` y las capturas de Excel, el "Diario de Campo"
es el mismo registro de actividad de la Programacion (seccion 6), pero
mostrado con **todos los campos que se van completando a lo largo de su
ciclo de vida** (ver flujo en seccion 2). Campos observados en el Excel real
`DIARIO DE CAMPO TRABAJOS MAN & TEC`:

| Campo | Quien lo llena | Notas |
|---|---|---|
| Fecha del trabajo | Supervisor (Programacion) | |
| Empresa | Supervisor (Programacion) | Ej. ARGOS, CORONA — ver seccion 4.4 |
| Equipo | Supervisor (Programacion) | Ej. "Rioclaro", "TP1 Trituradora", "Transporte", "Taller San Luis" — parece ser un catalogo de equipos/frentes de trabajo, no necesariamente el mismo concepto de "Activo" del modulo de reportes preventivos |
| Proceso | Supervisor (Programacion o al ejecutar) | Ej. "Cambiar banda", "SST", "Coordinador" |
| Actividad (texto libre) | Supervisor (app, al ejecutar) | Descripcion tecnica larga y detallada de lo realizado — texto libre extenso, no un catalogo cerrado |
| Nombre de las personas | Supervisor (Programacion + ejecucion) | Lista de personas del grupo en esa actividad |
| N° de personas | Calculado o manual | |
| Horas | Supervisor (ejecucion) | Ver logica de horas en seccion 7 |
| Jornada | Supervisor (ejecucion) | DIURNO / NOCTURNO |
| Comentarios | Administrativo (cierre) | |
| ZCOM, Linea, OT SAP, Acta Entrega Solicitada, WE | Administrativo (cierre) | Codigos de integracion/seguimiento con el sistema del cliente (ej. SAP de Argos) — **pendiente confirmar significado exacto de cada uno y si son obligatorios siempre o solo para ciertas empresas/procesos** |
| Columnas de certificacion por persona en la actividad (ej. `siso`, `Rescatista`, `Sup`, `Ing`, y otras abreviadas sin confirmar) | Deriva de seccion 4.4 | Marca que certificaciones cubren las personas asignadas a esa actividad especifica |

**Pendiente explicito**: el significado exacto de `ZCOM`, `OT SAP`, `Acta
Entrega Solicitada` y `WE` — parecen campos de integracion/trazabilidad
especificos del contrato con Argos (posiblemente numeros de orden de trabajo
SAP), no necesariamente aplicables a todas las empresas del catalogo. Definir
si son campos fijos del modulo o si tambien deben ser configurables por
empresa (siguiendo el mismo principio de flexibilidad de la seccion 4.4).

## 6. Programacion De Actividades (Web, Supervisores)

- La crea el **supervisor** desde la web.
- **Organizada por grupos**: las actividades se agrupan (ej. una cuadrilla o
  equipo de trabajo), y al exportar/enviar la programacion (ej. por
  WhatsApp), el orden de salida respeta el orden de grupo.
  **Pendiente**: definir si "grupo" es un catalogo propio (con nombre,
  supervisor fijo, etc.) o simplemente un agrupador visual de la tabla de
  programacion.
- **Actividad primaria vs. secundaria**: cada persona puede tener **una sola
  actividad primaria** en el periodo (ej. el dia), pero **N actividades
  secundarias**. Al crear una actividad, se indica si es primaria o
  secundaria para las personas involucradas. Esto implica una validacion:
  el sistema debe impedir asignar una segunda actividad primaria a la misma
  persona en el mismo periodo.
- Selecciona **empresa** y **area** (combobox).
- Selecciona **uno o varios empleados** (combobox), **filtrado por
  elegibilidad** segun la seccion 4.4 (no aparecen o no son seleccionables
  los empleados sin las inducciones vigentes que exige la empresa elegida).
- **La cantidad de columnas depende de la cantidad de roles** (**pendiente**:
  aclarar exactamente que significa esto — ¿una columna de horas por cada rol
  involucrado en la actividad? ¿columnas distintas segun el tipo de empleado
  seleccionado?).
- Horas de trabajo posible/estimado (**pendiente**: definicion exacta del
  campo).
- Cada actividad tiene un **supervisor asignado**.
- Puede haber **varias actividades por dia**: un boton "+" agrega una fila
  nueva de actividad, se llena y se guarda.
- **Pendiente**: el usuario indico que enviara pantallazos/ejemplos
  concretos de como son las programaciones reales (mencionado el
  2026-09-07, aun no llegan al cierre de esta version del documento).

## 7. Registro De Horas Y Evidencias (App Android, Supervisores)

Funcionalidad **nueva** dentro de la app Android existente, para el rol
supervisor (a diferenciar del rol inspector ya existente en la app):

- El supervisor registra **evidencias y comentarios** sobre la actividad
  programada que se ejecuto.
- Indica si **todos** los empleados de la actividad trabajaron las horas
  acordadas en la programacion, o si hubo diferencias.
- Si hubo diferencias, se registra la cantidad de horas trabajadas
  **individualmente por cada persona** en esa actividad.
- Mecanica de captura de horas por persona:
  - Checkbox: si la persona **no trabajo**, el valor queda en **0**.
  - Si **si trabajo**, no se ingresa la cantidad de horas directamente: se
    ingresa **hora de inicio** y **hora final** (el sistema calcularia la
    duracion a partir de estos dos valores).
- **Pendiente**: "otros campos mas que luego detallaremos" (el usuario
  indico que hay campos adicionales sin especificar todavia).
- Cada supervisor hace este registro para las distintas actividades
  asignadas a su nombre ese dia.

## 8. Bitacora Mensual (Web, Administrativos Y Superadmin)

Vista de consolidado mensual para roles administrativos y `superadmin`,
alimentada por la Programacion + lo reportado por los supervisores (ver
flujo en seccion 2) — **no es una tabla de captura manual independiente**.

Con base en `bitacora.pdf` (formato real actual en Excel), la vista debe
reproducir al menos:

- Una fila por dia del mes (1 a 30/31), una columna por empleado.
- Valor de horas trabajadas ese dia por ese empleado en la celda.
- **Alerta visual** si hay dias con **0 horas** reportadas por el supervisor
  para algun empleado.
- El administrativo puede **corregir una hora**: se registra la hora nueva
  **sin borrar la anterior** (queda historico de la correccion).
- El valor corregido se muestra **en un color distinto** al original (en el
  Excel actual el patron de color ya se usa para destacar celdas, ej.
  amarillo para dias sin registro esperado, rojo para domingos/festivos).
- El administrativo puede dejar **comentarios**, que se muestran como
  **tooltip** sobre el dato (el Excel actual ya usa comentarios de celda de
  Excel exactamente con este proposito, ej. "jornada nocturna-1 ED-11EN" —
  confirma que el patron de UX que se pidio ya existe y hay que replicarlo).
- El Excel actual tambien totaliza por empleado: **total de horas del mes**
  vs. **horas a laborar** (una cuota fija mensual, 182 horas en el ejemplo
  visto) y una fila de **"extras"** (diferencia entre lo trabajado y la
  cuota, casi siempre negativa en el ejemplo). **Pendiente confirmar si este
  calculo de cuota/extras debe replicarse en la vista web** o si es un
  calculo aparte que hace la empresa fuera del alcance de este modulo.

## 9. Dashboards Comparativos (Programado Vs. Reportado)

Analogo a lo que ya existe para reportes preventivos (dashboard + tablas de
detalle), se necesitan **2 vistas adicionales** que comparen lo programado
(seccion 6) contra lo efectivamente reportado por los supervisores
(seccion 7):

1. Un dashboard.
2. Una vista de tablas con informacion a detalle.

**Estado: muy preliminar.** El usuario indico explicitamente que por ahora
solo tiene una idea superficial de estos dos modulos, sin detalle funcional
todavia.

## 10. Relacion Con El Sistema Existente Y Con Los Archivos De Referencia

- Reutiliza: Laravel/PostgreSQL como backend, Cloudflare R2 para archivos
  (mismo patron de `ReportFilePathBuilder`/streaming ya usado para evidencias
  de reportes preventivos, ver `ANALISIS_SISTEMA_LARAVEL.md` seccion 9), y la
  app Android existente como base tecnica (Retrofit, Room, WorkManager,
  autenticacion Sanctum) para la nueva capacidad de supervisor.
- Es una capa **separada** de lo operativo/campo (reportes preventivos,
  mediciones, eventos de banda): login administrativo distinto, roles
  distintos, dominio de datos distinto (empleados/horas/empresas-cliente de
  mano de obra en vez de activos/diagnosticos). Ver nota de terminologia en
  seccion 1 sobre "empresa" vs. `Client`.
- Queda por definir si "supervisor" y "administrativo" son roles nuevos
  dentro del mismo sistema de roles actual (`app/Models/Role.php`,
  `RoleModulePermission`) o un esquema de autenticacion completamente aparte
  (seccion 3 ya lo deja como login independiente).
- **Archivos de referencia real recibidos el 2026-09-07** (formato actual en
  Excel del proceso que este modulo va a reemplazar/formalizar):
  - `Bitacora Septiembre 2026.xlsx` (capturas + `bitacora.pdf`): bitacora
    mensual de horas por empleado, ~40 empleados, con cuota de 182 h/mes,
    columna de extras, comentarios de celda y color condicional.
  - `DIARIO DE CAMPO TRABAJOS MAN & TEC.xlsx` (capturas + `diario sept.pdf`):
    diario de actividades tecnicas por empresa/equipo/proceso, con campos de
    integracion tipo SAP (ZCOM, OT SAP, Acta Entrega, WE) y columnas de
    certificacion por persona.

## 11. Pendientes Explicitos (No Asumir, Confirmar Con El Cliente/Usuario)

- Roles administrativos nuevos: nombres y permisos exactos (¿supervisor
  puede autocorregirse o solo el administrativo corrige?).
- Campos completos de informacion personal del empleado.
- Lista completa de tipos de documento/certificacion a adjuntar, y cuales
  son genericos del empleado vs. cuales son exigidos por empresa especifica.
- Definicion exacta de "cantidad de columnas depende de cantidad de roles"
  en la programacion.
- Definicion de "grupo" en la Programacion (¿catalogo propio o agrupador
  visual?).
- Campos completos de la programacion de actividades (pantallazos
  prometidos, aun no llegan a esta version).
- Campos adicionales del registro de horas/evidencias en Android.
- Significado exacto de `ZCOM`, `OT SAP`, `Acta Entrega Solicitada`, `WE` en
  el Diario de Campo, y si son fijos o configurables por empresa.
- Si el calculo de costo por hora segun rol (`mec`/`aux`/`sup y siso`) entra
  en el alcance de este modulo.
- Si la logica de cuota mensual (182 h) y "extras" del Excel actual debe
  replicarse en la Bitacora web.
- Detalle funcional de los 2 dashboards comparativos (seccion 9).

## 12. Estimacion De Costo Y Tiempo

**Nota interna**: esta seccion es para uso propio de planeacion/negociacion.
No debe copiarse literalmente a comunicaciones con el cliente — en
particular, la comparacion de tiempo con asistencia de IA es informacion
interna de productividad, no un dato que deba aparecer en una cotizacion
formal.

### 12.1 Costo de mercado (sin descuento, referencia)

Metodo: comparar cada pieza contra fases de la cotizacion de referencia
(`COTIZACIÓN SOFTWARE.pdf`, COP $30.000.000 / 10 semanas ≈ $3.000.000/semana
promedio). Version actualizada tras conocer la complejidad real del catalogo
de empresas/inducciones con logica de elegibilidad, grupos, actividad
primaria/secundaria y el objeto enriquecido del Diario de Campo (todo esto
no se conocia en la primera estimacion del 2026-09-05):

| Pieza | Semanas est. (rango) |
|---|---|
| Login administrativo + roles nuevos | 0,5 – 1 |
| CRUD Empleados (datos + R2 + vencimientos + alertas + historico) | 1 – 1,5 |
| Catalogo dinamico de empresas + inducciones/certificaciones + iconos + logica de elegibilidad (bloqueo de seleccion) | 1,5 – 2,5 |
| Mini-CRUDs de catalogos de apoyo | 0,5 – 0,75 |
| Programacion: grupos, actividad primaria/secundaria con validacion, selector con filtro de elegibilidad, orden de exportacion | 1,5 – 2,5 |
| Diario de Campo (objeto enriquecido, campos tipo SAP, cierre administrativo) | 1 – 1,5 |
| Registro de horas/evidencias en Android | 0,75 – 1 |
| APIs nuevas (empleados, empresas/inducciones, programacion, registro de horas) | 0,75 – 1 |
| Bitacora mensual con correccion/alertas/tooltip | 0,75 – 1,25 |
| 2 dashboards comparativos (superficial, puede crecer) | 0,5 – 1 |
| Pruebas y estabilizacion | 0,75 – 1,25 |
| **Total** | **~9,5 – 15 semanas** |

A la tarifa promedio de referencia (~$3.000.000/semana): **~COP $28.500.000 –
$45.000.000** de valor de mercado para el alcance actualmente conocido
(sigue siendo parcial, ver seccion 11).

### 12.2 Lo que se cobrara vs. la referencia de mercado

Valor a cobrar decidido por el usuario: **~COP $4.000.000 – $4.200.000**.

Esto representa aproximadamente **9% – 15%** del rango de valor de mercado
estimado (12.1) — un descuento considerablemente mayor al que parecia al
momento de fijar el precio (cuando la complejidad conocida era menor,
seccion 12 original del 2026-09-05 estimaba 15%-23%). Motivo del descuento:
continuidad de la relacion comercial con Mantec, ya explicado por el usuario
en conversaciones previas (misma logica que las "etapas" anteriores
cobradas por debajo de mercado).

### 12.3 Tiempo real estimado con asistencia de IA (dato interno, no para el cliente)

Estimacion aproximada de tiempo de desarrollo real trabajando con asistencia
de IA (Claude Code), distinguiendo por tipo de trabajo:

- Piezas tipo CRUD/formulario (login+roles, CRUD empleados, mini-CRUDs,
  APIs basicas, dashboards): la asistencia de IA acelera significativamente
  este tipo de trabajo (boilerplate, formularios, endpoints repetitivos) —
  reduccion estimada de ~50% sobre el tiempo de mercado.
- Piezas de logica de negocio genuinamente nueva (catalogo dinamico con
  elegibilidad, grupos + actividad primaria/secundaria, Diario de Campo
  enriquecido, reconciliacion Programacion-vs-Reportado, Bitacora): la
  aceleracion es mas modesta (~20%-25%) porque el cuello de botella es
  diseño de reglas de negocio, pruebas con datos reales y iteracion de UX,
  no la escritura de codigo en si.
- QA/pruebas de campo: aceleracion minima, sigue requiriendo verificacion
  manual con supervisores reales.

Estimado resultante: **~8 – 9 semanas de trabajo real** (vs. 9,5-15 semanas
de referencia de mercado sin asistencia de IA) — una reduccion de ~30% sobre
el punto medio del rango de mercado, no una reduccion dramatica, porque la
mayor parte del esfuerzo de este modulo especifico esta en reglas de negocio
y flujos de reconciliacion (elegibilidad, primaria/secundaria, cierre
administrativo), no en trabajo repetitivo donde la IA rinde mas.
