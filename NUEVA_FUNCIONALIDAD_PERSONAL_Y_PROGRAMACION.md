# Nueva Funcionalidad: Gestion De Personal Y Programacion De Actividades

Estado del documento: **borrador vivo, en levantamiento**. Se ira completando a
medida que lleguen Excel, pantallazos y detalles adicionales. No implementar
codigo a partir de este documento hasta que el alcance quede cerrado (ver
seccion 11, Pendientes).

Fecha de inicio del levantamiento: 2026-09-05
Ultima actualizacion: 2026-09-11 (se agrega la seccion 13 documentando el
prototipo visual navegable construido para revisar el diseño con el cliente,
se resuelve visualmente el tercer estado de certificados pendiente en 4.4/11,
y se deja registrada una discrepancia sin resolver entre el prototipo y la
regla de elegibilidad primaria/secundaria de la seccion 6 — ver seccion 13.4)

Actualizacion anterior: 2026-09-10 (se corrige una hipotesis equivocada sobre
"cantidad de columnas", se confirman nombres de categorias, fechas de
empleado, independencia total del sistema de roles actual, y se resuelve
si "grupo" es catalogo o agrupador visual — ver secciones 3, 4, 4.3, 4.4, 6,
7, 8, 10 y 11)

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
   automaticamente la BITACORA mensual (seccion 8) y el DIARIO DE CAMPO
   (seccion 5) — no son formularios de captura independientes, son vistas/
   consolidados sobre las mismas actividades.
```

Es decir: **la actividad es una sola entidad que atraviesa un ciclo de vida
de 3 actores** (supervisor-crea -> supervisor-ejecuta -> administrativo-
cierra), y tanto la Bitacora como el Diario de Campo son proyecciones/
reportes sobre esas actividades, no tablas separadas que se llenan a mano
por aparte.

## 3. Autenticacion Y Roles

- Habra un **login administrativo diferente** al login actual. **Confirmado
  2026-09-10**: este login, sus roles y sus permisos son **completamente
  independientes** del sistema de roles/permisos del modulo de reportes
  preventivos existente (`Role.php`, `RoleModulePermission`) — no se
  reutiliza ni se comparte con ese sistema.
- El rol `superadmin` es el unico rol existente que se mantiene con acceso a
  esta area nueva. Los demas usuarios del modulo (supervisores,
  administrativos) no necesitan saber que existe esa cuenta: no aparece en
  ningun catalogo, selector ni listado visible para ellos (confirmado
  2026-09-09).
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

- Informacion personal (**parcialmente confirmado 2026-09-10**: cedula,
  fecha de nacimiento, fecha de ingreso/primer dia de trabajo (para calcular
  antiguedad), fecha de inicio del ultimo contrato y fecha de terminacion.
  El usuario indico "luego miramos mas" — el resto de campos exactos sigue
  **pendiente**, vendran en Excel).
- **Checkbox "tiene usuario de acceso" (confirmado 2026-09-09)**: no todo
  empleado tiene login al modulo administrativo — se habilita o inhabilita
  usuario y contraseña por empleado mediante una casilla, en vez de un
  proceso de registro separado.
- **Checkbox "hace parte de la Bitacora" (confirmado 2026-09-10)**: bandera
  independiente al crear el empleado. Algunos administrativos (ej. personal
  de oficina que da ordenes de trabajo pero no registra horas de campo) no
  deben aparecer nunca en la Bitacora mensual, aunque tuvieran horas
  registradas — ver regla combinada en seccion 8.
- Estado activo/inactivo (activar/inactivar trabajadores, sin necesariamente
  eliminar el registro).
- Iconos visuales indicando que certificaciones/inducciones vigentes tiene
  cada empleado (ver seccion 4.4) — se observan en `diario sept.pdf` columnas
  abreviadas por persona/actividad (ej. `siso`, `Rescatista`, `Sup`, `Ing`,
  y otras sin encabezado completo visible) que apuntan a este mismo concepto
  de roles/certificaciones marcadas por persona.
- **Nickname/apodo corto** (confirmado 2026-09-09): cada empleado necesita un
  identificador corto para mostrarse en la Programacion/Diario de Campo en
  vez del nombre completo (en las capturas reales aparecen como "Brayan C",
  "Brayan P", "Brayan Q" para diferenciar homonimos del mismo grupo). Los
  supervisores usan la misma idea pero como un **codigo interno abreviado**
  en la columna "Responsable" del Diario de Campo (ej. "Luis Fernando" ->
  algo como "LF"). **Confirmado 2026-09-09**: el codigo/nickname se escribe
  **manualmente** al crear el empleado, no se autogenera a partir del
  nombre — largo libre, de 1 a varias letras segun se necesite para
  diferenciar homonimos.

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

- **Categoria/cargo del empleado** — catalogo distinto del rol de login de
  la seccion 3 (**confirmado 2026-09-09**). Categorias identificadas:
  **Campo/Operario** (nombre exacto **pendiente**, el cliente puede usar
  otro termino), **Supervisor**, **SISO** y **Administrativo** — supervisor
  y SISO son categorias distintas entre si, aunque ambas puedan coincidir
  con un rol de login de la seccion 3. Estas categorias SI se ven
  reflejadas en `diario sept.pdf`, columnas `mec`/`aux`/`sup y siso x hora`.
  **Confirmado 2026-09-10**: el calculo de costo por hora segun estas
  categorias es **un tema aparte, fuera del alcance de este modulo**.
  **Modelo de visibilidad en la Programacion, con nombres confirmados
  2026-09-10**: las 4 categorias de cargo se agrupan en **2 categorias
  superiores** — **"Campo"** y **"Administrativos"** — que organizan el
  selector de empleados de la Programacion en **2 columnas**:
  - Columna "Campo": empleados con categoria Campo/Operario.
  - Columna "Administrativos": empleados con categoria Administrativo,
    Supervisor o SISO (el cargo especifico "Administrativo" queda anidado
    dentro de esta categoria superior del mismo nombre — cuidado con
    confundir ambos niveles al implementar).
  Ademas, cada usuario tiene un **habilitar/inhabilitar individual** para
  aparecer en el selector de la Programacion — no es una exclusion fija por
  categoria (un Administrativo puntual podria habilitarse si hace falta).
  Por defecto se espera que los Administrativos permanezcan inhabilitados,
  pero la regla real la define ese toggle por persona, no la categoria por
  si sola.
- **CRUD de Certificados generales, campos confirmados (2026-09-09)**:
  nombre, estado activo/inactivo, e icono (ver bullet de iconos en la
  seccion 4.4). Cada certificado creado aqui aparece automaticamente como
  columna dinamica en la vista/formulario del empleado, donde se adjunta el
  archivo real via R2 con su fecha de obtencion/vencimiento (mismo
  mecanismo que la seccion 4.1).
- Planta de trabajo.
- Posiblemente mas catalogos similares (**pendiente**, el usuario indico
  "quiza mas con sus propios minicruds" sin especificar cuales).

### 4.4 Empresas Y Catalogo Dinamico De Inducciones/Certificaciones

Aclarado por el usuario el 2026-09-07 — esta es una pieza central del modulo,
mas compleja que un catalogo simple:

- Existe un catalogo de **empresas** (sitios cliente de mano de obra: Argos,
  Corona, Calidra, etc. — ver nota de terminologia en seccion 1).
- **Empresa por defecto (confirmado 2026-09-09)**: al crear una empresa se
  puede marcar como la "general" (una sola a la vez), para que se
  preseleccione automaticamente en el campo Empresa de la Programacion —
  en la practica una misma empresa se repite en casi todas las filas del
  dia, y esto evita reseleccionarla cada vez.
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
  empleados de la Programacion (seccion 6), un empleado **no es
  seleccionable** para una actividad de una empresa determinada si le falta
  alguna induccion **obligatoria de esa empresa**, o si la tiene vencida.
  Esto es una validacion de negocio real (cumplimiento/seguridad), no solo
  un filtro visual. **Confirmado 2026-09-09**: esta regla de bloqueo aplica
  unicamente a las inducciones por empresa, NO a los certificados generales
  (ver bullet siguiente).
- **Certificados generales, informativos, no bloqueantes (confirmado
  2026-09-09)**: a diferencia de las inducciones por empresa, los
  certificados generales del empleado (alturas, espacios confinados,
  seccion 4.1) NO bloquean su seleccion en la Programacion aunque esten
  vencidos o falten. Si deben mostrar una **alerta visual** cuando esten
  vencidos, distinta del icono normal de posesion. **Confirmado
  2026-09-10**: el icono con animacion de parpadeo/pulso + tooltip es
  especificamente para **vencido** (el empleado si tiene el certificado
  pero ya caduco). **Resuelto visualmente en el prototipo (ver seccion 13)**:
  el tercer estado ("nunca obtenido") usa un icono gris apagado
  (`shield-off`, sin parpadeo), distinto del verde vigente y del ambar
  parpadeante de vencido — pendiente que el cliente valide este tratamiento
  de 3 estados antes de darlo por definitivo.
- Se muestran **iconos** junto a cada empleado indicando que certificaciones
  tiene vigentes (ej. icono de alturas, icono de espacios confinados),
  tanto en el CRUD de empleados como, presumiblemente, en el selector de la
  Programacion.
- **Correccion 2026-09-10**: la frase "la cantidad de columnas depende de la
  cantidad de roles", documentada originalmente en la seccion 6
  (Programacion), en realidad se referia a **este CRUD de Empleados**: la
  cantidad de columnas dinamicas en la vista/formulario del empleado
  depende de cuantos certificados e inducciones existan en estos catalogos,
  no de "roles" ni de la pantalla de Programacion.

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
| Fecha del trabajo | Automatica | **Confirmado 2026-09-09**: es informativa, el supervisor no la edita manualmente |
| Empresa | Supervisor (Programacion) | Ej. ARGOS, CORONA — ver seccion 4.4. Una empresa puede quedar marcada como "por defecto" y preseleccionarse sola |
| Equipo | Supervisor (Programacion), texto libre manual | Ej. "Rioclaro", "TP1 Trituradora", "Taller San Luis" — **confirmado 2026-09-09**: no es un catalogo, se escribe manualmente (el usuario indico "luego miramos si creamos algo", queda abierto a futuro) |
| Proceso | Supervisor (Programacion o al ejecutar) | Ej. "Cambiar banda", "SST", "Coordinador" |
| Actividad — **version programada** (confirmado 2026-09-09) | Supervisor (Programacion) | Descripcion general/corta al agendar (ej. "Realizar cambio de cauchos") — texto libre |
| Actividad — **version ejecutada** (confirmado 2026-09-09) | Supervisor (app, al ejecutar) | Descripcion tecnica larga y detallada de lo realmente realizado — texto libre extenso (ej. "Se cortan cauchos y se fabrican ojos chinos en estos para su facil calibracion..."). Es un **segundo texto que se guarda aparte, no reemplaza la version programada** — ambas quedan disponibles. Filas como "TRANSPORTE AEROVAN 463 GRUPO 2 ARGOS", "Descanso", "Incapacidad", "Licencia" o "Permiso" NO son un tipo de actividad especial — son actividades normales, tipicamente marcadas como secundarias, donde este mismo campo describe la situacion |
| Nombre de las personas | Viene fijo de la Programacion (confirmado 2026-09-09) | Lista de personas asignadas en la Programacion — el Diario de Campo no permite reasignar personas distintas, solo la muestra. Puede incluir varias personas en una sola fila (ej. una fila de "Descanso" con 6-11 nombres) |
| N° de personas | Automatico (confirmado 2026-09-09) | Se calcula a partir de cuantas personas se seleccionaron en "Nombre de las personas", no se digita a mano |
| Horas | **Valor final mostrado** (confirmado 2026-09-09) | Nunca muestra la hora originalmente programada: muestra la hora **corregida por el administrativo** si existe, o si no la **reportada por el supervisor en la app** al cierre del dia — misma regla de "hora final" que la Bitacora (seccion 8). Es opcional, no obligatorio (ver seccion 7) |
| Jornada | Supervisor (ejecucion) | DIURNO / NOCTURNO |
| Comentarios | Administrativo (cierre) | |
| ZCOM, Linea, OT SAP, Acta Entrega Solicitada, WE (y al menos 1 columna adicional vista en captura, encabezado cortado/ilegible) | Administrativo (cierre), **texto libre manual (confirmado 2026-09-09)** | Codigos de integracion/seguimiento con el sistema del cliente (ej. SAP de Argos). **Decision de alcance**: por ahora se implementan como campos de texto libre sin validar ni significado de negocio definido — el usuario indico explicitamente "aun no sabemos que son, pongamosle tipo texto". Sigue pendiente si a futuro deben volverse configurables por empresa |
| `mec`, `aux`, `sup y siso x hora` | Confirmado presente en el Excel real (2026-09-09) | Columnas de tarifa/costo por categoria de cargo (seccion 4.3) — **sigue pendiente confirmar si el calculo de costo entra en el alcance de este modulo** |
| Columnas de certificacion por persona en la actividad (ej. `siso`, `Rescatista`, `Sup`, `Ing`, y otras abreviadas sin confirmar) | Deriva de seccion 4.4 | Marca que certificaciones cubren las personas asignadas a esa actividad especifica |

**Pendiente explicito**: el significado exacto de `ZCOM`, `OT SAP`, `Acta
Entrega Solicitada` y `WE` — parecen campos de integracion/trazabilidad
especificos del contrato con Argos (posiblemente numeros de orden de trabajo
SAP), no necesariamente aplicables a todas las empresas del catalogo. Definir
si son campos fijos del modulo o si tambien deben ser configurables por
empresa (siguiendo el mismo principio de flexibilidad de la seccion 4.4).

## 6. Programacion De Actividades (Web, Supervisores)

- La crea el **supervisor** desde la web.
- **Ventana de edicion (confirmado 2026-09-09)**: el supervisor solo puede
  modificar la Programacion del **dia actual o el dia inmediatamente
  anterior**. No puede editar programaciones mas antiguas que eso.
- **Organizada por grupos**: las actividades se agrupan (ej. una cuadrilla o
  equipo de trabajo), y al exportar/enviar la programacion (ej. por
  WhatsApp), el orden de salida respeta el orden de grupo.
  **Confirmado 2026-09-09**: el listado se reagrupa/reordena automaticamente
  por grupo en tiempo real al crear una actividad nueva (una actividad del
  grupo 1 sube directo al bloque del grupo 1, no se ordena manualmente
  despues). Ademas existe un **boton "Organizar"** que reordena todo el
  listado por grupo bajo demanda, y el reposicionamiento **manual** tambien
  esta disponible, especialmente para actividades que aun no tienen grupo
  asignado.
  **Resuelto 2026-09-10**: "grupo" es **solamente un agrupador visual** de
  la tabla de programacion, no un catalogo propio (sin nombre ni supervisor
  fijo asociado como entidad independiente).
- **Actividad primaria vs. secundaria**: cada persona puede tener **una sola
  actividad primaria** en el periodo (ej. el dia), pero **N actividades
  secundarias**. Al crear una actividad, se indica si es primaria o
  secundaria para las personas involucradas. Esto implica una validacion:
  el sistema debe impedir asignar una segunda actividad primaria a la misma
  persona en el mismo periodo.
  **Confirmado 2026-09-09**: la regla de elegibilidad por inducciones de
  empresa (seccion 4.4) solo aplica a la actividad **primaria**. Las
  actividades **secundarias** no se filtran por certificados ni
  inducciones, porque normalmente son "novedades" (descanso, incapacidad,
  transporte, permisos) que no implican trabajo de campo real en la
  empresa.
- Selecciona **empresa** y **area** (combobox).
- Selecciona **uno o varios empleados**, mostrados en **2 columnas por
  categoria** (nombres confirmados 2026-09-10, ver seccion 4.3):
  **"Campo"** y **"Administrativos"** (esta ultima agrupa cargo
  Administrativo, Supervisor y SISO habilitados). Se combinan estos
  filtros:
  - **Habilitado/inhabilitado por usuario** (seccion 4.3): cada persona
    tiene un toggle individual que controla si aparece en este selector,
    independiente de su categoria.
  - **Por elegibilidad de empresa** (seccion 4.4): solo aplica a la
    actividad **primaria** (ver bullet de primaria/secundaria arriba). No
    aparecen o no son seleccionables los empleados sin las inducciones
    obligatorias vigentes que exige la empresa elegida. Los certificados
    generales NO aplican a este filtro (son informativos, ver seccion 4.4).
- **Correccion 2026-09-10**: el punto "la cantidad de columnas depende de la
  cantidad de roles" no era sobre esta pantalla — el usuario aclaro que se
  referia al **CRUD de Empleados** (seccion 4.4): la cantidad de columnas
  dinamicas ahi depende de cuantos certificados e inducciones existan en
  esos catalogos, no de roles ni de la Programacion.
- Horas de trabajo posible/estimado: campo manual, **opcional, no
  obligatorio** (confirmado 2026-09-09 — ver tabla de la seccion 5).
- Cada actividad tiene un **supervisor asignado**, mostrado con su codigo
  interno abreviado en la columna "Responsable" (ver nickname en seccion 4).
- Puede haber **varias actividades por dia**: un boton "+" agrega una fila
  nueva de actividad, se llena y se guarda.
- **Pantallazos reales recibidos e incorporados el 2026-09-09** (capturas de
  `DIARIO DE CAMPO TRABAJOS MAN & TEC.xlsx`, columnas FECHA/EMPRESA/EQUIPO/
  ACTIVIDAD/PERSONAS/HORAS/JORNADA/NOMBRE DE LAS PERSONAS/RESP/GRUPO) — ver
  tabla de campos actualizada en la seccion 5.

## 7. Registro De Horas Y Evidencias (App Android, Supervisores)

Funcionalidad **nueva** dentro de la app Android existente, para el rol
supervisor (a diferenciar del rol inspector ya existente en la app):

**Alcance de este repositorio (confirmado 2026-09-09)**: la app Android en
si se desarrolla en un repositorio aparte (Android Studio), fuera de este
repo Laravel. Lo que corresponde construir y **documentar** desde aqui son
las **APIs** que esa app consume — no la interfaz movil.

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
- **Confirmado 2026-09-10**: la API debe incluir la **fecha de la
  Programacion** a la que corresponde el registro de horas/evidencias.
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

- **Navegacion (confirmado 2026-09-10)**: a diferencia de la Programacion
  (que navega por dia), la Bitacora navega por **mes dentro de un año**,
  con controles para avanzar/retroceder tanto de mes como de año.
- Una fila por dia del mes (1 a 30/31), una columna por empleado —
  **confirmado 2026-09-09**: el encabezado de columna usa el **nickname**
  del empleado (seccion 4), no el nombre completo.
- **Regla de que empleados aparecen como columna (confirmado 2026-09-10)**:
  se combinan dos condiciones, ambas obligatorias — (a) el empleado tiene
  activo el checkbox "hace parte de la Bitacora" (seccion 4), y (b) registra
  al menos una hora en el mes que se esta viendo. Un empleado con el
  checkbox activo pero sin horas ese mes especifico tampoco aparece esa
  vista (aunque si aparezca en otro mes donde si tenga horas); un empleado
  sin el checkbox nunca aparece, sin importar si tiene horas o no.
- **Trazabilidad de 3 horas por celda (confirmado 2026-09-09)**: cada celda
  dia/empleado no guarda un solo numero, guarda 3 valores con origen
  distinto:
  1. **Programada** — la hora que el supervisor puso al crear la actividad
     en la Programacion (seccion 6).
  2. **Reportada** — la hora que el supervisor registro en campo al cierre
     del dia (app Android, seccion 7).
  3. **Corregida** — la hora que un administrativo ajusto manualmente, si
     hizo falta (opcional, no siempre existe).
  Los 3 valores se consultan en un **tooltip** al pasar el mouse sobre la
  celda, en este orden fijo: **izquierda = programada, centro = reportada
  por el supervisor, derecha = corregida por el administrativo**. Ninguna
  correccion borra los valores anteriores, todos quedan disponibles ahi.
- **Valor final mostrado en la celda (confirmado 2026-09-09)**: por defecto
  es la hora **reportada por el supervisor**; si existe una correccion
  administrativa, esa pasa a ser la hora definitiva que se muestra.
- **Alerta visual** si la hora reportada queda en blanco/0: debe verse en un
  **color distinto** para que el administrativo detecte que hay una
  novedad pendiente de resolver.
- El administrativo puede dejar **comentarios**: se agregan haciendo **clic
  en la celda, que abre un modal** (confirmado 2026-09-09 y reconfirmado
  2026-09-10 como el unico mecanismo para agregar un comentario); luego se
  consultan como **tooltip** sobre el dato (el Excel actual ya usa
  comentarios de celda de Excel exactamente con este proposito, ej.
  "jornada nocturna-1 ED-11EN"). **Resuelto 2026-09-10**: el comentario **si
  comparte el mismo tooltip** que las 3 horas — si la celda tiene
  comentario, se muestra junto con las horas; si no tiene comentario, el
  tooltip solo muestra las 3 horas.
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
todavia. **Nota 2026-09-09**: el usuario anticipa que probablemente no se
usen mucho en la practica, pero pide construirlos igual "por si acaso" —
no cambia el alcance, solo indica prioridad baja.

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
- **Resuelto 2026-09-10** (ver seccion 3): "supervisor" y "administrativo"
  son roles de un esquema de autenticacion **completamente aparte** del
  sistema de roles actual (`app/Models/Role.php`, `RoleModulePermission`) —
  no se reutiliza ni se comparte con ese sistema.
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
  puede autocorregirse o solo el administrativo corrige?) — el usuario
  reconfirmo que aun no sabe, lo definira mas adelante (2026-09-10).
- Campos completos de informacion personal del empleado (**avance
  2026-09-10**: cedula, nickname, checkbox de usuario/login, fecha de
  nacimiento, fecha de ingreso, fecha de inicio de ultimo contrato y fecha
  de terminacion ya confirmados, ver seccion 4; el usuario indico "luego
  miramos mas" — el resto sigue abierto).
- Nombre exacto que usa el cliente para la categoria "Campo/Operario" de la
  seccion 4.3 — el usuario respondio "no se" (2026-09-10).
- Que otros mini-CRUDs de catalogos de apoyo hacen falta ademas de
  categoria/cargo, planta de trabajo y certificados (seccion 4.3) — el
  usuario respondio "no se si necesitemos mas" (2026-09-10).
- Lista completa de tipos de documento/certificacion a adjuntar, y cuales
  son genericos del empleado vs. cuales son exigidos por empresa especifica
  (el CRUD de Certificados como tal ya quedo definido, ver seccion 4.3) —
  el usuario indico que "se iran agregando luego" (2026-09-10).
- Tercer estado visual para certificados generales: distinguir "nunca
  obtenido" de "vencido" en el selector de la Programacion — **propuesta ya
  prototipada** (icono gris apagado vs. ambar parpadeante, ver seccion 4.4 y
  13.4), falta que el cliente la valide como definitiva.
- Campos adicionales que debe exponer/aceptar la API de registro de
  horas/evidencias, mas alla de la fecha de la Programacion ya confirmada
  (seccion 7) — la app Android en si se construye en otro repositorio
  (confirmado 2026-09-09).
- Significado exacto de `ZCOM`, `OT SAP`, `Acta Entrega Solicitada`, `WE` y
  una columna adicional sin identificar en el Diario de Campo —
  **reconfirmado 2026-09-10 que hoy no se sabe**; mientras tanto se
  implementan como texto libre (ver seccion 5).
- Si la logica de cuota mensual (182 h) y "extras" del Excel actual debe
  replicarse en la Bitacora web — **reconfirmado 2026-09-10 que sigue sin
  definir**.
- Detalle funcional de los 2 dashboards comparativos (seccion 9) — baja
  prioridad esperada por el usuario (se construyen igual, 2026-09-09); la
  pregunta especifica de que debe mostrar cada uno quedo sin resolver el
  2026-09-10 porque no se formulo con claridad, hay que retomarla.

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

## 13. Prototipo Visual (Referencia De Diseño)

**Que es y que no es**: a partir del 2026-09-10 se construyo un prototipo
navegable de las pantallas principales de este modulo, **sin funcionalidad
real** (sin backend, sin base de datos, sin autenticacion) — vive en una ruta
aparte del sistema (prefijo deliberadamente no obvio, fuera del middleware
`auth`), con datos de ejemplo tomados directamente de los Excel reales de la
seccion 10. Su unico proposito es **validar visualmente** el diseño con el
cliente antes de programar en serio; no cambia la regla de la cabecera de
este documento (no implementar codigo real hasta que el alcance quede
cerrado, seccion 11). Esta seccion documenta que muestra ese prototipo hoy,
para poder comparar contra las secciones 3-9 y detectar que falta por
definir o proyectar.

### 13.1 Estructura comun (sidebar/layout)

**Confirmado 2026-09-11**: el sidebar de este prototipo debe ser **identico,
visual y funcionalmente**, al sidebar real de la plataforma
(`resources/views/components/admin/sidebar.blade.php` +
`layouts/admin.blade.php`), no una version simplificada aparte. Se replico:

- Fondo blanco, acento naranja de marca (`#d55b20`) en el item de menu
  activo, misma tipografia/espaciados que el sidebar real.
- Colapso de escritorio (boton con flecha, persistido en `localStorage`) y
  drawer movil off-canvas con boton de hamburguesa — misma mecanica Alpine.js
  que la plataforma real, con una clave de `localStorage` propia
  (`preview_personal_*`) para no mezclar su estado con el sidebar real (misma
  app, mismo origen/navegador).
- Los 5 items de menu del prototipo (Login, Programacion, Diario de Campo,
  Bitacora mensual, Empleados) son especificos de este modulo — el sidebar
  real tiene items distintos segun el rol autenticado (Dashboard,
  Indicadores, Clientes, etc., ver seccion 3 sobre independencia de roles).

### 13.2 Pantalla por pantalla

- **Login** (seccion 3): formulario con campo **Usuario** (no email, igual
  convencion que el login actual del sistema) + contraseña, panel lateral de
  marca. Botón "Ingresar" navega directo a Programación (sin autenticar,
  es mockup).
- **Programación** (seccion 6): navegación por **calendario** (día actual
  por defecto, se puede saltar a cualquier fecha — hoy y ayer se muestran
  como "Editable", el resto "Solo lectura", igual que la regla de ventana de
  edición confirmada). Tabla única agrupada visualmente por "Grupo" (evita
  desalineación de columnas entre grupos). Modal "Nueva actividad" con
  Empresa/Área/Grupo/Equipo/Jornada/Responsable/Actividad/Horas, selector de
  "Actividad primaria para", y selector de personas en 2 columnas
  (Campo/Administrativos) filtrado por elegibilidad de inducciones —
  ver discrepancia abierta en 13.4. Botón "Copiar como imagen" (exporta la
  tabla del día a PNG vía `html2canvas-pro`, con copiado a portapapeles o
  descarga como respaldo en móvil).
- **Diario de Campo** (seccion 5): tabla ancha con FECHA/EMPRESA/EQUIPO/
  ACTIVIDAD (versión programada en negrita + versión ejecutada expandible
  "ver detalle ejecutado")/PERSONAS/N°/HORAS/JORNADA/Comentarios/ZCOM/Línea/
  OT SAP/Acta entrega/WE, con scroll contenido en el div (barra visible
  horizontal y vertical, igual tratamiento que Bitácora).
- **Bitácora mensual** (seccion 8): navegación por mes/año. Columnas de
  empleado derivadas de los mismos registros de Empleados (nickname real,
  no una lista independiente), filtradas por "hace parte de la Bitácora" +
  horas registradas ese mes. Celda con trazabilidad de 3 horas
  (programada/reportada/corregida) en tooltip, modal de corrección
  administrativa, alerta ámbar si no reportó, totales de horas/cuota/extras
  en el pie. Scroll contenido con barra visible en ambos sentidos.
- **Empleados** (seccion 4): tabla con Acciones (editar + toggle
  Activo/Inactivo)/Nombre completo/Nickname/Abreviatura/Cédula/Categoría/
  Nacimiento (+edad calculada)/Ingreso/Contrato desde-hasta/Usuario/
  Bitácora/Accesos (tarjeta hover con estado por empresa)/Certificados
  (iconos de 3 estados: vigente/vencido/nunca obtenido, ver 4.4). Campana de
  notificaciones (contratos por vencer, certificados por vencer, cumpleaños
  próximos, todo con umbral de "menos de 1 mes" salvo cumpleaños a 7 días).
  CRUDs en modal para "Empresas e inducciones" (con edición inline de
  nombres, marcar obligatoria, marcar empresa por defecto) y "Certificados"
  (nombre + ícono seleccionable de una librería curada).

### 13.3 Decisiones de diseño validadas por el prototipo

Cosas que no estaban resueltas antes de prototipar y que el ejercicio dejó
zanjadas (quedan aquí como referencia, no se repiten en la seccion 11):

- Tercer estado visual de certificados (13.1/4.4, arriba).
- El icono de alerta debe ser el mismo ícono del certificado/inducción (no
  un signo de exclamación genérico), con animación de parpadeo solo para
  "vencido" — "no_posee" queda neutro, sin alarmar.
- Patrón de exportar-como-imagen para compartir la Programación por
  WhatsApp (sección 9 original solo pedía el botón, sin detalle de mecánica)
  resuelto como: `html2canvas` sobre el bloque exportable, con
  copiar-a-portapapeles como primario y descarga como respaldo si el
  navegador no soporta `ClipboardItem` con imágenes (típico en móvil).

### 13.4 Que falta por proyectar (comparar contra secciones 3-9)

Brechas conocidas entre lo ya prototipado visualmente y lo documentado — o
puntos donde el prototipo tomó una decisión que **contradice** lo ya
confirmado y necesita reconciliarse con el cliente antes de cerrar alcance:

- **Discrepancia a resolver (no asumir)**: la sección 6 confirma
  (2026-09-09) que la regla de elegibilidad por inducciones **solo aplica a
  la actividad primaria**, y que las secundarias no se filtran. El
  prototipo, por instrucción explícita del 2026-09-11, hoy **oculta
  completamente** de la lista de selección a cualquier empleado sin
  inducción vigente — sin distinguir primaria de secundaria. Falta
  confirmar con el cliente cuál de las dos reglas es la definitiva antes de
  programar la validación real.
- **Sin prototipar aún**: subida real de documentos a R2 con fecha de
  obtención/vencimiento (sección 4.1) — el prototipo solo muestra el
  ícono de estado final, no el formulario de carga.
- **Sin prototipar aún**: modal de histórico de documentos/contratos
  anteriores de un empleado (sección 4.2).
- **Sin prototipar aún**: mini-CRUD de "Categoría/cargo" en sí (sección
  4.3) — en el prototipo la categoría es un select fijo de 2 opciones
  (Campo/Administrativos) dentro del formulario de empleado, no un catálogo
  gestionable con las 4 categorías reales (Campo/Operario, Supervisor,
  SISO, Administrativo).
- **Sin prototipar aún**: mini-CRUD de "Planta de trabajo" (sección 4.3).
- **Sin prototipar**, y fuera de alcance de un mockup web: la app Android
  de registro de horas/evidencias (sección 7) y los 2 dashboards
  comparativos (sección 9) — quedan pendientes de una fase de diseño propia
  cuando se retomen (sección 11).
- El prototipo asume una sola empresa "por defecto" (ARGOS) preseleccionada
  y localStorage separado del real — comportamiento correcto solo dentro del
  propio prototipo, no algo que deba migrarse literalmente a la
  implementación real (ahí "empresa por defecto" es un dato de negocio, no
  una preferencia de UI).
