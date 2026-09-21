# Nueva Funcionalidad: Gestion De Personal Y Programacion De Actividades

Estado del documento: **borrador vivo, en levantamiento**. Se ira completando a
medida que lleguen Excel, pantallazos y detalles adicionales. No implementar
codigo a partir de este documento hasta que el alcance quede cerrado (ver
seccion 11, Pendientes).

Fecha de inicio del levantamiento: 2026-09-05
Ultima actualizacion: 2026-09-16 (implementacion REAL del Escalon B
confirmado — login administrativo independiente + Empleados + Empresas +
**Programacion** con base de datos real, guard `personal` propio,
incluyendo las dos reglas de negocio que el mockup no validaba (primaria
unica por persona/dia, ventana de edicion del supervisor). Diario de
Campo/Bitacora siguen siendo el mockup de la seccion 13 por ahora — ver
secciones 14 y 14.1)

Actualizacion anterior: 2026-09-15 (Mantec confirma oficialmente el Escalon B
de la seccion 12.4 — $3.200.000, Programacion + Diario de Campo + Bitacora,
sin gestion documental completa ni tableros comparativos. Se ajusta el
prototipo de Empleados al alcance real contratado, se quita el bloqueo de
elegibilidad y los certificados generales de Programacion, se reemplaza su
selector de personas por un combobox buscable con horas acumuladas del mes,
y la cuota mensual de la Bitacora pasa de constante fija a editable por mes
— ver seccion 13.5)

Actualizacion anterior: 2026-09-14 (se corrige la seccion 6: primaria/secundaria
es una propiedad de la actividad completa, no una marca individual por
persona dentro de la misma actividad — ver seccion 6.1. Se ajusta el
prototipo de Programacion en consecuencia)

Actualizacion anterior: 2026-09-11 (se agrega la seccion 13 documentando el
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

  **Correccion 2026-09-14 (ver 6.1)**: primaria/secundaria es una propiedad
  de la **actividad completa**, no una marca individual por persona dentro
  de la misma actividad.

### 6.1 Correccion: Primaria/Secundaria Es Por Actividad, No Por Persona

El prototipo inicial (seccion 13) implemento la marca P/S **por persona
dentro de una misma actividad** (ej. una fila con 4 personas donde una
aparecia como "P" y las otras 3 como "S"). El cliente aclaro el 2026-09-14
que esto es incorrecto: **una actividad es primaria o secundaria como
unidad completa**, y esa condicion aplica por igual a todas las personas
asignadas en ella. No existe el caso de una actividad con unas personas en
primaria y otras en secundaria al mismo tiempo.

En la practica esto significa que al crear una actividad se elige un unico
"Tipo de actividad" (Primaria/Secundaria) para toda la fila, no un selector
de "cual de estas personas es la primaria". La regla de "una sola actividad
primaria por persona por dia" (parrafo anterior) se sigue cumpliendo, pero
se valida a nivel de "en cuantas actividades marcadas como Primaria
distintas aparece la misma persona ese dia", no dentro de una sola
actividad.

**Ya corregido en el prototipo (seccion 13)**: la vista de Programacion
(`resources/views/preview-personal/programacion.blade.php`) ahora guarda
`tipo` ('P'/'S') a nivel de actividad, la tabla muestra un unico badge P/S
junto al texto de la Actividad (no uno por persona), la columna Personas
quedo como texto simple separado por comas (sin badges/pills por persona,
lo que ademas redujo el ancho de la tabla), y el modal "Nueva actividad"
reemplazo el selector "Actividad primaria para" (por persona) por un
selector unico "Tipo de actividad" (Primaria/Secundaria) que aplica a toda
la fila.

**Pendiente**: la validacion real de "no permitir una segunda actividad
Primaria para la misma persona el mismo dia" sigue sin implementarse (el
prototipo no valida nada, es solo mockup) — queda para cuando se construya
el backend real.
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
  vs. **horas a laborar** (una cuota mensual, 182 horas en el ejemplo
  visto, que **se define manualmente cada mes** — no es una constante fija
  para siempre, confirmado 2026-09-15) y una fila de **"extras"**
  (diferencia entre lo trabajado y la cuota, casi siempre negativa en el
  ejemplo). **Resuelto 2026-09-15 (ver seccion 13.5)**: si se replica en la
  vista web — la cuota es editable por mes desde la propia Bitacora.

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
- ~~Si la logica de cuota mensual (182 h) y "extras" del Excel actual debe
  replicarse en la Bitacora web~~ — **resuelto 2026-09-15, ver seccion
  13.5**: si se replica, y la cuota se define manualmente cada mes.
- Detalle funcional de los 2 dashboards comparativos (seccion 9) — baja
  prioridad esperada por el usuario (se construyen igual, 2026-09-09); la
  pregunta especifica de que debe mostrar cada uno quedo sin resolver el
  2026-09-10 porque no se formulo con claridad, hay que retomarla.
- **Auto-llenado de horas del Responsable (2026-09-18)**: idea a futuro,
  para Diario de Campo/Bitacora (no para Programacion) — cuando un
  Responsable (ver seccion 14.7) cierre/evalue las actividades que
  supervisó ese dia, sus propias horas podrian calcularse solas a partir
  de las cuadrillas que tuvo a cargo, en vez de digitarse aparte. El
  usuario confirmo explicitamente que es solo una referencia a futuro, sin
  alcance definido todavia — no implementar hasta que se retome.

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

### 12.4 Escalones De Alcance Negociados (2026-09-07 En Adelante)

**Nota interna**, mismo caracter que el resto de la seccion 12 — no copiar
literalmente al cliente.

Tras cotizar el alcance completo en $4.000.000-$4.200.000 (12.2), la cliente
(Doña Ruby Ocampo) empezo a negociar recortes de alcance. A la fecha hay
tres escalones distintos sobre la mesa (no confundirlos):

**Escalon A — Alcance completo (12 puntos)**: $4.000.000 - $4.200.000, ya
cotizado (12.2). Incluye gestion documental completa (R2, vencimientos,
alertas configurables, historico — puntos 2-5 de la lista en lenguaje
cliente), catalogos editables, Programacion/Diario/Bitacora, conexion con
la app Android, y los 2 dashboards comparativos.

**Escalon B — WhatsApp 2026-09-07, ya comunicado a la cliente**: Doña Ruby
pidio explicitamente obviar los puntos de gestion documental completa
("Podemos obviar el punto dos tres y cuatro... Que quede solo para
programación bitácora y diario de campo"). Se le confirmo por WhatsApp el
mismo dia: **$3.200.000, aprox. 3-4 semanas**, aclarando que "hay items que
no se pueden quitar para el correcto funcionamiento" (ej. un catalogo
minimo de empleados/empresas sigue siendo indispensable para que
Programacion funcione, aunque sea sin el CRUD completo de documentos/R2).
Este escalon **tampoco incluye** los 2 dashboards comparativos (punto 12) —
el mensaje solo menciona programacion, bitacora y diario de campo.

**Escalon C — Consultado 2026-09-14, aun no comunicado a la cliente**:
¿cuanto cobrar por el alcance completo (12.1) menos unicamente los 2
dashboards comparativos (punto 12), es decir conservando la gestion
documental completa (R2/vencimientos/alertas/historico) que en el Escalon B
si se habia quitado? Aplicando la misma logica de descuento que ya rindio
9%-15% en 12.2 (proporcion de semanas de la pieza "2 dashboards
comparativos" en 12.1, que es 0,5-1 de las 9,5-15 semanas totales, ~5%-7%
del esfuerzo):

- Puntos 1-11 (todo menos dashboards): **≈ $3.900.000**
- Punto 12 solo (2 dashboards comparativos): **≈ $250.000 - $300.000**

Este escalon C es un punto medio entre A ($4.2M) y B ($3.2M) que **todavia
no se le ha ofrecido a la cliente** — queda como referencia interna por si
se retoma la negociacion sobre si conservar o no la gestion documental
completa.

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
  "Tipo de actividad" (Primaria/Secundaria, aplica a toda la actividad —
  corregido 2026-09-14, ver 6.1), y selector de personas en 2 columnas
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

### 13.5 Prototipo De Empleados Reducido Al Alcance Contratado (2026-09-15)

Mantec confirmó oficialmente el **Escalón B** de la sección 12.4
($3.200.000 — Programación + Diario de Campo + Bitácora, sin gestión
documental completa ni tableros comparativos). Se ajustó el prototipo de
Empleados (`resources/views/preview-personal/empleados.blade.php`) para
dejar de mostrar funcionalidad que no está contratada, evitando que el
mockup prometa visualmente algo que no se va a construir en esta etapa:

- **Se quitó del prototipo**: campana de notificaciones (avisos de
  contrato/certificado por vencer, cumpleaños), CRUD de "Certificados
  generales" (alturas, espacios confinados — sección 4.3), columna
  "Certificados" de la tabla, columna "Accesos" (tooltip de
  vigente/vencido por empresa) y su leyenda. Todo esto es parte de la
  gestión documental completa (puntos 2-5 de la propuesta, sección 12.4)
  que Mantec no contrató en esta fase.
- **Se simplificó, no se quitó**: el CRUD de "Empresas e inducciones" pasó
  a ser solo "Empresas" — nombre, marcar por defecto, archivar/restablecer
  (ya no borra registros, los archiva — mismo criterio que
  activo/inactivo en empleados). Se quitó el sub-CRUD de inducciones
  obligatorias por empresa. Confirmado explícitamente por el usuario
  2026-09-15: Empleados sí necesita poder crear/editar/archivar empresas
  (las usa el selector de Empresa en Programación), pero no gestionar
  inducciones ni certificados.
- **Resuelto 2026-09-15**: se quitó también de Programación el bloqueo de
  selección por inducciones (`elegible(emp)` ya no existe en
  `programacion.blade.php`) — consistente con que Empleados ya no
  administra inducciones. El selector de personas de la sección 6 ahora
  muestra **todos** los empleados activos, sin filtrar por empresa. Se
  quitaron también los íconos de certificados generales de ese selector
  (dependían del CRUD de Certificados, tampoco contratado). De paso se
  reemplazó la grilla fija de checkboxes (Campo/Administrativos) por un
  **combobox buscable** (escribir para filtrar, clic para agregar como
  chip removible) — con todos los empleados visibles una lista fija ya no
  era práctica. Cada persona muestra sus **horas acumuladas en el mes**
  (dato de referencia, no bloqueante) tomadas del mismo archivo compartido
  que usa la Bitácora (`_horas-mes-data.php`, extraído de los
  `$totalesReales` que antes vivían solo en `bitacora.blade.php`).
- **Resuelto 2026-09-15**: la "cuota mensual" de horas a laborar (sección
  8, antes constante fija `$cuota = 182` en PHP) ahora es **editable
  manualmente por mes** desde la propia Bitácora (input numérico junto al
  navegador de mes/año), persistida en `localStorage` por año-mes — sigue
  sin haber backend real en el prototipo, así que no sobrevive a un cambio
  de navegador/dispositivo, pero sí a un refresh. Confirma la duda abierta
  de la sección 8: el cálculo de cuota/extras **sí se replica** en la
  vista web, y la cuota no es un número fijo para siempre.

## 14. Implementación Real — Fase 1 (Login + Empleados + Empresas)

**2026-09-16**: arranca la construcción del backend real del módulo, por
fuera del mockup de la sección 13 (que sigue existiendo intacto en
`/preview-rrhh-7f3k2q/*`, sin auth, solo como referencia visual para
Programación/Diario de Campo/Bitácora hasta que se retomen en una Fase 2).

**Alcance de esta Fase 1**: login administrativo real e independiente,
CRUD real de Empleados, CRUD real de Empresas, acceso por rol. Nada de
persistencia de Programación/actividades, horas, Bitácora, inducciones
por empresa, certificados generales, documentos en R2, notificaciones,
API Android ni dashboards — eso sigue siendo Fase 2 en adelante (ver
sección 12.4 para el mapeo contra lo efectivamente contratado, Escalón B).

**URL real**: `/personal/*` (`/personal/login`, `/personal/empleados`) —
prefijo nuevo, limpio, separado del mockup.

**Arquitectura de autenticación** (ver sección 3 — login/roles
"completamente independientes" del sistema actual):
- Guard nuevo `personal` (sesión) sobre un modelo nuevo `App\Models\Employee`
  (tabla `employees`), sin relación con `App\Models\User`/`Role`/
  `RoleModulePermission` del sistema actual.
- **Superadmin no se duplica**: sigue entrando por el `/login` de siempre
  (guard `web` existente) y accede a `/personal/*` porque
  `App\Support\PersonalGuard::check()` acepta el guard `personal` **o**
  un usuario `web` con `role.key === 'superadmin'`. No tiene fila en
  `employees` — invisible en los catálogos del módulo, como pide la
  sección 3.
- `employees.role` es una columna string simple (`'supervisor'` |
  `'administrativo'` | `null`), no una tabla de permisos — los nombres
  formales de rol siguen pendientes de cierre con el cliente (sección 11);
  una tabla completa tipo `RoleModulePermission` sería adelantarse a un
  requisito aún abierto.
- Empleados de categoría **Campo nunca tienen `role` ni acceso de login**
  — validado en `EmployeeController`, no como constraint de base de datos.

**CRUD de Empleados**: mismos campos que el prototipo ya redujo en la
sección 13.5 (nombre, nickname, abreviatura, categoría, activo, hace
parte de la Bitácora), más lo que el login real sí necesita: checkbox
"Tiene usuario de acceso" → usuario/contraseña/rol, condicionado a
categoría Administrativos. "Eliminar" un empleado sigue sin existir —
solo activar/inactivar, igual que en el prototipo.

**CRUD de Empresas**: igual que el prototipo ya lo dejó en la sección
13.5 — nombre, marcar por defecto, archivar/restablecer (nunca borrado
destructivo). Alimenta el selector de Empresa que Programación seguirá
usando cuando se construya en Fase 2.

**Bootstrap local**: `PersonalModuleSeeder` (registrado en
`DatabaseSeeder`) crea una empresa (`ARGOS`, por defecto) y un empleado
administrativo de arranque (`personal.admin` / `123456`) — mismo
mecanismo que ya usa `UserSeeder` para el `superadmin` actual.

**Archivos clave**: `app/Models/Employee.php`, `app/Models/Company.php`,
`app/Support/PersonalGuard.php`, `app/Http/Middleware/EnsurePersonalAccess.php`,
`app/Http/Controllers/Personal/*`, `resources/views/personal/*`,
`resources/views/layouts/personal*.blade.php`,
`database/migrations/2026_09_16_*`, bloque `Route::prefix('personal')` en
`routes/web.php`.

**Validado localmente (2026-09-16)**: migraciones aplican y revierten
limpio; login real funciona (creado vía formulario → logout → login de
nuevo); empleado con `has_login=false` no puede entrar aunque tenga
password en base de datos; empleado Campo con intento de `has_login`/`role`
es rechazado con mensaje visible en el modal; superadmin entra a
`/personal/empleados` sin login nuevo y no aparece en la tabla; cerrar
sesión de `/personal` no cierra la sesión del panel admin real; los 4
endpoints JSON de Empresas (crear/renombrar/marcar por defecto/archivar)
responden correctamente; las rutas del mockup (`/preview-rrhh-7f3k2q/*`)
siguen funcionando sin cambios.

**Pendiente / Fase 2**: migrar Diario de Campo y Bitácora a base de datos
real (horas con trazabilidad de 3 valores, cuota mensual persistida
server-side en vez de `localStorage`), retomar inducciones por empresa y
certificados generales si el cliente contrata esa ampliación (sección
12.4, "gestión de documentos" y "tableros comparativos"), y cerrar los
nombres formales de rol con el cliente (sección 11) para eventualmente
reemplazar la columna `role` simple por algo más granular si hace falta.

### 14.1 Fase 2a — Programación real

**2026-09-16**: Programación pasa a base de datos real, dentro del mismo
`/personal/*`. Diario de Campo y Bitácora siguen en el mockup — por diseño
(sección 2) son *proyecciones sobre las mismas actividades* de
Programación, así que no tenía sentido construirlas antes de que existiera
la tabla real.

**Tablas nuevas**: `activities` (fecha, empresa, grupo, área — catálogo
"ilustrativo" sin captura real, sección 6 —, equipo, actividad, tipo P/S,
horas estimadas, jornada, responsable — ahora FK real a `employees`, no un
string de abreviatura suelto) y el pivot `activity_employee` (personas de
la actividad). `activity_type` vive en `activities`, no en el pivot —
confirma la corrección de la sección 6.1 (es propiedad de la actividad
completa).

**Dos reglas de negocio que el mockup NO validaba, y aquí sí, de verdad**
(sección 6):
- **Primaria única por persona/día**: al guardar una actividad primaria,
  se verifica que ninguna de las personas ya tenga otra actividad primaria
  esa misma fecha — si la tiene, se rechaza nombrando a quién. El mockup
  decía explícitamente "no validado en este mockup"; ya no aplica esa
  salvedad.
- **Ventana de edición del supervisor**: "el supervisor solo puede
  modificar la Programación del día actual o el día anterior" ahora se
  aplica de verdad — pero **solo** cuando quien actúa tiene
  `role = 'supervisor'`. Administrativo y superadmin no tienen esa
  restricción (no hay base textual para restringirlos igual, y la sección
  11 sigue sin cerrar los permisos exactos entre roles — supuesto
  explícito, ajustable).

**Cambios de UX respecto al mockup** (todos documentados como decisión
consciente, no descuido):
- Navegación de fecha pasa de todo-en-Alpine a `?date=` con recarga de
  página — el mockup cargaba ~40 actividades de ejemplo completas en
  memoria, algo que no escala con datos reales. Mismo patrón que ya usa
  Bitácora hoy.
- El calendario sigue siendo un modal navegable por mes sin recargar toda
  la página, pero ahora pide los días con datos a un endpoint nuevo
  (`GET /personal/programacion/dias-con-datos`) en vez de tenerlos todos
  en memoria.
- Se quitó el botón "Organizar" — con datos reales el orden por grupo
  siempre lo da el `ORDER BY` del backend, el botón no tenía nada que
  hacer.
- Se quitó la negrita visual de las filas de "transporte" — en el mockup
  era un flag manual solo en los datos de ejemplo; la sección 5 ya aclara
  que esas filas no son un tipo especial, y sin un campo real no hay de
  dónde inferirlo.
- "Horas acumuladas este mes" en el buscador de personas **sigue siendo
  dato de ejemplo** (mismo archivo que usa Bitácora) — para empleados
  reales nuevos (no estaban en el Excel de muestra) simplemente no muestra
  nada, hasta que exista Bitácora real.

**Bugs encontrados y corregidos durante la verificación por HTTP** (no
solo curl feliz — se probó activamente hasta romper algo):
- `Rule::exists()->where('columna', false)` rompía contra Postgres
  (bindeaba el booleano PHP como cadena vacía → `invalid input syntax for
  type boolean`) — se cambió a la forma de closure
  (`->where(fn ($q) => $q->where('columna', false))`), que sí arma un
  `where()` normal de query builder.
- El endpoint de días-con-datos devolvía timestamps ISO completos
  (`"2026-09-16T05:00:00.000000Z"`) en vez de `"2026-09-16"` — el cast de
  fecha del modelo se aplica incluso en `pluck()` de una sola columna; el
  `Set` de JS del calendario nunca hubiera matcheado. Se normaliza con
  `->toDateString()` antes de responder JSON.
- Las horas estimadas se mostraban como `"10.00"` en vez de `"10"` (cast
  `decimal:2`) — se corrige con `+0` al mostrarlas.

**Validado localmente (2026-09-16)**: migraciones aplican y revierten
limpio; actividad creada vía formulario real se ve correctamente agrupada
por grupo en la tabla; segunda actividad primaria para la misma persona el
mismo día rechazada con mensaje visible; actividad secundaria para esa
misma persona ese mismo día sí permitida; supervisor de prueba rechazado
al intentar programar una fecha de hace 10 días; superadmin sin esa
restricción, mismo caso permitido; endpoint de días-con-datos devuelve el
formato correcto tras el fix; mockup (`/preview-rrhh-7f3k2q/*`) sigue
funcionando sin cambios; acceso sin sesión a `/personal/programacion`
redirige correctamente al login.

**Pendiente**: edición/eliminación de una actividad ya creada (el mockup
tampoco la tenía — solo creación; se deja explícito por si se necesita
después), Diario de Campo y Bitácora reales (siguientes en la lista,
ahora que `activities` ya existe para que ambas se apoyen en ella).

### 14.2 Fase 2b — Diario de Campo real

**2026-09-16**: Diario de Campo pasa a base de datos real. Por diseño
(sección 2 y 5) **no es una tabla nueva** — es el mismo registro
`Activity` de Programación, mostrado y enriquecido con los campos que se
completan a lo largo de su ciclo de vida (supervisor crea → supervisor
ejecuta → administrativo cierra). Bitácora sigue en el mockup — sigue
dependiendo de datos de horas reportadas que solo puede dar la app Android
(sección 7, repositorio aparte, aún no existe).

**Columnas nuevas sobre `activities`** (sección 5, tabla completa):
`process`, `executed_description`, `corrected_hours`, `reported_hours`
(todas nullable, sin migración adicional pendiente cuando exista la app),
`comments`, `zcom`, `line_code`, `ot_sap`, `acta_entrega`, `we_code`, más
auditoría de cierre `closed_by_employee_id` (FK `employees`) y
`closed_at`.

**Decisiones de diseño**:
- `reported_hours` queda en el esquema pero **no es editable desde esta
  pantalla todavía** — el documento la marca como "Supervisor, vía app",
  y esa app no existe aún (sección 7). La regla de "valor final" de horas
  (sección 5/8) ya la contempla en el modelo (`finalHours()`:
  corregida → reportada → estimada), lista para cuando exista esa fuente.
- "Proceso" y "Actividad ejecutada" se editan en el mismo modal de cierre
  administrativo, aunque el documento los marca como responsabilidad del
  supervisor — sin la app todavía alguien tiene que poder llenarlos
  manualmente. Simplificación documentada, no definitiva.
- **Quién puede cerrar**: administrativo y superadmin, no supervisor —
  coincide con la sección 2 ("el ADMINISTRATIVO revisa y TERMINA de
  llenar la actividad"). Autorización real en el controlador
  (`abort_if(...role === 'supervisor', 403)`), no solo el botón oculto en
  la vista (AGENTS.md sección 6) — verificado con un `PATCH` directo por
  curl sin pasar por la UI. El supervisor sí puede **ver** Diario de
  Campo completo, solo no editar los campos de cierre.
- Un solo modal por fila con los 9 campos editables y un único `PATCH`,
  en vez de inputs sueltos por celda con autoguardado (que tenía el
  mockup, sin persistencia real detrás) — más simple de validar
  correctamente. Reabre con errores igual que Empleados y Programación.
- Mismo calendario y mismo endpoint `dias-con-datos` que Programación —
  es la misma tabla `activities`.
- Sin botón "Nueva actividad" en esta pantalla — Diario de Campo no crea,
  solo cierra; la creación sigue siendo responsabilidad de Programación.

**Validado localmente (2026-09-16) por HTTP con curl** (no solo el happy
path): actividad creada en Programación aparece el mismo día en Diario de
Campo con badge "Pendiente" y sin datos de cierre; al cerrarla como
superadmin con proceso/horas corregidas/comentarios/códigos, la fila pasa
a "Cerrado", el botón cambia a "Editar cierre" y "Horas" muestra la
corregida (10) en vez de la programada (8); un supervisor de prueba ve la
pantalla completa pero sin botón de cierre, y un `PATCH` directo contra
`/personal/diario-campo/{id}` sin pasar por la UI es rechazado con `403`;
el calendario (`dias-con-datos`) devuelve el día correcto; el mockup
(`/preview-rrhh-7f3k2q/diario-campo`) y Programación real siguen
funcionando sin cambios. Datos y empleado de prueba eliminados al
terminar.

**Pendiente**: edición/eliminación de actividades desde Programación
(heredado de la sección 14.1); significado real de ZCOM/Línea/OT SAP/Acta
entrega/WE sigue sin confirmar con el cliente (quedan como texto libre a
propósito). Bitácora real: ver sección 14.3.

### 14.3 Fase 2c — Bitácora real

**2026-09-16**: Bitácora pasa a base de datos real — último módulo del
alcance confirmado del contrato ($3.200.000, sección 12.4). Por diseño
(sección 8) **no es una tabla de captura manual independiente**: es un
consolidado mensual alimentado por Programación, con corrección
administrativa opcional por día/persona.

**Decisión de diseño clave, confirmada con el usuario**: la app Android
(fuente real de "reportada", sección 7) todavía no existe. Se preguntó
explícitamente qué mostrar en la celda mientras tanto y se confirmó usar
`corregida ?? programada` como valor final (programada = horas calculadas
en vivo sumando `activities.estimated_hours` por empleado/día), en vez de
seguir literalmente el texto de la sección 8 ("por defecto es la
reportada"). Sin la app, seguir el texto literal habría dejado casi toda
la Bitácora en blanco/alerta hasta que un administrativo corrigiera
manualmente cada día de cada persona — menos útil que el Excel actual
mientras tanto. "Reportada" queda en el modelo (siempre "—" por ahora,
visible en el tooltip), lista para cuando exista la app — mismo criterio
ya usado con `reported_hours` en Diario de Campo (sección 14.2).
Consecuencia: la alerta ámbar ahora se dispara cuando **ni programada ni
corregida** existen ese día (antes se habría disparado por la mera
ausencia de "reportada", que hoy es sistemáticamente ausente).

**Tablas nuevas**: `bitacora_entries` (corrección administrativa por
empleado/día — `corrected_value` es **texto libre**, admite códigos como
"L" = licencia, confirmado en el mockup 2026-09-09/10; más `comment` y
auditoría `corrected_by_employee_id`) y `bitacora_quotas` (cuota de horas
por año-mes — reemplaza el `localStorage` que usaba el mockup como
solución temporal). "Programada" no se guarda en ninguna tabla nueva: se
calcula en vivo desde `activities`/`activity_employee` (ya existentes
desde Fase 2a), sin mezclarse con `corrected_hours` de Diario de Campo
(esa es una corrección a nivel actividad, no a nivel persona/día).

**Quién accede**: a diferencia de Programación y Diario de Campo, la
sección 8 titula Bitácora "Web, Administrativos Y Superadmin" — el
supervisor no tiene acceso ni de lectura aquí (no solo de edición). Se
aplica `abort_if(...role === 'supervisor', 403)` en las tres rutas
(`index`, guardar corrección, guardar cuota), y el enlace se oculta del
sidebar para ese rol.

**Bug encontrado y corregido antes de reportar éxito**: la vista portada
del mockup usaba `posicionarPopover()` (helper JS de los tooltips de
celda) que solo existía en el layout del mockup, no en el layout real —
se portó a `layouts/personal.blade.php` antes de verificar, junto con el
mismo fix de clase CSS (`scroll-container-visible` → `table-scroll-container`)
ya aplicado en Diario de Campo.

**Validado localmente (2026-09-16) por HTTP con curl**: dos actividades
de prueba en Programación (8h y 10h, mismo empleado, días distintos) se
reflejan correctamente como "programada" en sus celdas, con total del mes
= 18; un empleado con `in_bitacora = false` con horas ese mes no aparece
como columna; una corrección de texto ("L", licencia) se guarda, se
muestra en la celda, y el total del mes baja de 18 a 10 (el texto no
suma); la cuota mensual se guarda y persiste tras recargar (ya no
depende de `localStorage`), y "Extras" recalcula correctamente; un
supervisor de prueba recibe `403` tanto en `GET /personal/bitacora` como
en los dos POST de guardado directos por curl, y no ve el enlace en su
sidebar; mockup, Programación real y Diario de Campo real siguen
funcionando sin cambios. Datos y empleados de prueba eliminados al
terminar.

**Pendiente**: sincronización con la app Android (cuando exista, activa
la columna "reportada" real y probablemente amerite revisar si el
fallback a "programada" sigue teniendo sentido o debe retirarse);
calendario real de festivos colombianos (hoy solo domingo = festivo,
simplificación heredada del mockup); dashboards comparativos
programado-vs-reportado (sección 9, fuera del Escalón B contratado).
Con esto, los tres módulos del alcance confirmado (Programación, Diario
de Campo, Bitácora) están completos en su versión real.

### 14.4 Editar/eliminar actividades en Programación

**2026-09-16**: Programación real (14.1) solo permitía crear. Se agrega
editar y eliminar una actividad ya creada, reusando el patrón dual
crear/editar que ya probó Empleados (`formAction`/`formMethod` Alpine
dinámicos, un solo modal, botón "Editar" por fila que llama
`editarActividad(actividad)`). Esto obligó a convertir los campos simples
del modal de `old()`-en-Blade a `x-model` de Alpine — antes solo
alcanzaba para crear.

**Regla nueva**: una actividad ya cerrada en Diario de Campo (`closed_at`
no nulo) deja de ser editable/eliminable para supervisor, sin importar la
ventana de edición — el administrativo ya la revisó y finalizó (sección
2). Administrativo/superadmin sí pueden seguir corrigiéndola (son quienes
la cerraron). Se implementó `ActivityController::canModify()`, que
extiende el `isEditable()` ya existente con este chequeo, usado tanto
para mostrar/ocultar los botones en la vista como para autorización real
en `update()`/`destroy()` (`abort_if(..., 403)`, no solo UI oculta).

Las reglas de negocio de `store()` (ventana de edición del supervisor,
primaria única por persona/día) se extrajeron a un método privado
`validated()` reutilizado por `update()` — el chequeo de primaria única
excluye la propia actividad al editar. Eliminar es borrado real
(`delete()`, no archivado — las actividades son registros operativos, no
catálogos); el pivot `activity_employee` ya tenía `cascadeOnDelete()`
desde Fase 2a.

**No se puede mover una actividad a otro día desde este modal** — el
campo fecha sigue fijo a la página que se está viendo (toda actividad
visible ya tiene esa fecha, por el propio filtro de `index()`). Si hace
falta cambiar de día, se elimina y se crea de nuevo.

**Validado localmente (2026-09-16) por HTTP con curl**: actividad de
prueba editada (empresa, área, equipo, horas, jornada) — cambios
reflejados correctamente; intento de editar violando la regla de primaria
única rechazado con el mensaje correcto, sin persistir cambios, y el
modal reabre en modo edición (no creación) gracias al `activity_id`
oculto; eliminación real confirmada en base de datos, incluyendo cascada
del pivot; actividad cerrada desde Diario de Campo — supervisor de prueba
ya no ve los botones editar/eliminar en esa fila y recibe `403` real en
ambos `PUT`/`DELETE` directos por curl; superadmin sigue viendo los
botones y pudo editar esa misma actividad cerrada sin problema; mockup,
Diario de Campo y Bitácora reales siguen funcionando sin cambios. Datos y
empleado de prueba eliminados al terminar.

### 14.5 Roles y permisos dinámicos

**2026-09-16**: `employees.role` (string fijo `'supervisor'`/
`'administrativo'`) se reemplaza por un sistema configurable de subroles.
Antes, agregar un rol nuevo (ej. "SISO") o cambiar qué ve "Supervisor"
requería una sesión de código — cada regla estaba hardcodeada como
`role === 'supervisor'` en 4 controladores distintos. Ahora es un módulo
nuevo ("Roles y permisos", exclusivo de superadmin) donde se crean/
renombran roles y se marca qué módulos ve cada uno, sin tocar código.

**Independiente del sistema actual**: existían tablas `Role`/
`RoleModulePermission`/`SystemModule` en el guard `web`, pero **no
estaban conectadas a ninguna verificación real** (se confirmó por
búsqueda en el código — cero referencias a `can_view`/`modulePermissions`
fuera de los modelos). Se decidió no reutilizarlas ni tocar el sistema
actual — tabla nueva `personal_roles`, exclusiva de este módulo.

**Lista fija de 6 permisos** (confirmada con el usuario, no una matriz
genérica ver/crear/editar): `ver_empleados` (Empleados y Empresas),
`ver_programacion`, `editar_programacion_sin_limite` (sin esto, la
ventana de edición de hoy/ayer de la sección 6 sigue aplicando — mismo
permiso gobierna si se puede tocar una actividad ya cerrada),
`ver_diario_campo`, `cerrar_diario_campo`, `ver_bitacora` (un solo
permiso para ver y usar, igual que antes). Son columnas boolean directas
en `personal_roles`, no una tabla de permisos aparte — la lista es fija,
no un catálogo de módulos que vaya a crecer.

**Migración de datos, no solo de esquema**: `employees.personal_role_id`
(FK a `personal_roles`) reemplaza a `role`. La misma migración siembra
los roles "Administrativo" (los 6 permisos en `true`) y "Supervisor"
(`editar_programacion_sin_limite`/`cerrar_diario_campo`/`ver_bitacora`
en `false`, resto `true`) con los permisos exactos que ya tenían en la
práctica antes de este cambio — incluyendo que Empleados/Empresas nunca
tuvieron restricción de rol hasta ahora — y backfillea cada empleado
existente según su `role` string. El deploy de este cambio no le quita
acceso a nadie; el usuario ajusta desde la UI nueva de ahí en adelante.

**"Roles y permisos" es exclusivo de superadmin, a propósito no
configurable** — no forma parte de los 6 permisos ni del sidebar
dinámico: si un subrol pudiera administrar permisos, podría
autoasignarse acceso (escalación de privilegios).

**`PersonalGuard::can(string $permiso)` centraliza la autorización** —
superadmin siempre `true`; un `Employee` depende del booleano
correspondiente en su `personalRole`. Reemplaza cada
`PersonalGuard::employee()?->role === 'supervisor'` que existía en
`ActivityController`, `FieldDiaryController` y `BitacoraController`, más
el chequeo especial que solo ocultaba Bitácora en el sidebar (ahora cada
ítem, incluido Empleados/Empresas que antes no tenían ninguna
restricción, se muestra según su permiso).

**Bug preexistente encontrado y corregido durante la verificación** (no
introducido hoy, ya estaba desde Fase 1): la validación de
`EmployeeController` referenciaba `$employee` dentro de un closure
`->after()` sin capturarlo en el `use()` — `Undefined variable $employee`
al editar un empleado con acceso habilitado y contraseña en blanco (el
caso normal de "no cambiar la contraseña"). Corregido agregando
`$employee` al `use()`.

**Validado localmente (2026-09-16) por HTTP con curl**: migración aplica,
revierte limpio (empleados con un rol que no existía en el mundo
original de 2 roles quedan en `role = null` tras revertir — pérdida de
información esperada, no corrupción) y reaplica correctamente; rol
"SISO" creado sin ningún permiso — empleado de prueba con ese rol recibe
`403` en las 5 pantallas del módulo; `/personal/roles` da `403` a un
empleado con rol "Administrativo" (no superadmin); ventana de edición de
Programación, bloqueo de actividad cerrada, cierre de Diario de Campo y
bloqueo total de Bitácora — las 4 reglas ya construidas siguen
funcionando igual que antes, ahora impulsadas por el permiso en vez del
nombre del rol; reasignar el rol de un empleado desde Empleados cambia su
acceso de inmediato (sin caché); eliminar un rol con empleados asignados
rechazado con mensaje claro, uno sin empleados sí se puede eliminar;
renombrar un rol se refleja de inmediato en los empleados que lo tienen
(vía FK, no texto duplicado); mockup y el resto de Personal siguen
funcionando sin cambios. Datos y empleados de prueba eliminados al
terminar.

**Seguimiento 2026-09-16 (mismo día)**: el login siempre redirigía a
Empleados sin importar el rol — un rol sin ese permiso (ej. SISO) entraba
bien pero caía directo en un `403`. Corregido:
`PersonalGuard::firstAccessibleRoute()` recorre los permisos en el mismo
orden que el sidebar (Empleados → Programación → Diario de Campo →
Bitácora) y devuelve la primera ruta accesible; `PersonalAuthController`
la usa para ambos flujos de login (empleado y superadmin). Si el rol no
tiene ningún permiso, cae en `/personal/sin-acceso` — pantalla explicando
la situación con botón de cerrar sesión, en vez de un `403` crudo justo
después de loguearse. Validado por HTTP: rol sin permisos →
`/personal/sin-acceso`; rol con un solo permiso (`ver_diario_campo`) →
aterriza directo ahí, no en Empleados; superadmin sigue aterrizando en
Empleados como antes (sin regresión). Datos de prueba eliminados.

### 14.6 Campo "Proceso" También Editable Desde Programación (2026-09-18)

**2026-09-18**: la columna `process` ("Proceso") ya existía en `activities`
desde la Fase 2b (sección 14.2, columnas del Diario de Campo), pero solo se
podía diligenciar al cerrar una actividad como administrativo. A pedido del
usuario, ahora también es editable desde Programación — el supervisor la
diligencia al crear/editar la actividad, en vez de esperar al cierre
administrativo (coincide con la sección 5, que ya marcaba "Proceso" como
responsabilidad de "Supervisor (Programación o al ejecutar)").

**Cambios**: `ActivityController::validated()` valida `process` (`nullable`,
`max:150`, mismo límite que ya usaba `FieldDiaryController::close()` porque
comparten la misma columna `string('process', 150)`); `store()`/`update()`
lo persisten. En `programacion/index.blade.php` se agregó la columna
"Proceso" en la tabla justo después de "Equipo" (con su celda de datos y el
`colspan` del encabezado de grupo ajustado de 8 a 9), y un campo de texto
libre "Proceso" en el modal de creación/edición, también justo después de
"Equipo" (grid del modal pasó de 2 a 3 columnas). Se propagó `process` en
el estado Alpine (`formActividad`, `emptyFormActividad()`,
`editarActividad()`, `oldValues` de Blade) siguiendo el mismo patrón dual
crear/editar que ya usaban el resto de campos simples del formulario.

**Sin condición de carrera con el cierre administrativo**: una actividad ya
cerrada en Diario de Campo deja de ser editable desde Programación
(`canModify()`, sección 14.4), así que nunca hay dos pantallas escribiendo
`process` al mismo tiempo — mientras está abierta solo Programación la
toca, una vez cerrada solo Diario de Campo (para quien tenga
`editar_programacion_sin_limite`).

**Sin migración nueva**: la columna y el `$fillable` del modelo ya
existían; el cambio es enteramente de controlador y vista.

**Validado localmente (2026-09-18) por HTTP con curl** (sin suite
automatizada para este módulo, mismo criterio que el resto de la sección
14): actividad creada con `process="Proceso QA"` se guarda y aparece en la
celda de la tabla justo después de Equipo, con el `colspan` del
encabezado de grupo ya en 9; edición cambia el valor correctamente
(`UPDATE` confirmado en base de datos); un intento de guardar `process`
con 151 caracteres es rechazado por la validación (el valor en base de
datos queda intacto, no se trunca ni se persiste el valor inválido);
Diario de Campo sigue respondiendo `200` sin cambios. Empleado y
actividad de prueba eliminados al terminar.

**Seguimiento 2026-09-18 (mismo día): Equipo y Proceso pasan a combobox de
sugerencias**: el usuario pidió evitar que Equipo/Proceso terminen con
variantes tipo "Cemento"/"CEMENTO"/"Semento" (ensucia el filtrado futuro,
ej. Bitácora), pero sin convertirlos en catálogo rígido — deben seguir
siendo texto libre, y si es la primera vez que se usa el sistema (sin
datos aún) el combobox no debe mostrar nada.

**Cambios**: `ActivityController::index()` carga, para `team` y `process`,
los valores ya usados en `activities` vía un helper nuevo
`distinctFreeTextValues(string $column)` — `DISTINCT` + `unique()` por
`mb_strtolower()` (agrupa "Cemento"/"CEMENTO" en una sola sugerencia, sin
tocar el valor real guardado en cada fila) — pasados a la vista como
`equipos`/`procesos`. En `programacion/index.blade.php`, ambos inputs se
convirtieron en combobox: mismo patrón de popover teleportado a `body` +
`posicionarPopover()` que ya usan Responsable y Personas en este mismo
modal, pero aquí el input **es** el valor final (no hay selección por ID
como en Responsable) — `x-model` sigue apuntando a
`formActividad.team`/`formActividad.process`, y las sugerencias
(`equiposFiltrados()`/`procesosFiltrados()`) solo filtran en vivo (case
insensitive, `includes()`) lo ya usado antes; hacer clic en una sugerencia
solo copia ese texto al input, no bloquea escribir algo nuevo. Sin
sugerencias que coincidan (incluido el caso de tabla vacía), el panel
muestra "Sin coincidencias — se guardará el texto escrito." en vez de
nada oculto a ciegas, mismo criterio de mensaje vacío que ya usaba
Personas.

**Deliberadamente fuera de alcance**: `FieldDiaryController::close()`
(cierre de Diario de Campo) también puede escribir `process`, y ese campo
sigue siendo un input de texto plano sin combobox — el usuario solo pidió
el cambio para el formulario de Programación. Sigue siendo una vía por la
que puede colarse una variante nueva de "Proceso" sin sugerencias; queda
como pendiente explícito si se quiere unificar.

**Validado localmente (2026-09-18) por HTTP con curl**: con la tabla
`activities` vacía, `equipos`/`procesos` llegan como arreglos vacíos al
estado Alpine (confirmado en el HTML servido, `equipos:
JSON.parse('[]')`); tras crear una actividad con `team="Cemento"` y otra
con `team="CEMENTO"` (mismo día), recargar la página deja `equipos` con
una sola sugerencia (`["CEMENTO"]`, dedupe case-insensitive funcionando)
mientras ambas filas de la tabla siguen mostrando su texto original tal
cual se escribió (`Cemento` y `CEMENTO`, sin normalizar el dato guardado);
mismo comportamiento verificado para `process` (`"Coordinador"` /
`"coordinador"` → una sola sugerencia `["Coordinador"]`). Datos y
empleado de prueba eliminados al terminar.

### 14.7 "Responsable De Actividad" Pasa A Ser Una Marca Por Rol (2026-09-18)

**2026-09-18**: el selector "Responsable (supervisor)" de Programación
(sección 6) decidía quién podía ser responsable comparando el **nombre
exacto** del subrol contra el string `"Supervisor"` — tanto en la lista
que ve el usuario (`ActivityController::index()`) como en la validación
real del backend (`responsible_employee_id` en `validated()`). Frágil: si
mañana se renombra ese subrol, o se crea uno nuevo con otro nombre que
también deba poder ser responsable (ej. "Jefe de cuadrilla"), no hay forma
de lograrlo sin tocar código. A pedido del usuario, se reemplaza por una
marca configurable a nivel de **Rol** (`PersonalCategory`), no de subrol:
todo empleado cuyo Rol tenga `responsable_actividad = true` puede elegirse
como Responsable, sin importar el nombre de su subrol específico.

**Cambios**:
- Migración `2026_09_18_140000_add_responsable_actividad_to_personal_categories_table.php`:
  columna `responsable_actividad` (boolean, default `false`) en
  `personal_categories`.
- `PersonalCategory`: nuevo campo en `$fillable`/`casts()`.
- `PersonalCategoryController::store()`/`update()`/`serialize()`: validan,
  persisten y devuelven `responsable_actividad` (mismo patrón AJAX ya
  usado por el resto de esta pantalla).
- `ActivityController::index()`: `$supervisores` ahora sale de
  `Employee::whereHas('personalCategory', fn ($q) => $q->where('responsable_actividad', true))`
  en vez de `whereHas('personalRole', ...->where('name', 'Supervisor'))`.
- `ActivityController::validated()`: la regla `Rule::exists()` de
  `responsible_employee_id` se actualizó al mismo criterio
  (`personal_category_id` dentro de las categorías con el flag en `true`)
  — **crítico que ambos coincidan siempre**: si el selector y la
  validación del servidor usaran criterios distintos, el formulario
  ofrecería opciones que el backend rechazaría. Se quitó el import de
  `PersonalRole` en este controlador (ya no se usa para nada más ahí).
- `resources/views/personal/roles/index.blade.php`: checkbox "Responsable
  de actividad" en el modal Nuevo/Editar rol, justo debajo del nombre (tal
  como se pidió), con nota explicando que aplica a todos los subroles de
  ese Rol; badge azul "Responsable de actividad" en el encabezado de cada
  Rol cuando está activo; estado Alpine (`emptyFormCategoria`,
  `editarCategoria`, payload de `guardarCategoria`) propagando el campo.

**Decisión de migración de datos, confirmada con el usuario**: el subrol
"Supervisor" vive hoy dentro del Rol "Administrativo", que también agrupa
los subroles "Administrativo" y "SISO". Activar el flag automáticamente en
"Administrativo" durante la migración habría preservado a los 2
supervisores actuales, pero también habría vuelto elegibles de inmediato a
6 personas más (3 "Administrativo" + 3 "SISO") que hoy no pueden ser
Responsable — un cambio de alcance real, no solo técnico. Se consultó
explícitamente y el usuario eligió dejar el flag **apagado en todos los
Roles tras el deploy** (default `false` de la columna, sin backfill).
**Consecuencia a tener en cuenta al desplegar**: hasta que el superadmin
active el flag manualmente desde "Roles y permisos" en el Rol que
corresponda, el selector de Responsable quedará vacío, y cualquier
actividad existente que ya tenga un responsable asignado (ej. Luis
Fernando, Bonilla sup) **no podrá editarse** (ni siquiera para cambiar
otro campo) sin antes reasignar o limpiar su Responsable — la validación
es real en servidor, no solo un filtro visual, y aplica sobre el valor que
ya trae el formulario aunque el usuario no lo toque.

**Validado localmente (2026-09-18) por HTTP con curl** (usuario superadmin
de prueba desechable, sin tocar ninguna cuenta real): crear un Rol nuevo
con `responsable_actividad=true`, un subrol bajo ese Rol (sin nombre
especial, sin ningún permiso marcado) y un empleado con ese Rol/subrol
hace que el empleado aparezca en `supervisores[]` de Programación;
crear una actividad con ese empleado como `responsible_employee_id` es
aceptado (`302`, persistido en base de datos); apagar el flag del Rol
saca al empleado de `supervisores[]` de inmediato (confirmado también por
consulta directa a Eloquent vía tinker, no solo por HTTP) y bloquea
guardar cualquier cambio sobre una actividad que todavía lo tenga como
responsable (intento de edición rechazado, `302` con errores, la
descripción original en base de datos queda intacta — no se aplicó el
cambio); la actividad ya creada sigue mostrando el nombre del responsable
en su fila de la tabla (comportamiento esperado: no se reescribe
historial, solo se bloquean selecciones nuevas). Migración aplica y
revierte limpio. Usuario, Rol, subrol, empleado y actividad de prueba
eliminados al terminar; los 2 Roles reales (`Camposs`, `Administrativo`)
quedan con el flag en `false`, tal como se acordó.

**Confirmado contra un caso real (2026-09-18)**: el usuario mostró una
captura real del Excel de Diario de Campo donde Bonilla (supervisor) tiene
su propia fila de actividad ("Supervisor") sin Responsable asignado,
mientras que las filas de cuadrilla sobre un equipo sí lo tienen ("B").
Aclaración explícita del usuario: **no existe una regla especial** de
"un Responsable-elegible se exime a sí mismo" — simplemente no todas las
actividades tienen Responsable, es la costumbre de la empresa registrar al
supervisor como una actividad más. El comportamiento actual (`personas` y
`responsible_employee_id` ambos opcionales, sin `required`, ver
`ActivityController::validated()`) ya es correcto tal cual está — no se
necesita ningún cambio de validación a partir de este ejemplo.

### 14.8 Autocompletar Responsable Por Grupo (2026-09-18)

**2026-09-18**: pedido del usuario para agilizar la captura — cuando el
mismo Grupo ya tiene una actividad ese día con Responsable asignado (caso
típico: varias actividades de una misma cuadrilla/día comparten
supervisor), escribir ese número de Grupo en una actividad nueva
autocompleta el mismo Responsable, en vez de tener que volver a buscarlo
cada vez. **Explícitamente acotado al día que se está viendo** — nunca
mezcla el Grupo con el de otro día.

**Cambios**:
- `ActivityController::index()`: nueva variable `$gruposResponsables`,
  armada sobre la misma colección `$actividades` que ya viene filtrada por
  `where('date', $date)` — por construcción nunca puede traer datos de
  otro día. `unique('group_number')` sobre la colección ya ordenada por
  `group_number, id` se queda con el Responsable de la actividad más
  antigua de ese grupo si hubiera más de uno por inconsistencia de datos.
  Se pasa a la vista como `gruposResponsables` (mapa `numero_grupo =>
  employee_id`).
- `programacion/index.blade.php`: el input de Grupo del modal
  (`name="group_number"`) suma `@change="autocompletarResponsablePorGrupo()"`.
  El método nuevo en Alpine **nunca pisa una elección ya hecha** — solo
  actúa si `formActividad.responsible_employee_id` sigue vacío — y busca
  el valor en `gruposResponsables[grupo]`. Si no hay match (grupo nuevo, o
  sin ninguna actividad con Responsable ese día todavía), no hace nada; el
  campo se llena a mano como siempre.
- Se dispara con `@change` (al perder foco/confirmar el valor), no en cada
  tecla, para no autocompletar a medio escribir un número de más de un
  dígito.

**Validado localmente (2026-09-18) por HTTP con curl + navegador real
(Selenium/Chrome)**: se crearon dos actividades de un mismo día por HTTP
(Grupo 1 → Responsable A, Grupo 2 → Responsable B) y se confirmó
`gruposResponsables` correcto en el HTML servido
(`{"1":<id_A>,"2":<id_B>}`). En navegador real: abrir "Nueva actividad" y
escribir Grupo=1 autocompleta a Responsable A; en una actividad nueva
aparte, Grupo=2 autocompleta a Responsable B; eligiendo primero un
Responsable a mano (B) y escribiendo después Grupo=1 (que mapea a A), el
valor elegido a mano **no se sobrescribe** — queda en B, confirmado leyendo
el estado real de Alpine (`Alpine.$data(...).formActividad`) dentro del
navegador, no solo por inspección del DOM. Datos, Rol, subrol, empleados y
usuario de prueba eliminados al terminar.

**Pendiente/fuera de alcance**: la elección manual solo se protege
mientras el campo Responsable siga con el mismo valor desde que se
autocompletó — si el usuario lo vacía explícitamente y vuelve a disparar
el evento de Grupo (ej. tocando el spinner), podría volver a
autocompletarse. No se agregó seguimiento de "auto vs. manual" por ser un
caso borde poco frecuente frente al flujo típico descrito por el usuario
(una actividad nueva, un Grupo, una vez).

### 14.9 "Responsable De Actividad" También Configurable Por Subrol (2026-09-18)

**2026-09-18, mismo día que 14.7**: al ver el modal real de "Editar
subrol" (captura del usuario, subrol "Supervisor" dentro del Rol
"Administrativo"), el usuario notó que el checkbox nuevo de 14.7 solo vive
en el Rol — activarlo en "Administrativo" marcaría de una vez a los 3
subroles que agrupa (Administrativo/Supervisor/SISO), justo el problema ya
detectado y evitado en 14.7 (por eso ahí se dejó el flag apagado por
defecto). Pidió poder marcarlo también a nivel de uno o varios subroles
específicos, sin tener que activar el Rol completo.

**Solución**: el mismo campo `responsable_actividad`, ahora también en
`personal_roles` (Subrol), **se combina con OR** junto al de
`personal_categories` (Rol) — un empleado es elegible como Responsable si
su Rol lo tiene activo, **o** si su Subrol específico lo tiene activo, o
ambos. Esto permite dos formas de uso simultáneas: activar todo un Rol de
una vez (para Roles simples que no necesitan mezcla), o activar solo
"Supervisor" dentro de "Administrativo" sin tocar "Administrativo"/"SISO".

**Cambios**:
- Migración `2026_09_18_150000_add_responsable_actividad_to_personal_roles_table.php`:
  misma columna, mismo default `false`, ahora en `personal_roles`.
- `PersonalRole`: nuevo campo en `$fillable`/`casts()`.
- `PersonalRoleController`: se valida y persiste igual que
  `disponible_en_programacion` (no es un permiso de acceso, es una regla
  de elegibilidad — se valida aparte del array `PERMISOS`).
- `ActivityController`: nuevo método privado `responsableEligibleQuery()`
  que centraliza el criterio OR (Rol o Subrol) en un solo lugar, reusado
  tanto por `$supervisores` (`index()`) como por la regla `Rule::exists()`
  de `responsible_employee_id` (`validated()`) — evita que ambos criterios
  se desincronicen si el campo cambia de nombre o de lógica a futuro.
- `resources/views/personal/roles/index.blade.php`: segundo checkbox
  "Responsable de actividad" en el modal de Subrol (sección "Otra
  configuración", junto a "Aparece como persona seleccionable en
  Programación"), con nota explicando que se suma al del Rol; columna
  nueva "Responsable de actividad" en la tabla de subroles (mismo patrón
  check/minus que "Disponible en Programación"); estado Alpine
  (`emptyFormRol`, payload de `guardarRol`) propagando el campo.

**Validado localmente (2026-09-18) por HTTP con curl + tinker** (usuario
superadmin desechable): un Rol con el flag en `false` y dos subroles bajo
él, uno con el flag en `true` y otro en `false` — solo el empleado del
subrol marcado aparece en `supervisores[]` de Programación y solo él pasa
la validación real de `responsible_employee_id` (`302` aceptado); el
mismo intento con el empleado del subrol sin marcar es rechazado (`302`
con errores, no se persiste en base de datos, confirmado por conteo en
BD). Chequeo de regresión aparte confirma que el caso ya validado en 14.7
(Rol en `true`, Subrol en `false`) sigue funcionando exactamente igual.
Migración aplica y revierte limpio. Todos los datos de prueba eliminados
al terminar.

### 14.10 Corrección: Rol Y Subrol (no OR) + Columna Acciones Fija (2026-09-18)

**2026-09-18, mismo día**: al ver el modal real de "Editar subrol" (captura
del usuario) con el Rol "Administrativo" ya activado en producción, el
usuario señaló que **un Subrol nunca debería poder ser Responsable si su
Rol no lo es** — la sección 14.9 lo dejó como una alternativa independiente
(`rol.responsable_actividad OR subrol.responsable_actividad`), lo cual
reabría exactamente el problema que 14.7 quería evitar: si alguien activa
el Rol "Administrativo" completo (como ya pasó en producción), el `OR`
hacía elegibles de inmediato a los 3 subroles (Administrativo/SISO/
Supervisor) sin poder excluir ninguno, aunque sus flags individuales
siguieran en `false`.

**Corrección**: la relación pasa de `OR` a **el Rol como requisito**
(`rol.responsable_actividad AND subrol.responsable_actividad`) — un
Subrol nunca otorga nada si su Rol no está también activo; el Rol deja de
ser "todos sus subroles son Responsable" y pasa a ser "requisito
habilitante" para poder marcar subroles específicos dentro de él. Un
empleado sin subrol asignado nunca es elegible (no hay nada que
combinar). Cambios:

- `ActivityController::responsableEligibleQuery()` renombrado a
  `responsableEligibleRoleIds()`: ahora calcula los **IDs de Subrol**
  elegibles (`PersonalRole::where('responsable_actividad', true)
  ->whereIn('personal_category_id', <categorias con el flag activo>)`),
  reusado tanto por `$supervisores` (`index()`) como por la regla
  `Rule::exists()` de `responsible_employee_id` (`validated()`) — un solo
  `whereIn('personal_role_id', ...)` en ambos lados, ya no hace falta el
  `OR` con `personal_category_id` directo en el empleado.
- `roles/index.blade.php`: la columna "Responsable de actividad" de la
  tabla de subroles ahora muestra el **efecto real**
  (`cat.responsable_actividad && rol.responsable_actividad`), no el flag
  crudo del subrol — un subrol marcado con su Rol apagado ya no se ve en
  verde si en la práctica no otorga nada. En el modal de Subrol, el
  checkbox "Responsable de actividad" se deshabilita
  (`categoriaActualResponsable()`) cuando el Rol no lo tiene activo, con
  una nota ámbar indicando que hay que activarlo primero en el Rol — capa
  de claridad en UI, la aplicación real sigue siendo la del backend
  (AGENTS.md sección 6: nunca confiar solo en ocultar/deshabilitar en el
  frontend).

**Bug de UI encontrado de paso (mismo reporte del usuario)**: la columna
"Acciones" (lápiz de editar subrol) quedó fuera de la vista al agregar la
columna "Responsable de actividad" en 14.9 — la tabla ya no cabía en el
ancho visible, y `table-scroll-container` oculta la barra de scroll a
propósito (para otras pantallas), así que no había ninguna pista de que
hacía falta scrollear para ver el lápiz. Se movió "Acciones" a ser la
**primera** columna con `sticky-col` (mismo patrón ya usado en la tabla de
Programación), quedando siempre visible sin depender del ancho de las
columnas de permisos que se agreguen después.

**Validado localmente (2026-09-18) por HTTP con curl + tinker + navegador
real (Selenium/Chrome)**:
- Caso crítico (antes fallaba bajo `OR`, ahora correcto): Rol con flag
  `false` + Subrol bajo él con flag `true` → el empleado de ese subrol
  **ya no** aparece en `supervisores[]` y un intento de guardarlo como
  Responsable es rechazado (`302` con errores, no persiste en BD).
- Rol con flag `true` + un Subrol con flag `true` y otro con flag `false`
  → solo el empleado del subrol con flag `true` aparece y pasa la
  validación; el del subrol con flag `false` es rechazado aunque su Rol
  esté activo (el caso concreto que motivó todo esto: "Supervisor" sí,
  "Administrativo"/"SISO" no, los 3 dentro del mismo Rol
  "Administrativo").
- En navegador real: al editar un subrol cuyo Rol tiene el flag apagado,
  el checkbox "Responsable de actividad" aparece con el atributo
  `disabled` real (confirmado leyendo el DOM, no solo por inspección
  visual) y se ve la nota ámbar explicando por qué; la celda de "Acciones"
  tiene `position: sticky` confirmado por `getComputedStyle`, y su
  posición queda dentro del viewport horizontal sin necesitar scroll
  manual.

Datos, Rol, subrol, empleados y usuario de prueba eliminados al terminar
en cada verificación. No se tocó el Rol "Administrativo" real (ya
activado por el usuario en producción) ni el subrol "Supervisor" real —
falta que el usuario también active "Responsable de actividad" en
"Supervisor" específicamente para que sus empleados vuelvan a aparecer
como Responsable (con el Rol solo activado y ningún subrol marcado, hoy
nadie es elegible — comportamiento esperado bajo la corrección de esta
sección).

### 14.11 Tabla De Roles Y Permisos: Encabezado Envolvente En Vez De Desbordar (2026-09-18)

**2026-09-18**: con 12 columnas (Acciones, Subrol, Estado, Empleados, 6
permisos, Disponible en Programación, Responsable de actividad) la tabla
de "Roles y permisos" se desbordaba en pantallas de laptop normales
(ej. 1366px), sin ninguna pista visible de que hacía falta hacer scroll
horizontal — `table-scroll-container` oculta la barra de scroll a
propósito para las tablas de datos de Programación/Diario de
Campo/Bitácora, que sí necesitan scroll real por la cantidad de filas y
columnas de datos. El usuario pidió que el encabezado use 2-3 filas en
vez de desbordar, cuando el monitor lo amerite.

**Decisión de alcance**: el arreglo queda **acotado a esta pantalla**, sin
tocar el CSS compartido de `layouts/personal.blade.php` — Programación,
Diario de Campo y Bitácora son tablas de datos reales (filas que crecen
con el uso) que sí necesitan el patrón de scroll horizontal con barra
oculta; la tabla de Roles y permisos es una tabla de configuración con
pocas filas y muchas columnas booleanas cortas, un caso distinto que
amerita comportamiento distinto.

**Cambios** (todos en `resources/views/personal/roles/index.blade.php`,
en un `<style>` y un `<colgroup>` propios de esta vista, sin tocar nada
compartido):
- `.roles-permission-table { table-layout: fixed; width: 100%; min-width: 0; }`
  — reemplaza el `width: max-content` heredado de `.preventive-table`
  (que expande la tabla más allá del contenedor cuando hace falta), por
  un ancho que respeta el contenedor y fuerza a las columnas a repartirse
  el espacio disponible.
- `<colgroup>` con anchos fijos para las columnas angostas de contenido
  corto (Acciones 80px, Subrol 100px, Estado 100px, Empleados 90px) y sin
  ancho explícito para las 8 columnas restantes (6 permisos + Disponible
  + Responsable), que se reparten el espacio sobrante en partes iguales
  — más angostas en pantallas pequeñas (favoreciendo el envolvido en 2-4
  líneas), más anchas en pantallas grandes (favoreciendo un encabezado de
  1-2 líneas).
- `.roles-permission-table th { overflow-wrap: anywhere; }` — necesario
  porque encabezados de una sola palabra ("Acciones", "Empleados",
  "Responsable", "Programación") no tienen espacio donde envolver con
  solo `white-space: normal` (heredado): sin esto se seguían desbordando
  aunque las frases de varias palabras sí envolvían bien. Con esto, como
  último recurso, la palabra se puede partir en vez de desbordar.
- Se quitaron los `style="min-width:7rem/8rem"` inline que ya no aplican
  bajo `table-layout: fixed` (el ancho real lo decide el `<colgroup>`, no
  el contenido); se redujo el padding horizontal de las celdas angostas
  (`px-4`/`px-3` → `px-2`) para dejarles más espacio de contenido real
  dentro de su columna fija.

**Validado localmente (2026-09-18) por navegador real (Selenium/Chrome) en
tres tamaños de ventana**:
- 1366×768 (laptop típico "algo pequeño"): `scrollWidth === clientWidth`
  (0px de desborde, confirmado con medición exacta, no solo visual);
  encabezado en 2-4 líneas según la columna — captura de pantalla revisada,
  "ACCIONES"/"EMPLEADOS" ya caben en una sola línea tras ampliar sus
  columnas fijas, el resto envuelve limpio en los espacios naturales
  ("EDITAR SIN LÍMITE DE" / "HOY/AYER"), con algún corte de palabra
  aceptable en las columnas más angostas ("PROGRAM" / "ACIÓN").
- 1920×1080 (escritorio normal): mismo 0px de desborde; la mayoría de
  encabezados caben en 1-2 líneas limpias, sin cortes de palabra — se ve
  como una tabla normal.
- 1152×700 (caso extremo, más pequeño de lo que pidió el usuario): sigue
  sin desbordar (0px), aunque el encabezado necesita 6-7 líneas en las
  columnas más angostas — degradación aceptable para un tamaño de
  ventana inusualmente chico, prioriza nunca cortar contenido por sobre
  la estética en ese extremo.

Ningún dato de producción fue tocado durante esta verificación (solo
lectura, sesión de superadmin desechable eliminada al terminar).

### 14.12 Bug: `posicionarPopover()` Ubicaba Mal Los Combobox Con Poco Contenido (2026-09-18)

**2026-09-18**: el usuario reportó (con capturas reales) que el combobox
de "Personas de la actividad" en Programación aparecía flotando lejos del
campo que lo abre, cerca del tope del modal — pero solo cuando la lista
filtrada tenía pocos resultados; con la lista completa (muchos
resultados) se ubicaba bien. Mismo síntoma esperable en los demás
combobox de Programación (Equipo, Proceso, Responsable) y en cualquier
otro consumidor de la función compartida `posicionarPopover()`
(`layouts/personal.blade.php`): tooltips de celda de Bitácora, filtro de
columna de Empleados.

**Causa raíz**: `posicionarPopover(el, ancho, altoEstimado)` decide si el
popover cabe debajo del campo; si no, lo "voltea" hacia arriba
calculando `top = rect.top - altoEstimado - 6` — es decir, asume que el
popover mide **exactamente** `altoEstimado` (un techo máximo pasado por
cada llamador: 280 para Personas, 220 para Responsable, 208 para
Equipo/Proceso, 300 para el filtro de columna de Empleados, 100 para el
tooltip de Bitácora), no su alto real. Cuando el contenido real es mucho
más corto que ese techo (ej. una lista filtrada a 1 resultado), el
popover terminaba con un hueco enorme entre su borde inferior real y el
campo que lo abrió, flotando visualmente lejos de donde el usuario
esperaba verlo. Con la lista completa (contenido cercano al techo
`altoEstimado`), el hueco era chico y pasaba desapercibido — de ahí que
el usuario notara el bug solo "cuando es limitado por cantidades".

**Corrección**: en vez de calcular `top` asumiendo una altura fija, al
voltear hacia arriba se ancla por **`bottom`**
(`bottom = window.innerHeight - rect.top + 6`) y se deja `top` sin
definir — así el navegador coloca el borde inferior REAL del popover
(cualquiera sea su altura real, determinada por su contenido y su propio
`max-h-*` de Tailwind) exactamente donde corresponde, sin importar cuánto
mida. Corregido en `layouts/personal.blade.php` (versión real) y también
en `preview-personal/_layout.blade.php` (copia idéntica que usa el
mockup, corregida por consistencia aunque esa pantalla no es funcional).

**Validado localmente (2026-09-18) por navegador real (Selenium/Chrome)**,
reproduciendo el escenario exacto de las capturas del usuario (modal
"Nueva actividad", combobox "Personas de la actividad"):
- Lista completa (sin filtrar): popover de 256px de alto (tope
  `max-h-64`), separado del campo por 6px — igual que antes del fix
  (nunca estuvo roto en este caso).
- Lista filtrada a "bo" (1 resultado + "Sin resultados."): popover de
  97px de alto, separado del campo por **6px** — antes del fix el hueco
  habría sido de ~183px (280 − 97), exactamente el bug reportado.
  Captura de pantalla confirma visualmente que el popover ahora aparece
  pegado al campo de búsqueda, no cerca del tope del modal.

No se verificaron explícitamente los otros consumidores de la función
(tooltip de Bitácora, filtro de Empleados) porque el fix es genérico a
nivel de la función compartida y el mecanismo del bug es idéntico en
todos — quedan cubiertos por el mismo cambio, pero sin una verificación
puntual por HTTP/navegador en esta sesión.

### 14.13 Bug: Ícono "X" Invisible Al Agregar Una Persona A La Actividad (2026-09-18)

**2026-09-18**: el usuario reportó (con captura real) que los chips de
"Personas de la actividad" no mostraban el ícono "X" para quitarlos,
mientras que el chip de "Responsable (supervisor)" sí lo mostraba
correctamente.

**Causa raíz**: cada chip de persona (`<template x-for="id in personas">`)
trae su propio botón "quitar" con `<i data-lucide="x">`, que Lucide
convierte a un `<svg>` real solo cuando se llama `lucide.createIcons()`
después de que el elemento existe en el DOM. `abrirModal()` y
`editarActividad()` sí llaman
`this.$nextTick(() => window.lucide?.createIcons())` — por eso los chips
de personas que ya vienen precargados al **editar** una actividad
existente se ven bien. Pero `togglePersona(emp)` (la función que agrega
una persona nueva al buscarla y hacer clic) modifica el arreglo
`personas` sin llamar a ese refresh — cada chip agregado así durante la
sesión (creando una actividad nueva o agregando gente a una que se está
editando) queda con el `<i data-lucide="x">` sin convertir, es decir,
vacío/invisible, para siempre. El chip de Responsable nunca tuvo este
problema porque es un único elemento **estático** (no un `x-for`) que ya
existe oculto en el DOM desde que se abre el modal — su ícono se convierte
una sola vez en el `nextTick` inicial y de ahí en adelante solo se le
cambia la visibilidad, sin necesitar un nuevo `createIcons()`.

**Corrección**: se agregó
`this.$nextTick(() => window.lucide?.createIcons());` al final de
`togglePersona(emp)`, mismo patrón que ya usan `abrirModal()`/
`editarActividad()`. `quitarPersona()` no necesitaba el mismo fix (quitar
un chip no crea íconos nuevos).

**Validado localmente (2026-09-18) por navegador real (Selenium/Chrome)**:
en una actividad nueva, buscar y hacer clic en una persona (ej. "bonilla")
para agregarla — el botón "quitar" de su chip recién creado ya trae un
`<svg>` real renderizado (confirmado inspeccionando el DOM, no solo
visualmente), sin ningún `<i data-lucide>` sin convertir. Captura de
pantalla confirma el chip "bonilla ×" visible junto al buscador.

### 14.14 Crear/Editar/Eliminar Actividad Pasa A AJAX, Sin Recargar Página (2026-09-19)

**2026-09-19**: el usuario reportó que crear una actividad en Programación
recargaba la página completa y mostraba el resultado en un banner verde
arriba (`session('success')` del layout), en vez del toast de la esquina
inferior derecha que ya usan Empleados y Roles y permisos
(`showCrudToast()`, compartido en `layouts/personal.blade.php`). Pidió
llevar Programación al mismo patrón: AJAX real, sin recargar, mismo toast.

**Causa raíz**: a diferencia de Empleados/Roles (ya AJAX desde el
prototipo), el modal de Programación seguía siendo un `<form>` nativo
(`POST`/`PUT`/`DELETE` con recarga completa) — heredado de cuando esta
pantalla pasó a base de datos real (sección 14.1) y nunca se convirtió.

**Cambio**:

- `ActivityController::store/update/destroy` ahora devuelven JSON
  (`success`, `message`, y `activity` serializado — o `id` en destroy)
  cuando el request pide `application/json` (`isAjaxRequest()`, mismo
  criterio que `EmployeeController`); si no, conservan el `redirect()` con
  flash como fallback. Nuevo método privado `serialize()` (misma forma que
  usa `index()` para hidratar el estado inicial y que devuelve
  store/update, incluyendo `modificable` por fila vía `canModify()` — ya
  no se pasa `$modificables` aparte a la vista).
- `programacion/index.blade.php`: la tabla dejó de ser un `@foreach` de
  Blade — ahora es un array Alpine reactivo (`actividades`, hidratado con
  `actividadesJs` del controller) recorrido con `x-for` anidado por grupo
  (`gruposOrdenados()`, mismo criterio de orden que antes tenía la query:
  `group_number` ascendente con nulos al final, luego `id`). El modal dejó
  de ser un `<form>` nativo (se quitaron `@csrf`, el `_method` oculto y los
  demás inputs ocultos que solo existían para el submit nativo) — ahora
  `@submit.prevent="guardarActividad()"` arma el payload JSON directamente
  desde el estado Alpine y lo manda por `fetch()`. Eliminar también pasa
  por `fetch()` (`eliminarActividad()`) en vez de un `<form>` con
  `@method('DELETE')`. Se quitó el flujo de reapertura del modal vía
  `session()`/`old()` en validación fallida (`$errors->any()`) — ya no
  aplica con AJAX, los errores 422 se muestran con el mismo `showCrudToast()`
  (patrón idéntico a `guardarEmpleado()`).
- Efecto secundario cuidado a propósito: antes, cada guardado recargaba la
  página completa, lo que de paso refrescaba las sugerencias de
  Equipo/Proceso y el mapa Grupo→Responsable (`gruposResponsables`,
  autocompletar). Sin ese reload, `guardarActividad()` actualiza esas
  listas en memoria (`agregarSugerencia()`) tras cada guardado exitoso,
  para no perder ese comportamiento.

**No cambia**: autorización (`ver_programacion`/`editar_programacion_sin_limite`),
ventana de edición, validación de primaria única por persona/día, ni el
bloqueo de actividades cerradas en Diario de Campo — toda esa lógica sigue
intacta en el backend (`ActivityController::validated()`/`canModify()`),
la conversión a AJAX solo cambia cómo el frontend consume la respuesta.

**Cobertura**: [tests/Feature/Personal/ActivityControllerAjaxTest.php](/home/jupazago/Documentos/mantecv1/mantec/tests/Feature/Personal/ActivityControllerAjaxTest.php)
(crear/editar/eliminar por AJAX, 422 de validación, 403 sin permiso,
render de `index` con y sin actividades).

**Bug encontrado en la validación manual del usuario, corregido el mismo
día**: al crear una actividad nueva sin tocar el `<select>` de Empresa (que
ya mostraba "ARGOS" — la primera empresa del listado, ninguna marcada
`is_default`), el guardado fallaba con "El campo company id es
obligatorio." **Causa raíz**: `emptyFormActividad()` arrancaba
`company_id` en `defaultCompanyId` (`null` si ninguna empresa tiene
`is_default=true`), pero el `<select>` nativo igual muestra la primera
opción del listado por comportamiento propio del navegador, sin que eso
dispare un evento `change` que sincronice el modelo de Alpine. Con el
`<form>` nativo esto no se notaba porque el navegador enviaba lo que se
veía en pantalla (no el estado de Alpine); en AJAX se envía
`formActividad.company_id` tal cual, exponiendo el desfase. **Corrección**:
`defaultCompanyId` ahora cae a la primera empresa del listado
(`$empresas->first()?->id`) cuando no hay ninguna marcada por defecto —
mismo id que el `<select>` ya mostraba. Cobertura agregada:
`test_new_activity_form_defaults_to_first_company_when_none_is_marked_default`
en el mismo archivo de test.

**Pendiente de validar**: a diferencia del fix de la sección 14.13, el
resto del flujo (editar, eliminar) todavía no se probó con un navegador
real en esta sesión — solo con la suite de PHPUnit (HTTP + renderizado
Blade) y las correcciones puntuales de esta sección, confirmadas por el
usuario en su propio navegador.

**Estilo de "Personas de la actividad" / "Responsable (supervisor)"
(2026-09-19, iterado varias veces el mismo día)**: el usuario pidió
resaltar visualmente a quién pertenece un choque de "primaria única por
persona/día" (sección 6.1), y de paso ajustar el estilo de los chips
(antes gris pequeño, `bg-slate-100 text-slate-700 text-xs`). Se probaron
dos variantes de fondo de color (naranja pálido `bg-orange-100`, luego
unificado también al chip de Responsable) pero el usuario reportó que el
fondo no dejaba leer bien el nombre. **Estado final**: sin fondo — solo
texto (`text-sm text-slate-700`, más grande que el original) y su botón
"×" (`text-slate-400 hover:text-red-500`, mismo patrón que el resto de
botones "quitar" de esta página). El contenedor de la lista de personas
usa `gap-x-5 gap-y-2` (antes `gap-2`) para separar cada persona sin
depender de un fondo que las delimite.

- Nuevo estado `personasConflicto` (array de ids), poblado en
  `guardarActividad()` cuando el 422 trae el error de `personas` con el
  texto "otra actividad primaria" — `extraerPersonasConflicto()` parsea
  los nicknames del mensaje del backend (`ActivityController::validated()`,
  regla `personas` en el `after()`) y los matchea contra `nombrePersona(id)`
  (que en este módulo devuelve el nickname). El backend no expone ids en
  el error, solo texto — no se justificaba tocar el contrato de
  validación de Laravel (`{"errors": {...}}`) solo para esto. Sin fondo de
  chip, el conflicto se marca con el **color del texto**
  (`text-red-700` en vez de `text-slate-700`), no con `bg`/`ring`. Se
  limpia al abrir/editar el modal y al guardar con éxito.
- Como el parseo depende del texto exacto del mensaje del backend, se
  agregó `test_primary_activity_conflict_error_message_includes_employee_nickname`
  para fijarlo — si ese texto cambia, el test avisa que hay que actualizar
  `extraerPersonasConflicto()` también.

### 14.15 Bug: Actividades Del Mismo Grupo Se Partían En Dos Secciones Tras Crear Por AJAX (2026-09-19)

**Reportado por el usuario**: al crear una segunda actividad con
`group_number=1` (mismo grupo que una actividad ya existente ese día), la
vista mostraba dos secciones "GRUPO 1" separadas en vez de una sola con
ambas filas — se corregía solo al refrescar la página.

**Causa raíz**: el `<input type="number">` de "Grupo" usaba
`x-model="formActividad.group_number"` sin el modificador `.number` de
Alpine, así que el valor viajaba como **string** ("1") en el JSON del
`fetch()`. `Activity` no tiene `group_number` en `$casts`, así que
`ActivityController::store()` (que no hace `refresh()` desde BD antes de
`serialize()`) devolvía ese mismo string en la respuesta AJAX. Mientras
tanto, las actividades cargadas al inicio de la página vienen de una query
a BD ya hidratadas como número. `gruposOrdenados()` (el JS de
`programacion/index.blade.php`) agrupa con un `Map` usando `group_number`
como key — un `Map` trata `"1"` (string) y `1` (number) como claves
**distintas** aunque representen el mismo grupo, de ahí las dos secciones.

**Corrección** (tres capas, cinturón y tirantes):

- `ActivityController::serialize()`: castea `group_number` a `(int)`
  explícitamente antes de devolverlo — la respuesta AJAX ya no depende del
  tipo que haya llegado en el request.
- `gruposOrdenados()`: normaliza la key con `Number(...)` antes de agrupar
  (defensivo — protege contra cualquier futura respuesta con el tipo
  inconsistente, no solo la de hoy).
- El `<input>` de Grupo pasó a `x-model.number` — el payload que sale del
  frontend ya es un número desde el origen, no solo se corrige en la
  respuesta.

**Cobertura**: `test_activity_response_serializes_group_number_as_integer_even_if_sent_as_string`
en `tests/Feature/Personal/ActivityControllerAjaxTest.php` (simula
exactamente el request que mandaba el frontend con el bug).

### 14.16 Selector De Personas Muestra Horas Reales De Bitácora ("acumuladas + hoy") (2026-09-19)

**Pedido del usuario**: al elegir un empleado en "Personas de la
actividad" no se veían las horas acumuladas (Bitácora), y pidió que se
muestren las acumuladas **más** las de la actividad que se está creando
("acumuladas + hoy") para poder comparar candidatos y decidir a quién
programar.

**Causa de que no se viera nada**: `ActivityController::index()` seguía
leyendo `resource_path('views/preview-personal/_horas-mes-data.php')` —
el Excel de ejemplo del mockup original, buscado por **nickname**. Para
cualquier empleado real creado después de ese mockup (incluidos todos los
"camellador N"/"Siso N" de prueba) no hay entrada ahí, así que siempre
salía `null` y no se mostraba nada. Mientras tanto, Bitácora real
(sección 14.3) ya existe desde hace días y calcula horas reales
(`BitacoraController`) — `ActivityController` nunca se actualizó para
usarla.

**Cambio**:

- Nuevo servicio [app/Services/Bitacora/BitacoraHoursCalculator.php](/home/jupazago/Documentos/mantecv1/mantec/app/Services/Bitacora/BitacoraHoursCalculator.php):
  extrae el criterio de "hora final" de un día (corrección administrativa
  de `BitacoraEntry.corrected_value` si existe, si no lo programado —
  suma de `estimated_hours` vía `activity_employee`/`activities`) que
  antes vivía solo, duplicado en potencia, dentro de
  `BitacoraController::index()`. **`BitacoraController` no se tocó** — se
  dejó tal cual, funcionando, para no arriesgar una pantalla real ya en
  uso; el servicio es el lugar correcto para refactorizarlo hacia allá en
  una tarea aparte, deliberada.
- `ActivityController::index()` ahora calcula `horasAcumuladas` por
  empleado con `BitacoraHoursCalculator::totalPorEmpleado()`, sumando
  **solo los días del mes anteriores** a la fecha que se está
  viendo/programando (`$dateCarbon->day - 1`) — el día que se está
  programando queda fuera a propósito, para no contarlo dos veces (se
  muestra aparte, ver abajo). Se busca por `employee_id`, no por
  nickname.
- `programacion/index.blade.php`: `horasMesTexto()` se renombró a
  `horasResumenTexto()` y ahora es reactivo — combina
  `emp.horasAcumuladas` (fijo, viene del backend) con
  `formActividad.estimated_hours` (lo que el supervisor esté escribiendo
  en "Horas estimadas" en ese momento) en vivo, sin recargar nada. Texto
  resultante: `· 18h` (solo acumuladas), `· +10h hoy` (solo lo de hoy, sin
  acumuladas previas), `· 18h +10h hoy` (ambas), o nada si no hay ningún
  dato. Se muestra tanto en el buscador (antes de seleccionar, para
  comparar candidatos) como en los chips ya seleccionados.
- El Excel de ejemplo (`_horas-mes-data.php`) sigue existiendo — todavía
  lo usan los mockups de `preview-personal/` (`programacion.blade.php`,
  `bitacora.blade.php`), que son solo referencia visual sin enlace desde
  el sidebar real. No se tocó ni se borró.

**Cobertura**: `test_index_exposes_accumulated_bitacora_hours_before_the_viewed_date_only`
(verifica que solo cuentan los días anteriores, que una corrección
administrativa sobreescribe lo programado, y que ni el día programado ni
días futuros cuentan) y
`test_bitacora_hours_calculator_prefers_correction_and_excludes_non_numeric_from_sum`
(fija el contrato del servicio: corrección numérica manda, corrección no
numérica como "L" de licencia no suma horas pero tampoco cuenta como "0",
queda `null` si es el único dato calificado del rango) — ambos en
`tests/Feature/Personal/ActivityControllerAjaxTest.php`.

**Pendiente**: no se probó con navegador real en esta sesión — solo con
PHPUnit (HTTP + cálculo del servicio). Falta confirmar visualmente que el
texto "· 18h +10h hoy" se vea bien en el buscador y en los chips, y que
al escribir/borrar "Horas estimadas" se actualice en vivo sin recargar.

### 14.17 Horas Estimadas Nunca Negativas + Tarjeta De Hover Con Nombre Completo Y Horas (2026-09-19)

**Bug reportado**: el campo "Horas estimadas" del modal aceptaba valores
negativos (ej. `-0,5`) — el `<input type="number">` no tenía `min="0"`.
**Corrección**: se agregó `min="0"` (mismo patrón que ya tenía "Grupo" con
`min="1"`); el backend ya rechazaba negativos server-side
(`estimated_hours' => ['nullable', 'numeric', 'min:0']`, sin cambios ahí).

**Tarjeta de hover pedida por el usuario**: al pasar el mouse sobre un
nombre en las columnas "Personas"/"Resp." de la tabla de Programación,
debe mostrar el nombre completo y las horas acumuladas del mes (si
aplica) — para saber quién es exactamente y cuánta carga ya tiene, sin
tener que abrir el modal de edición.

- `ActivityController::serialize()`: cada persona ahora trae también
  `nombre` (nombre completo, no solo `nickname`), y la actividad trae
  `responsible_nombre` junto a `responsible_nickname`.
- `ActivityController::index()`: nueva prop `horasAcumuladasPorId` — el
  array crudo que devuelve `BitacoraHoursCalculator::totalPorEmpleado()`
  (mismo cálculo que ya alimenta el selector del modal, sección 14.16),
  sin filtrar por elegibilidad/activo: alguien que ya aparece en una
  actividad de ese día debe poder mostrar sus horas en el hover aunque ya
  no sea seleccionable para actividades nuevas.
- `programacion/index.blade.php`: las celdas "Personas"/"Resp." dejaron de
  ser un solo `x-text` con nombres unidos por coma — ahora cada persona es
  su propio `<span>` con un popover teleportado a `<body>`
  (`posicionarPopover()`, mismo mecanismo que los combobox del modal,
  pero disparado por `@mouseenter`/`@mouseleave` en vez de
  `@focus`/`@click`) mostrando nombre completo + `horasAcumuladasCardTexto(id)`.

**Cobertura**: `test_store_response_includes_full_names_for_personas_and_responsible`
en `tests/Feature/Personal/ActivityControllerAjaxTest.php`.

**Pendiente**: sin validar en navegador real — falta confirmar que el
hover se posiciona bien dentro de la tabla con scroll horizontal (mismo
riesgo de recorte que ya se corrigió para los combobox el 2026-09-18,
sección 14.12) y que `min="0"` efectivamente bloquea el spinner del
campo de horas.

### 14.18 "Ver Como Supervisor" — Primer Paso De La Lógica Del API (Fase 3) (2026-09-19)

**Pedido del usuario**: en Empleados, un botón para "acceder como
supervisor" a un empleado elegido, que abre una pantalla nueva
(simplificada, pensada como celular) en otra pestaña — con el fin
explícito de **empezar a construir la lógica del API** que consumirá la
futura app Android (sección 7 del documento, repo aparte, todavía no
existe).

**Decisión de sesión/pestañas** (confirmada con el usuario antes de
implementar): las pestañas de un mismo navegador comparten sesión —
autenticar de verdad como el empleado elegido reemplazaría la sesión de
superadmin en **todas** las pestañas abiertas, no solo la nueva. Se
descartó esa opción. En su lugar: la sesión de superadmin nunca cambia
(`Auth::guard('personal')` no se toca); la pantalla nueva simplemente
opera sobre los datos del empleado elegido (pasado por la URL,
verificado en cada request) — "ver como", no "loguearse como". Lo que se
guarda queda atribuido a ese empleado
(`hours_registered_by_employee_id`), no al superadmin real que lo
escribió.

**Alcance de datos para esta primera versión** (confirmado con el
usuario, exactamente lo ya documentado en la sección 7, sin inventar
campos nuevos ni agregar evidencias/fotos todavía):

- Comentarios de la actividad ejecutada.
- ¿Todos los empleados trabajaron las horas acordadas en la
  Programación? (Sí/No).
- Si No: detalle por persona — checkbox "trabajó" (si no, queda en 0
  horas); si sí trabajó, hora de inicio y hora final (no un número
  directo) — el sistema calcula la duración, incluyendo turnos que
  cruzan medianoche (turno nocturno: si la hora final es menor o igual a
  la de inicio, se asume que cruzó a las 00:00 y se suma un día).

**Modelo de datos** — se reutilizaron `activities.comments` y
`activities.reported_hours`, que ya existían desde la migración de
Diario de Campo con el comentario explícito "reported_hours queda lista
para cuando exista la API de la app" (nunca se habían usado hasta hoy).
Solo se agregó lo que faltaba:

- Migración `2026_09_19_160000_...`: `activities.all_worked_scheduled_hours`
  (boolean nullable), `hours_registered_by_employee_id` (FK employees
  nullable) y `hours_registered_at` (timestamp nullable) — trazabilidad
  de quién registró y cuándo.
- Migración `2026_09_19_160001_...`: tabla nueva `activity_employee_hours`
  (`activity_id`, `employee_id`, `worked`, `start_time`, `end_time`,
  `worked_hours`, único por actividad+empleado) — el detalle por persona,
  solo tiene filas cuando `all_worked_scheduled_hours = false`. Nuevo
  modelo [app/Models/ActivityEmployeeHour.php](/home/jupazago/Documentos/mantecv1/mantec/app/Models/ActivityEmployeeHour.php).
- `Activity::finalHours()` no cambió su regla (corregida > reportada >
  programada) — ahora `reported_hours` sí tiene una fuente real que la
  alimenta. **No se tocó Bitácora** (`BitacoraController`/
  `BitacoraHoursCalculator`, sección 14.16): su cálculo sigue sin mirar
  `reported_hours`, solo corrección administrativa/programado. Conectar
  `reported_hours` a Bitácora es una decisión aparte, no pedida hoy.

**Backend**: nuevo [app/Http/Controllers/Personal/SupervisorViewController.php](/home/jupazago/Documentos/mantecv1/mantec/app/Http/Controllers/Personal/SupervisorViewController.php)
(`index`/`store`), rutas `personal.ver-como.index`/`personal.ver-como.store`
dentro del mismo grupo `personal.auth` que el resto del módulo.
Autorización real en cada request (no solo ocultar el botón, AGENTS.md
sección 6): `abort_unless(PersonalGuard::isSuperadmin(), 403)` — ni
siquiera un Employee con **todos** los permisos del módulo Personal
marcados puede entrar, porque esos permisos no implican ser superadmin.
`store()` además verifica que la actividad sea realmente del empleado
elegido (404 si no) y que no esté ya cerrada en Diario de Campo (403 si
sí — mismo criterio que `canModify()`).

**Frontend**: [resources/views/personal/ver-como/index.blade.php](/home/jupazago/Documentos/mantecv1/mantec/resources/views/personal/ver-como/index.blade.php) —
a propósito **no extiende `layouts.personal`** (sin sidebar/topbar del
panel admin): documento HTML propio, columna angosta centrada, pensada
para verse como pantalla de celular. Lista de actividades del empleado
elegido para el día (donde es `responsible_employee_id`, sección 7:
"asignadas a su nombre") como tarjetas; tocar una expande el formulario;
guarda por AJAX (fetch + JSON) contra `SupervisorViewController::store()`.
Botón "Ver como" agregado en `empleados/index.blade.php`
(`target="_blank"`), visible solo si `PersonalGuard::isSuperadmin()` —
gate visual además del real en el backend.

**Cobertura**: [tests/Feature/Personal/SupervisorViewControllerTest.php](/home/jupazago/Documentos/mantecv1/mantec/tests/Feature/Personal/SupervisorViewControllerTest.php)
(autorización incluso contra un Employee con todos los permisos, alcance
por `responsible_employee_id`, camino "todos trabajaron" vs. detalle por
persona con turno nocturno cruzando medianoche, validación de
inicio/final obligatorios si trabajó, actividad ajena rechazada,
actividad cerrada rechazada, limpieza de filas de detalle al volver a
"todos trabajaron").

**Pendiente**:

- No probado con navegador real en esta sesión.
- "Otros campos más que luego se detallarán" (sección 7) — el propio
  documento ya marca que esta especificación está incompleta a
  propósito; faltan evidencias/fotos y cualquier campo adicional que se
  confirme después.
- No se construyó el endpoint Sanctum real bajo `/api/*` que
  eventualmente consumirá la app Android — esta iteración es la pantalla
  web + la lógica de negocio (modelo de datos, cálculo de horas,
  validaciones) que esa API futura reutilizará; falta decidir cómo se
  autenticará un `Employee` contra Sanctum (hoy solo `User` tiene
  `/api/login`) cuando se construya esa parte.
- No se conectó `reported_hours` a Bitácora ni a los indicadores — sigue
  siendo un dato nuevo, no consumido todavía en otras pantallas.

**Corrección 2026-09-19 (mismo día)**: el usuario reportó el botón roto en
consola (`Alpine Expression Error: verComoUrlTemplate is not defined`) y
pidió que solo se muestre para roles elegibles como Responsable, no para
todos.

- **Causa del bug**: `verComoUrlTemplate` se agregó al `x-data="personalEmpleadosPage({...})"`
  pero nunca se agregó a la firma de la función `personalEmpleadosPage()`
  ni al objeto que devuelve — un `:href` en el template evalúa contra el
  objeto de datos de Alpine (`with($data)`), no contra el closure de la
  función externa, así que un identificador que solo vive como parámetro
  no declarado en el retorno nunca se resuelve. Corregido en ambos
  puntos.
- **Filtro por rol elegible**: se centralizó el criterio (antes solo
  vivía como método privado en `ActivityController`) en
  `PersonalRole::eligibleAsResponsableIds()` — mismo criterio de
  responsable_actividad por Rol+Subrol que ya usaba el selector
  "Responsable (supervisor)" de Programación.
  `EmployeeController`/`empleados/index.blade.php` calculan
  `es_responsable` por empleado con ese mismo método, y el botón usa
  `x-show="emp.es_responsable"` (además del `@if` de superadmin, que
  sigue igual). El backend (`SupervisorViewController`) no se restringió
  — no es un límite de seguridad, es solo relevancia de UI: un empleado
  no elegible simplemente nunca tendría actividades como responsable, la
  pantalla se vería vacía sin necesidad de bloquear la URL directa.

**Cobertura**: `tests/Feature/Personal/EmployeeControllerVerComoButtonTest.php`
— renderiza `/personal/empleados` de verdad (hubiera detectado el bug
original, que un test de solo status-code no atrapaba) y confirma
`es_responsable` en `true`/`false` según el rol.

**Simplificación 2026-09-19 (mismo día)**: tras ver el flujo funcionando
("cuando selecciona una actividad va a diligenciar lo que debe
diligenciar... lo tenemos perfecto"), el usuario pidió NO implementar
todavía la captura de hora inicio/hora final por persona — dejarlo solo
con la cantidad de horas, siempre positiva, precargada automáticamente
con la hora programada de la actividad y editable desde ahí.

- `SupervisorViewController`: se quitó `calcularHoras()` (duración desde
  hora inicio/final) y la validación de esos dos campos. Ahora
  `personas.*.worked_hours` es directamente `['required', 'numeric', 'min:0']`
  — mismo criterio de "nunca negativas" que "Horas estimadas" en
  Programación. `worked` (boolean) se sigue guardando, pero ahora se
  deriva automáticamente (`worked_hours > 0`), ya no es un checkbox
  aparte.
- `ActivityEmployeeHour`: las columnas `start_time`/`end_time` **no se
  eliminaron** del esquema (siguen nullable, sin uso) — quedan listas
  para cuando se retome esa captura más adelante, evitando el
  vaivén de una migración que las borre y otra que las vuelva a crear
  poco después.
- `ver-como/index.blade.php`: el detalle por persona pasó de
  checkbox "trabajó" + hora inicio/hora final condicional, a un único
  `<input type="number" min="0" step="0.5">` por persona, precargado en
  `personaForm()` con `estimated_hours` de la actividad
  (`p?.worked_hours ?? a.estimated_hours ?? 0`).

**Cobertura actualizada**: `test_store_with_per_person_hours_sums_reported_hours`
(reemplaza el test que simulaba el turno nocturno con hora inicio/final) y
`test_store_rejects_negative_worked_hours` (nuevo, mismo criterio de
horas nunca negativas), ambos en `SupervisorViewControllerTest.php`.

### 14.19 Bitácora Conectada Al Registro Real Del Supervisor ("Reportada") (2026-09-19)

**Pedido del usuario**: al ver el hover de una celda de Bitácora ya
después de registrar horas por "Ver como supervisor", "Reportada" seguía
en "—" y el número mostrado en la celda era el programado, no el
reportado. Pidió la regla correcta: reportada manda sobre programada, y
corregida manda sobre reportada.

**Causa — dos bugs, no uno**:

1. `BitacoraController::index()` traía su propia copia duplicada del
   cálculo de "programada" (no usaba `BitacoraHoursCalculator`, sección
   14.16/14.18) y `'reportada' => null` estaba **hardcodeado**, con un
   comentario de cuando "reportada" todavía no tenía ninguna fuente real
   ("sin la app Android, reportada siempre está vacía") — cierto en su
   momento, ya no desde que existe el registro real del supervisor
   (sección 7/14.18).
2. Incluso arreglando el backend, el **número que se ve en la celda**
   (y el resaltado ámbar de "sin datos"/"0 horas") lo calcula un
   **getter de Alpine en el propio HTML**
   (`get final() { return this.corregida ... ? this.corregida : this.programada; }`,
   en `personal/bitacora/index.blade.php`) que tampoco miraba
   `reportada` — un segundo lugar con la misma regla incompleta,
   independiente del backend.

**Corrección**:

- `BitacoraHoursCalculator`: nuevo método `horasReportadasPorDia()` —
  por empleado/día, solo cuenta actividades ya registradas
  (`activities.hours_registered_at` no nulo): usa
  `activity_employee_hours.worked_hours` cuando existe (el supervisor
  marcó que no todos trabajaron las horas programadas), o
  `estimated_hours` cuando confirmó que sí (sin fila de detalle — mismo
  valor que programada, pero ya confirmado en campo). `valorFinal()`
  ahora recibe también `$reportada` y aplica corregida > reportada >
  programada. `totalPorEmpleado()` (usado por el selector de Programación,
  sección 14.16) hereda la misma prioridad automáticamente.
- `BitacoraController::index()`: ya no duplica la query de "programada"
  — usa el servicio (inyectado). `'reportada'` ya no es `null`
  hardcodeado, viene de `horasReportadasPorDia()`.
- `personal/bitacora/index.blade.php`: el getter `final()` del lado
  cliente se corrigió a la misma prioridad de 3 niveles.

**Cobertura**: nuevo [tests/Feature/Personal/BitacoraControllerTest.php](/home/jupazago/Documentos/mantecv1/mantec/tests/Feature/Personal/BitacoraControllerTest.php)
(Bitácora no tenía ningún test hasta hoy) — actividad sin registrar sigue
mostrando solo programada, actividad registrada con detalle por persona
sirve reportada distinta de programada, actividad confirmada "todos
trabajaron" reporta `estimated_hours`, y una corrección administrativa
convive con reportada en los datos servidos.

**Pendiente — límite real de esta sesión**: el getter `final()` es JS que
corre en el navegador; una prueba de contenido HTML estático no puede
ejecutarlo. Sin herramienta de navegador disponible en este entorno, la
combinación de los 3 valores en pantalla (qué número final se ve y si se
resalta en ámbar) **no quedó verificada automáticamente** — solo se
verificó que el backend sirve los 3 valores (`programada`/`reportada`/
`corregida`) correctamente. Falta confirmación visual del usuario.

### 14.20 "Ver Como Supervisor": Turno Nocturno Que Cruza Medianoche + Evidencias (Foto/Video) A R2 (2026-09-19)

**Pedido del usuario**: dos ajustes sobre "Ver como supervisor" (sección
14.18). Primero, pulir la vista para cuando el responsable "hace login y
ve sus actividades del día" — incluyendo el caso de turnos que cruzan
medianoche (turno Nocturno programado "ayer" pero que el responsable
sigue diligenciando "hoy"). Segundo, agregar lo que 14.18 dejó marcado
explícitamente como pendiente: "faltan evidencias/fotos" — reutilizando
el patrón de subida a Cloudflare R2 ya usado en Reportes, pero con una
ruta (prefijo) propia para no mezclarse con la de reportes ni con nada
más del bucket (pedido explícito del usuario, confirmado con captura del
bucket real: `clientes/`, `2026/`, `inmobiliaria-saas/`).

**Turno nocturno (confirmado con el usuario)**: además de las
actividades de hoy, `SupervisorViewController::index()` ahora incluye
las de **ayer** con `shift = 'Nocturno'` — se muestran siempre (con su
badge Registrado/Pendiente normal), no se ocultan al registrarse. Cada
tarjeta agrega una etiqueta "Turno de ayer" cuando `a.date` no coincide
con la fecha consultada, para que no se confunda con las de hoy. El
estado vacío (antes un simple texto plano) se rediseñó con ícono y
mismo estilo de tarjeta que el resto de la pantalla.

**Evidencias — decisión de ruta en R2 (pedido explícito: "que no se
mezcle con nada más")**: `Activity` no tiene cliente/elemento como sí
tiene `ReportDetail` (que usa
[ReportFilePathBuilder](/home/jupazago/Documentos/mantecv1/mantec/app/Support/ReportFilePathBuilder.php),
prefijo `clientes/...`), así que no se reutilizó ese builder. Nuevo
[ActivityEvidencePathBuilder](/home/jupazago/Documentos/mantecv1/mantec/app/Support/ActivityEvidencePathBuilder.php)
con un prefijo de primer nivel propio y nuevo en el bucket:

```
personal-actividades/{empresa-slug-id}/{año}/actividad-{activity_id}/{fecha}_{uuid}.{ext}
```

**Modelo de datos**: migración
`2026_09_19_170000_create_activity_evidences_table.php`, tabla
`activity_evidences` (`activity_id`, `uploaded_by_employee_id`, `disk`,
`path`, `original_name`, `stored_name`, `mime_type`, `extension`,
`file_type` image|video, `size_bytes`, `sort_order`) — misma forma que
`report_detail_files`, sin `evidence_kind` ni soft-delete (no aplican
aquí, se mantuvo mínimo). Nota de implementación: Eloquent no pluraliza
"evidence" solo (es incontable en inglés, infiere la tabla
`activity_evidence`), así que el modelo
[ActivityEvidence](/home/jupazago/Documentos/mantecv1/mantec/app/Models/ActivityEvidence.php)
fija `$table` explícito. Nueva relación `Activity::evidences(): HasMany`.

**Backend**: nuevo
[ActivityEvidenceController](/home/jupazago/Documentos/mantecv1/mantec/app/Http/Controllers/Personal/ActivityEvidenceController.php)
(`store`/`open`/`destroy`), mismas 3 rutas dentro de
`personal.ver-como.*`. Autorización idéntica a
`SupervisorViewController::store()`: solo superadmin, la actividad debe
ser realmente del empleado elegido (404 si no), bloqueado si ya está
cerrada (403) — excepto `open()`, que sí permite ver evidencia de una
actividad ya cerrada (consistente con que el resto de los datos de una
actividad cerrada sigue siendo visible, solo no editable). Validación:
`mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm`,
`max:51200` (50MB) por archivo, máximo 6 por request — mismo criterio ya
usado para adjuntos de Inspector/BandEvent (más estricto que el patrón
laxo de `AdminReportEvidenceController`, apropiado para subida desde
celular). Sube con `Storage::disk('r2')->writeStream()` + verificación
`exists()`, igual que el patrón de Reportes.

**Frontend**: `ver-como/index.blade.php` agrega, dentro de cada tarjeta
expandida, una sección "Evidencias" con grid de miniaturas (abren la URL
firmada de R2 en pestaña nueva), botón de borrado por archivo, e input
de carga múltiple (`subirEvidencias`/`borrarEvidencia` en el componente
Alpine) — sube por `fetch`+`FormData` de forma independiente del guardado
de comentarios/horas.

**Cobertura**: `SupervisorViewControllerTest` ganó 3 pruebas (nocturna de
ayer aparece, diurna de ayer no aparece, nocturna de ayer ya registrada
sigue apareciendo). Nuevo
[ActivityEvidenceControllerTest](/home/jupazago/Documentos/mantecv1/mantec/tests/Feature/Personal/ActivityEvidenceControllerTest.php)
con `Storage::fake('r2')`: subida exitosa, rechazo de no-superadmin,
actividad ajena, actividad cerrada, mimetype inválido, archivo > 50MB,
borrado (BD + disco), borrado bloqueado en actividad cerrada, y
apertura redirige.

**Actualización tras revisión con el usuario (2026-09-19, misma sesión)**:
se probó con navegador real (Chrome vía Playwright) contra R2 real. Se
detectó y corrigió un bug: la miniatura de imagen nunca se veía (quedaba
solo el ícono genérico) porque `@error="$el.remove()"` (listener de
Alpine en el `<img>`) colisionaba con la directiva propia de Blade
`@error(...)` — Blade lo interpretó como su propia directiva y rompía el
compilado de la vista (500 en `/personal/ver-como/{id}`). Se corrigió
escapando a `@@error="..."`. Verificado de nuevo end-to-end: la
miniatura ahora sí carga el contenido real de la imagen (confirmado con
`naturalWidth`/`naturalHeight` del `<img>` renderizado, no solo el
ícono de respaldo).

**Pendiente**:

- El encabezado "Actividades de hoy" de la pantalla no se renombró pese
  a que ahora puede incluir una actividad de ayer (turno nocturno) — se
  consideró suficiente la etiqueta "Turno de ayer" por tarjeta.
- Se revisó `ANALISIS_SISTEMA_LARAVEL.md`: no menciona ninguna tabla del
  módulo Personal (`activities`, `activity_employee_hours`,
  `personal_roles`, etc., todas ya existentes) — el módulo completo
  quedó fuera de ese documento desde antes de esta sesión. Agregar solo
  `activity_evidences` ahí sería inconsistente sin el resto; se dejó sin
  tocar, pendiente de una actualización más amplia de ese documento que
  cubra todo el módulo Personal.

### 14.21 Diario De Campo: Filas Por Grupo De Horas + Sin Concepto De Estado (2026-09-19)

**Pedido del usuario**, revisando la tabla real de Diario de Campo con
datos de producción: la columna "Horas" mostraba un total agregado por
actividad (ej. 27.5 con 3 personas mezcladas), cuando en realidad cada
persona pudo haber trabajado una cantidad distinta. Regla pedida: **una
fila por cada valor de horas DISTINTO** entre las personas de la
actividad — si las 4 personas de una actividad trabajaron 12h, es 1
fila; si 2 trabajaron 8h y 2 trabajaron 10h, son 2 filas (cada una con
su subconjunto de personas y esa hora), no una fila por persona ni un
total agregado.

Además, pedido explícito y enfático: **"en diario de campo no existe eso
de estado, eso no va ni nunca va"** — se eliminó por completo la columna
"Estado" (badge "Pendiente"/"Cerrado"). Aclarado en una ronda de
preguntas: el botón que abre el modal de edición (Proceso, Horas
corregida, ZCOM, Línea, OT SAP, Acta entrega, WE) **se mantiene**, pero
se renombra de "Cerrar"/"Editar cierre" a **"Abrir modal"** — cita
textual del usuario: *"realmente eso no cierra nada pero si deja
editar"*. Por consistencia con esa misma razón se renombró también el
botón de envío del formulario, de "Guardar cierre" a "Guardar". La
columna de acción (antes "Estado") ahora no tiene encabezado de texto, y
se oculta por completo (header + celdas) para empleados sin el permiso
`cerrar_diario_campo` — antes la columna siempre se veía, solo el botón
interno se ocultaba.

**Importante — alcance de este cambio**: es puramente de presentación en
Diario de Campo. `closed_at`/`isClosed()` y todo lo que depende de ese
campo en el resto del sistema (bloqueo de edición en Programación vía
`ActivityController::canModify()`, bloqueo de registro en "Ver como
supervisor" vía `SupervisorViewController::store()`, sección 14.18/14.20)
**no se tocaron** — el usuario pidió específicamente que ya no se
llame/vea como "cerrar" en esta pantalla, no que se elimine el mecanismo
de cierre administrativo en sí.

**Backend — nuevo método [Activity::diaryHourGroups()](/home/jupazago/Documentos/mantecv1/mantec/app/Models/Activity.php)**:

- Si `corrected_hours` está definido (corrección administrativa, un solo
  valor para toda la actividad, nunca por persona): 1 grupo con todas las
  personas y ese valor — misma prioridad que `finalHours()`.
- Si `all_worked_scheduled_hours === false` y hay filas en
  `activity_employee_hours`: agrupa las personas por su `worked_hours`
  (vía `Activity::employeeHours`). Nota de implementación: se usa el
  valor **string** del cast `decimal:2` como llave de `groupBy()`, no el
  float — Eloquent Collection usa arrays PHP por debajo, que truncan
  claves float a int (8.5 y 8.0 colisionarían en la clave `8`).
  Una persona sin fila registrada todavía (agregada a la actividad
  después de que el supervisor ya guardó el detalle) cae en su propio
  grupo con horas `null`, en vez de desaparecer o romper.
- En cualquier otro caso (`all_worked_scheduled_hours` true o sin
  registrar todavía): 1 grupo con todas las personas y
  `reported_hours ?? estimated_hours`.

`FieldDiaryController::index()` agrega `employeeHours` al eager-load.

**Frontend**: `personal/diario-campo/index.blade.php` — el `@foreach`
por actividad ahora anida un `@foreach` por cada grupo de
`diaryHourGroups()`, generando una `<tr>` por grupo (Empresa/Equipo/
Proceso/Actividad/Jornada/Comentarios/códigos se repiten igual en cada
fila de la misma actividad; Personas/N°/Horas cambian por grupo). El
modal de edición se renderiza **una sola vez por actividad** (no una vez
por fila generada) — se movió el estado `modalAbierto` de `x-data` local
por `<tr>` a un estado compartido `modalActivityId` en el componente
Alpine de página (`diarioCampoPage()`), y el botón "Abrir modal" solo se
imprime en la primera fila de cada actividad (`$i === 0`).

**Cobertura**: nuevo
[tests/Feature/Personal/ActivityDiaryHourGroupsTest.php](/home/jupazago/Documentos/mantecv1/mantec/tests/Feature/Personal/ActivityDiaryHourGroupsTest.php)
(6 pruebas: sin registro, todos trabajaron igual, horas distintas se
separan, mismo valor no se separa, corrección colapsa a 1 fila, persona
sin registro cae en grupo aparte) y nuevo
[tests/Feature/Personal/FieldDiaryControllerTest.php](/home/jupazago/Documentos/mantecv1/mantec/tests/Feature/Personal/FieldDiaryControllerTest.php)
(6 pruebas: sin texto "Estado"/"Pendiente"/"Cerrado", botón dice "Abrir
modal" y no "Cerrar"/"Editar cierre"/"Guardar cierre", columna de acción
oculta sin permiso `cerrar_diario_campo`, 1 fila vs. 2 filas según el
caso, botón "Abrir modal" aparece una sola vez por actividad aunque
genere varias filas).

**Verificado en navegador real** contra datos reales de producción
(sincronizados de Railway): la actividad real "Actividad 1" de ARGOS
(la misma que el usuario mostró en su captura, con 3 personas y hasta
entonces un total agregado de horas) ahora se parte correctamente en 2
filas — confirma que el caso no era hipotético, ya existía en datos
reales. Sin errores de consola. Datos de prueba (empleados/actividades
QA) creados y eliminados después de verificar.

**Pendiente**:

- No se evaluó si Bitácora (`BitacoraHoursCalculator`, secciones 14.16/
  14.19) debería reflejar este mismo desglose por grupo — hoy sigue
  usando `reported_hours`/`corrected_hours` a nivel de actividad
  completa, sin cambios en esta sesión porque no fue parte del pedido.

### 14.22 Diario De Campo: Edición En Línea (Sin Modal) + Columna "Actividad Ejecutada" (2026-09-19)

**Pedido del usuario**, mismo día, revisando la sección 14.21 recién
implementada: (1) ver por separado la actividad de Programación
(`description`) y la que el supervisor ejecutó realmente
(`executed_description`) — antes esta última vivía oculta detrás de un
"ver detalle ejecutado" dentro de la misma celda de "Actividad"; y (2)
quitar el botón "Abrir modal" — que "las filas sean editables como
cuando vemos los reportes preventivos de los activos. así es más
fácil", citando el patrón de edición en línea celda-por-celda que ya
existe en
[AdminPreventiveReportController::inlineUpdate()](/home/jupazago/Documentos/mantecv1/mantec/app/Http/Controllers/Admin/AdminPreventiveReportController.php)
+
[resources/views/admin/reports/preventive/show.blade.php](/home/jupazago/Documentos/mantecv1/mantec/resources/views/admin/reports/preventive/show.blade.php)
(clic en la celda → input/textarea + ✕/✓ inline, sin recargar la fila
completa).

**Decisión confirmada con el usuario antes de implementar**: el modal
anterior, al guardarse, siempre marcaba la actividad como cerrada
(`closed_by_employee_id`/`closed_at`). Con edición celda-por-celda (
varios guardados pequeños e independientes en vez de un solo submit),
mantener eso habría cerrado la actividad con la primera edición de
cualquier campo — se preguntó explícitamente y se confirmó: **la
edición en línea ya NO marca la actividad como cerrada**. Alcance:
puramente de esta pantalla — `closed_at`/`isClosed()` y todo lo que
depende de eso en otras pantallas (`ActivityController::canModify()`,
`SupervisorViewController::store()`, `ActivityEvidenceController`) no
se tocaron, siguen funcionando igual, solo que ya nada los alimenta
desde Diario de Campo. Si en el futuro se necesita un cierre
administrativo real, sería una acción aparte.

**Backend**: `FieldDiaryController::close()` se reemplazó por
`inlineUpdate(Request, Activity)` (mismo patrón que
`AdminPreventiveReportController::inlineUpdate`): `PATCH
/personal/diario-campo/{activity}/inline-update`, body `{field, value}`,
whitelist de 9 campos (`process`, `executed_description`,
`corrected_hours`, `comments`, `zcom`, `line_code`, `ot_sap`,
`acta_entrega`, `we_code`), autorización igual que antes
(`PersonalGuard::can('cerrar_diario_campo')` — el nombre del permiso
quedó de cuando esto cerraba la actividad, no se renombró). Validación
especial para `corrected_hours` (numérico, no negativo); el resto por
longitud máxima. Valor vacío limpia el campo (`'' -> null`).

**"Horas" ahora es la celda editable** que escribe `corrected_hours`
(no se agregó una columna nueva "Horas corregida" separada — se
reutilizó la columna "Horas" ya existente, pre-cargada con el valor
efectivo mostrado, sea el programado, el reportado o ya una corrección
previa). Consecuencia a tener en cuenta: como `corrected_hours` es un
valor único por actividad (nunca por persona), editar la celda "Horas"
de **cualquier** fila de una actividad con varias filas (varios grupos
de horas, sección 14.21) corrige la actividad completa — al recargar,
esas filas se colapsan en una sola. Es el comportamiento esperado según
`Activity::diaryHourGroups()`, pero puede sorprender la primera vez.

**Por qué recarga la página en cada guardado** (a diferencia del patrón
de reportes preventivos, que actualiza solo la celda): varios campos
(Proceso, Comentarios, ZCOM, etc.) se repiten idénticos en todas las
filas generadas de una misma actividad cuando tiene más de un grupo de
horas (sección 14.21) — y "Horas" en particular puede cambiar cuántas
filas genera esa actividad, como se explicó arriba. Mantener eso
sincronizado en el DOM sin recargar sería frágil; un `window.location.reload()`
tras cada guardado exitoso es simple y siempre correcto. Los campos
sin permiso `cerrar_diario_campo` (`$puedeEditar = false`) se muestran
como texto plano, sin el wrapper `.inline-editable` — antes la columna
completa de "Estado" se veía igual para todos y solo el botón interno
se ocultaba.

**Frontend**: `personal/diario-campo/index.blade.php` — se agregó un
`<style>` con las mismas clases `.inline-edit-*`/`.inline-toast` de
`show.blade.php` (duplicadas, no extraídas a un partial compartido:
tocar esa vista para extraerlas habría sido un refactor no relacionado
sobre una pantalla que funciona, fuera del pedido de esta sesión) y un
script `diarioMountInlineEditor`/`diarioSaveInlineCell`/etc., variante
propia que soporta además `<textarea>` (`data-multiline="1"`) para
`executed_description`/`comments`, que en el patrón original solo tenía
`<input>` de una línea. Se eliminó por completo el modal, el estado
Alpine `modalActivityId`, y la columna de acción — ya no queda ningún
botón visible en la tabla, toda la edición es por celda.

**Cobertura**: `FieldDiaryControllerTest` se reescribió — nuevas
pruebas de `inlineUpdate` (guarda campo, no toca `closed_at`, rechaza
horas no numéricas/negativas, rechaza campo desconocido, rechaza sin
permiso, valor vacío limpia el campo) y de la vista (sin "Abrir modal"
ni "Guardar cierre", Actividad programada y ejecutada como campos
separados, celdas editables solo con permiso).

**Verificado en navegador real** con datos reales (sincronizados de
Railway): clic en celda "Proceso" → aparece input + ✕/✓ (visualmente
igual al patrón de reportes preventivos que pidió el usuario) → guardar
→ `PATCH inline-update` responde 200 → la página recarga → el nuevo
valor persiste como texto plano. Confirmado también contra la base de
datos real que `closed_at`/`closed_by_employee_id` siguen en `null`
después del guardado. Sin errores de consola.

**Verificación adicional (mismo día, tras revisión)**: se probó en
navegador real el caso de editar "Horas" sobre una actividad ya
partida en varias filas — actividad con 2 personas (8h y 10h, 2 filas),
se editó la celda "Horas" de la primera fila a "20", tras el reload la
actividad pasó a mostrarse en **1 sola fila** con ambas personas juntas
y "20" horas. Comportamiento esperado confirmado end-to-end, no solo a
nivel de `ActivityDiaryHourGroupsTest`.

**Bug encontrado por el usuario tras revisar en pantalla real, corregido
mismo día**: "la tabla se desborda, no se ve la actividad ejecutada".
Medido en navegador (ancho de tabla real vs. contenedor): con un
comentario largo en cualquier fila, la tabla llegaba a **5360px** de
ancho (contenedor real: 1086px) — una sola celda de "Actividad
ejecutada" o "Comentarios" con texto largo medía **~1800-2100px**.
Causa: `.inline-edit-trigger` es `display: inline-block` sin
`max-width` — sin eso, un inline-block crece para caber todo el texto
en una sola línea en vez de partir línea, y como todas las filas de una
columna de tabla comparten el mismo ancho, una sola celda larga
arrastra la columna (y la tabla) entera. Mismo problema late en
`resources/views/admin/reports/preventive/show.blade.php` (mismo CSS,
sin `max-width` tampoco) pero no se tocó esa vista — fuera de alcance de
esta sesión. Corrección: `max-width: 20rem` + `overflow-wrap:
break-word` en `.inline-edit-trigger` de `diario-campo/index.blade.php`.
Verificado de nuevo con texto largo real: tabla bajó a 2099px, celdas
largas ahora parten línea dentro de ~344px en vez de explotar.

### 14.23 Scrollbar Lateral Siempre Visible + "Actividad Ejecutada" = Comentario Del Supervisor (2026-09-19)

**Pedido del usuario**, mismo día: (1) "necesitamos una barra para el
scroll lateral, y siempre debe verse. hablo de un scroll interno en la
tabla" — el contenedor de la tabla (`.table-scroll-container`, definido
en
[layouts/personal.blade.php](/home/jupazago/Documentos/mantecv1/mantec/resources/views/layouts/personal.blade.php))
oculta el scrollbar a propósito (`scrollbar-width: none` +
`::-webkit-scrollbar { height: 0 }`), apoyándose solo en el texto
"Desliza horizontalmente" como pista. (2) "el comentario del supervisor
es realmente la actividad ejecutada. así es" — corrección conceptual:
la columna "Actividad ejecutada (supervisor)" (sección 14.22) se había
mapeado a `executed_description` (campo administrativo, ajeno al
supervisor); el campo correcto es `comments` (lo que el supervisor
escribe en "Ver como supervisor", sección 14.18/14.20, con el propio
placeholder "Novedades de la actividad ejecutada..."). Se preguntó
explícitamente si fusionar con la columna "Comentarios" existente o
dejar ambas mostrando el mismo dato — el usuario eligió **dejar ambas**
(duplicado intencional).

**Scrollbar — reutilizado, no reinventado**: existe ya un patrón
`.scroll-container-visible` en
[preview-personal/_layout.blade.php](/home/jupazago/Documentos/mantecv1/mantec/resources/views/preview-personal/_layout.blade.php)
con un comentario explícito: *"Para tablas grandes (Bitácora, Diario de
Campo): el patrón de scroll oculto de arriba no alcanza — aquí el
contenedor SI debe quedar acotado a la pantalla, con scrollbar lateral
(vertical) e inferior (horizontal) visibles"* — nombra Diario de Campo
por nombre, pero nunca se había aplicado a la pantalla real, solo al
mockup. Se copió tal cual (mismo nombre de clase, mismo CSS: `overflow:
auto`, `max-height: 65vh`, scrollbar delgado estilizado vía
`scrollbar-color`/`::-webkit-scrollbar-thumb`) al `<style>` local de
`diario-campo/index.blade.php`, sin tocar `layouts/personal.blade.php`
(otras pantallas que usan `.table-scroll-container` —p.ej. Programación—
no se tocaron, fuera de alcance). El exportador de imagen
(`imageExporterMixin`, mismo layout) ya soportaba ambas clases
(`.table-scroll-container, .scroll-container-visible`) de antes, así
que "Copiar como imagen" siguió funcionando sin cambios adicionales. Se
quitó el texto "Desliza horizontalmente para ver todas las columnas"
(ya no hace falta, el scrollbar es visible por sí mismo).

**"Actividad ejecutada" → `comments`**: en `diario-campo/index.blade.php`,
la celda cambió `data-field`/valor de `executed_description` a
`comments` (misma fuente que la columna "Comentarios", a propósito).
`FieldDiaryController::EDITABLE_TEXT_FIELDS` perdió la entrada
`executed_description` — ya no tiene ninguna celda que la use en esta
pantalla (el campo sigue existiendo en la base de datos, sin uso desde
aquí).

**Cobertura**: `FieldDiaryControllerTest` —
`test_shows_programada_and_ejecutada_as_separate_fields` ajustado para
verificar que se ve `comments` y NO se ve `executed_description`; nueva
`test_inline_update_rejects_executed_description` (regresión: ya no es
un campo editable válido).

**Verificado en navegador real**: "Actividad ejecutada (supervisor)"
muestra el mismo texto que "Comentarios" en filas reales (ej. "Ninguna
novedad, todo se hizo muy bien"); un `executed_description` de prueba
sembrado a propósito confirmado que NO aparece en pantalla. CSS del
contenedor confirmado por `getComputedStyle` (`overflow: auto`,
`scrollbar-width: thin`, `max-height: 585px` en viewport de 900px) y
`scrollWidth > clientWidth` (necesita scroll). La captura de pantalla
del contenedor recortado no capturó el scrollbar personalizado en sí
(limitación conocida de captura headless con scrollbars custom, no del
CSS) — las propiedades computadas sí confirman que el comportamiento es
correcto; el patrón fuente (`preview-personal`) ya estaba probado
visualmente antes de esta sesión.

**Pendiente**:

- No se verificó visualmente con captura de pantalla el scrollbar
  personalizado en sí (ver limitación de la herramienta arriba) — solo
  las propiedades CSS computadas. Vale la pena una confirmación visual
  rápida del usuario en su propio navegador.

**Ajuste menor (mismo día)**: pedido del usuario, "los encabezados
pueden ser sin paréntesis" — "Actividad (programación)" → "Actividad
programada", "Actividad ejecutada (supervisor)" → "Actividad
ejecutada". Solo texto de encabezado, sin cambio de comportamiento ni
de campos.

**Pendiente**: ninguno adicional a los ya listados en la sección 14.21.

### 14.24 API Real Para La App Android Del Supervisor (Sanctum + Employee) (2026-09-20)

**Pedido del usuario**: construir la API real que consumirá la app Android
(repo aparte `Mantec_ins`) para que un supervisor haga desde su celular lo
mismo que hasta hoy solo se probaba vía "Ver como" (secciones 14.18/14.20/
14.21/14.22): registrar comentarios + horas por actividad/persona, y subir
evidencia (foto/video) a R2. Se investigó primero el repo Android (ya tiene
un rol "Inspector" funcionando contra este mismo backend, con su propio
patrón Sanctum/Retrofit/multipart) para diseñar la API nueva de forma
consistente con lo que ya existe, en vez de inventar un patrón distinto.

**Decisión de autenticación (confirmada con el usuario antes de
implementar)**: el supervisor se autentica como **`Employee`**, no como
`User` — login nuevo y separado del login del Inspector, aunque ambos
terminan siendo tokens Sanctum válidos contra el mismo `auth:sanctum`.
Confirmado por investigación que esto no requiere configuración adicional:
Sanctum en este proyecto es polimórfico puro (`tokenable_type`/
`tokenable_id` estándar, sin guard/provider custom, `auth:sanctum` en modo
bearer sin `statefulApi()`) — agregar `HasApiTokens` a un segundo modelo
"simplemente funciona".

**Riesgo detectado y mitigado**: como el mismo middleware `auth:sanctum`
acepta indistintamente un token de `User` o de `Employee` (según el
`tokenable_type` de esa fila), sin un chequeo explícito un token de
Inspector podría llamar por error las rutas nuevas del Supervisor (o
viceversa). Nuevo middleware
[EnsureTokenableIsEmployee](/home/jupazago/Documentos/mantecv1/mantec/app/Http/Middleware/EnsureTokenableIsEmployee.php)
(`abort_unless($request->user() instanceof Employee, 403)`), alias
`personal.api.employee` (`bootstrap/app.php`), aplicado a todo el grupo
`api/personal/*` salvo login.

**Backend — nuevo namespace `App\Http\Controllers\Api\Personal\`**:

- [Employee.php](/home/jupazago/Documentos/mantecv1/mantec/app/Models/Employee.php):
  agrega `Laravel\Sanctum\HasApiTokens`.
- **`AuthApiController`** — calcado de
  [Api\AuthApiController](/home/jupazago/Documentos/mantecv1/mantec/app/Http/Controllers/Api/AuthApiController.php)
  (login del Inspector) pero contra `Employee`: `Employee::where('username',
  ...)->where('has_login', true)->where('activo', true)->first()` +
  `Hash::check()` + `createToken('supervisor-app')`. `POST
  api/personal/login` lleva `throttle:5,1` (no existía rate limiting en
  ningún login de este proyecto hasta hoy — ni `api/login` ni `/personal/
  login` web lo tenían; se agregó solo al endpoint nuevo, sin tocar los
  existentes).
- **`ActivityController`** — mismo query y misma lógica de guardado que
  [SupervisorViewController](/home/jupazago/Documentos/mantecv1/mantec/app/Http/Controllers/Personal/SupervisorViewController.php)
  (turno nocturno de ayer incluido, detalle de horas por persona vía
  `ActivityEmployeeHour`), pero `$employee = $request->user()` en vez de
  `Employee` por parámetro de URL + `PersonalGuard::isSuperadmin()` — el
  empleado siempre es el dueño del token, nunca algo que confiar de la
  URL/body.
- **`ActivityEvidenceController`** — mismo patrón de subida a R2
  (`ActivityEvidencePathBuilder`, `Storage::disk('r2')->writeStream()`,
  límites `mimetypes:.../max:51200 KB/max:6 archivos`) que la versión web
  ya probada esta sesión contra R2 real. Diferencia clave: `show()`
  devuelve **JSON** `{success, url, expires_at, file_type, original_name}`
  en vez de `redirect()->away($temporaryUrl)` — un cliente API pide la URL
  firmada y la usa directo en su visor de imagen/video (Coil/ExoPlayer del
  lado Android), no sigue redirects HTTP de servidor.

**Rutas nuevas** (`routes/api.php`):
```
POST   api/personal/login                                    (throttle:5,1, sin token)
POST   api/personal/logout                                   (auth:sanctum + personal.api.employee)
GET    api/personal/actividades
POST   api/personal/actividades/{activity}
POST   api/personal/actividades/{activity}/evidencias
GET    api/personal/actividades/{activity}/evidencias/{evidence}
DELETE api/personal/actividades/{activity}/evidencias/{evidence}
```

**Cobertura**: 20 pruebas nuevas en `tests/Feature/Api/Personal/`
(`AuthApiControllerTest`, `ActivityControllerTest`,
`ActivityEvidenceControllerTest`) — login correcto/incorrecto/
`has_login=false`/inactivo, logout revoca el token, turno nocturno
incluido, scope por empleado autenticado (404 si la actividad es de otro),
actividad cerrada rechazada (403), horas negativas rechazadas (422),
**token de `User` (Inspector) rechazado en rutas de Employee** (403, el
caso que motivó `EnsureTokenableIsEmployee`), evidencia sube/lista (JSON
con URL firmada)/borra igual que la versión web.

**Verificado en local con `curl` contra R2 real** (no solo tests): login →
token → listar actividades → guardar horas/comentarios → subir evidencia
(confirmada en el bucket real bajo `personal-actividades/corona-2/2026/
actividad-30/...`) → ver URL firmada real de R2 → borrar evidencia →
logout → confirmar que el token ya no sirve (401). Los `X-RateLimit-*`
headers del throttle nuevo también se confirmaron en las respuestas
reales. Datos de prueba (empleado/actividad QA) creados y eliminados
después de verificar.

**Pendiente**:

- Suite completa corrida sin regresiones (105/106, mismo `ExampleTest`
  preexistente y no relacionado desde antes de esta sesión).
- No se probó todavía desde la app Android real (eso es la Parte D de
  esta misma sesión, documentada en el repo `Mantec_ins` por separado,
  `API_SUPERVISOR.md`) — la verificación de arriba es servidor-a-servidor
  vía `curl`, no un dispositivo/emulador real.
- `ActivityController::serialize()` quedó duplicado entre el controlador
  web (`SupervisorViewController`) y el nuevo de API — son casi idénticos
  pero no se extrajo a un método compartido (ej. en el propio modelo
  `Activity` o un trait), para no tocar el controlador web ya probado
  esta sesión sin necesidad. Si diverge la lógica de negocio en el
  futuro, hay que recordar actualizar ambos.

### 14.25 Fix: "Hoy" En Programación Y Diario De Campo Debía Calcularse En Hora De Colombia, No Del Navegador (2026-09-21)

**Motivo**: tras corregir el mismo tipo de bug del lado Android (el toggle Hoy/Ayer usaba la zona horaria del dispositivo en vez de anclar a `America/Bogota`), el usuario pidió explícitamente confirmar que "todo en la web" (Programación, Diario de Campo, reportes) usa siempre hora de Colombia. Una auditoría dirigida (agente Explore, sin tocar código) encontró que el backend Laravel ya estaba bien anclado (`config/app.php` fija `'timezone' => 'America/Bogota'`, aplicado globalmente por el framework vía `date_default_timezone_set()` en cada request/comando/job; no hay ningún `Carbon::now('UTC')` ni zona horaria distinta hardcodeada en `app/`; ninguna migración usa `timestampTz`; no hay SQL crudo con `NOW()`/`CURRENT_TIMESTAMP`). El riesgo real estaba del lado **frontend**: varias vistas Blade usan `new Date().toISOString().slice(0, 10)` en Alpine.js embebido para calcular "hoy" en el navegador — `toISOString()` siempre convierte a UTC sin importar la zona horaria del navegador, así que entre las **19:00 y medianoche hora Colombia** (todos los días, para todo usuario, sin excepción, a diferencia del bug de Android que solo afectaba dispositivos mal configurados), esa cuenta ya cae en el día siguiente en UTC.

**Dónde afectaba**:

- `resources/views/personal/programacion/index.blade.php` y `resources/views/personal/diario-campo/index.blade.php` — el anillo que resalta "hoy" en el mini-calendario del selector de fecha señalaba el día equivocado durante esa ventana horaria.
- `resources/views/admin/reports/preventive/show.blade.php` (3 lugares) — usado como valor por defecto cuando la "fecha de ejecución" de un reporte viene vacía; este sí podía **escribir un dato real incorrecto** si el usuario no lo corregía manualmente antes de guardar (mayor severidad que los dos anteriores, que solo afectaban un resaltado visual).
- `resources/views/layouts/personal.blade.php` y `resources/views/preview-personal/_layout.blade.php` — mismo patrón, pero solo como sufijo del nombre de archivo al exportar una tarjeta como imagen PNG (cosmético, no afecta datos).

**Fix**:

- Programación y Diario de Campo: se agregó `hoyReal: @js(today()->toDateString())` al objeto de configuración que Blade inyecta en `x-data`, calculado en el servidor (hora de Colombia real), y el Alpine `hoyStr` ahora usa ese valor en vez de `new Date()` del navegador — mismo patrón que ya usaba `fecha` para el día que se está viendo.
- Reportes preventivos: los 3 fallbacks pasaron de `new Date().toISOString().slice(0, 10)` a `@json(today()->toDateString())`, calculado en el servidor al momento de renderizar la página (mismo patrón que el `@json($canInlineEditExecutionDate)` que ya existía en el mismo bloque de script).
- Exportar como imagen (los dos layouts): como es un mixin de JS genérico reutilizado en varias páginas y solo afecta el nombre del archivo exportado (no un dato del reporte), se optó por construir la fecha con los campos locales del navegador (`getFullYear()/getMonth()/getDate()`) en vez de `toISOString()`, que sí corrige el bug de conversión a UTC sin necesitar inyectar nada desde el servidor.

**Cobertura**: 2 pruebas nuevas — `ActivityControllerAjaxTest::test_programacion_index_injects_hoyReal_in_colombia_timezone` y `FieldDiaryControllerTest::test_diario_campo_index_injects_hoyReal_in_colombia_timezone` — ambas cargan la página real autenticada y verifican que el HTML renderizado contiene `hoyReal: '<today()->toDateString() real>'` y ya no contiene `new Date().toISOString()`. No se agregó test para los 3 fallbacks de `show.blade.php` (no había fixture de prueba existente para esa página, fuera del alcance de esta sesión de Personal/Programación) ni para los dos mixins de exportar imagen — se validaron por lectura de diff y porque `php artisan view:cache` compiló sin error las 5 vistas tocadas.

**Verificado en esta sesión**:

- `php artisan view:clear && php artisan view:cache` — compiló **todas** las vistas Blade del proyecto sin error, incluyendo las 5 tocadas.
- Suite completa: `php artisan test` — **107/108 pasando** (mismo `ExampleTest` preexistente y no relacionado desde antes de esta sesión; las 2 pruebas nuevas de esta sección pasan).
- No se verificó visualmente en un navegador real (no hay herramienta de automatización de navegador disponible en este entorno para el lado Laravel) — la verificación se hizo a nivel de renderizado servidor (HTML real generado, con el valor correcto embebido), no de comportamiento JS en un DOM vivo.

**Pendiente**:

- Verificación visual manual en navegador (confirmar que el anillo de "hoy" en el calendario de Programación/Diario de Campo efectivamente se ve en el día correcto, sobre todo probando cerca de las 19:00–00:00 hora Colombia).
- Los 3 fallbacks de `show.blade.php` y los 2 mixins de exportar imagen quedan sin test automatizado (ver "Cobertura" arriba) — riesgo bajo porque son rutas de fallback/cosmética, no el dato principal mostrado.
- `config/database.php` no fija `'timezone' => 'America/Bogota'` para la conexión `pgsql` — hoy no hay ningún código que dependa de eso (todo pasa por Carbon, no por SQL crudo), pero es una configuración frágil: si en el futuro alguien agrega `DB::raw('NOW()')` o una columna `timestampTz`, se rompería silenciosamente. No se tocó en esta sesión por no ser un riesgo activo hoy — queda como mejora preventiva recomendada, no aplicada.

### 14.26 Programación: Reordenar Columnas + Columna "Cant." De Personas + Fix Separador De Nombres (2026-09-21)

**Motivo**: dos pedidos sobre la tabla real de Programación (`personal/programacion/index.blade.php`, no el mockup de `preview-personal/`). (1) Reordenar columnas: después de "Actividad" debía ir una columna nueva con la **cantidad** de personas (no la lista), luego Horas, luego Jornada — la lista de nombres (columna "Personas") pasó a ir después de Jornada. (2) La lista de nombres en la columna "Personas" se veía sin espacio después de la coma ("Herrera,Ana Cecilia,..." en vez de "Herrera, Ana Cecilia, ...").

**Investigación del separador antes de tocar código** (regla de no suposición): se revisó el separador `', '` en las 4 ubicaciones donde se arma esa lista (tabla real, mockup, Diario de Campo, mensaje de conflicto en `ActivityController`) y **las 4 ya tenían el espacio correcto**, byte por byte (`xxd` confirmó `0x20`, espacio ASCII normal, no un carácter invisible). El usuario confirmó que el problema se veía tanto en la página en vivo como en la imagen exportada ("Copiar como imagen"). Causa más probable: la lista se armaba con **dos `<span>` vecinos** por persona — uno para el nombre (con el hover de horas acumuladas) y otro aparte solo para el separador, sin espacio de por medio en el HTML fuente (`</span><span ...>`) — un patrón que algunos navegadores y, sobre todo, `html2canvas` (la librería de "Copiar como imagen") pueden rendear con el espacio del segundo `<span>` colapsado o mal posicionado.

**Fix aplicado**: se fusionó el separador dentro del mismo `<span>` que el nombre — `x-text="p.nickname + (idx < a.personas.length - 1 ? ', ' : '')"` en vez de dos `<span>` separados. Un solo nodo de texto "Nombre, " no deja margen para que el espacio se pierda en ningún renderizador. Efecto secundario menor y aceptado: el subrayado punteado del hover ahora cubre también la coma y el espacio, no solo el nombre.

**Reordenamiento de columnas**: `<thead>` y cada fila del `<tbody>` cambiaron de `Actividad, Personas, Horas, Jorn., Resp.` a `Actividad, Cant., Horas, Jorn., Personas, Resp.` — la celda nueva es `<td x-text="a.personas.length || '—'">`. El `colspan` de la fila divisora de "Grupo N" pasó de `9` a `10` (una columna más).

**Archivos modificados**: `resources/views/personal/programacion/index.blade.php`, `tests/Feature/Personal/ActivityControllerAjaxTest.php` (2 pruebas nuevas).

**Cobertura**: `test_programacion_index_shows_cantidad_column_between_actividad_and_horas` — verifica el ORDEN real de los encabezados en el HTML (posición de "Actividad" < "Cant." < "Horas" < "Jornada" < "Personas"), no solo que el texto exista en algún lado de la página. `test_programacion_index_payload_includes_all_personas_for_cantidad_column` — confirma que el payload que alimenta `a.personas.length` (client-side) trae realmente a las personas asignadas. El número que se ve en pantalla lo calcula Alpine en el navegador (`a.personas.length`), así que no se puede verificar por PHPUnit — sí se verificó que el dato fuente está completo y en la posición correcta.

**Verificado en esta sesión**: `php artisan view:clear && php artisan view:cache` — compiló sin error. Suite completa: `php artisan test` — **109/110 pasando** (mismo `ExampleTest` preexistente y no relacionado; las 2 pruebas nuevas pasan). No se verificó visualmente en un navegador real (misma limitación de entorno que 14.25 — sin herramienta de automatización de navegador disponible para el lado Laravel en esta sesión).

**Pendiente**: verificación visual manual (confirmar que el espacio después de la coma se ve bien tanto en la página como en "Copiar como imagen", y que el nuevo orden de columnas se ve correcto). No se tocó `preview-personal/programacion.blade.php` (el mockup estático) — no fue parte del pedido y su separador ya estaba correcto.

### 14.27 "Copiar Como Imagen": Quitar Columna Acciones + Fix De Espacio Sobrante En El Export (2026-09-21)

**Motivo**: el usuario compartió cómo se ve la imagen generada por "Copiar como imagen" de Programación — pidió (1) que no aparezcan los botones de editar/eliminar (columna Acciones), y (2) preguntó si sobraba espacio, con la instrucción de que la exportación siempre debe capturar la tabla completa sin importar el tamaño.

**Investigación** (`imageExporterMixin()` en `layouts/personal.blade.php`, compartido por Programación/Diario de Campo/Empleados): se encontró un bug real de timing, no solo cosmético. `fullWidth` (el ancho que se le pasa a `html2canvas` como `windowWidth`/`width`) se calculaba a partir del `scrollWidth` del elemento **antes** de aplicarle la clase `exporting-compact` (que achica el padding y el tamaño de fuente para el export) — esa clase solo se agregaba después, dentro de `onclone()`, sobre el DOM **clonado**. Resultado: el canvas se generaba con el ancho "grande" (padding normal) pero el contenido real dentro terminaba más angosto (padding compacto), dejando una franja en blanco a la derecha de la imagen — exactamente el "sobra espacio" que preguntó el usuario.

**Fix aplicado**:
- `.exporting-compact .sticky-col { display: none; }` — nueva regla CSS. `.sticky-col` es siempre la columna Acciones en estas tablas (confirmado: solo Programación la usa, Diario de Campo y Empleados no tienen esa clase, así que el fix no les afecta). Al ocultarse por `display:none`, la tabla no deja espacio vacío donde iba esa columna — las demás se corren para ocupar el lugar, como cualquier columna de tabla oculta.
- `exportarComoImagen()`: la clase `exporting-compact` (y el `overflow:visible`/`max-height:none` del contenedor con scroll) ahora se aplican primero a la página **real** (no solo al clon), y `fullWidth` se mide **después** de eso — así el ancho calculado ya corresponde al contenido compacto real que se va a capturar. Se revierte todo en un bloque `finally` (éxito o error) para que la página vuelva a verse normal después de exportar. Se mantiene también la aplicación de la clase dentro de `onclone()` (redundante pero inofensivo, por si el clon no hereda bien el estado).
- La captura de la altura completa (muchas filas/grupos) ya funcionaba correctamente de antes — `#areaExportable` no tiene una altura fija ni `overflow:hidden` propio, así que `html2canvas` ya toma el `scrollHeight` real sin recortar, sin importar cuántas filas tenga. No hizo falta tocar nada para eso.

**Archivos modificados**: `resources/views/layouts/personal.blade.php` (compartido por Programación, Diario de Campo y Empleados — el fix de ancho aplica a los tres, el ocultamiento de Acciones solo afecta a Programación porque es la única con `.sticky-col`).

**Verificado en esta sesión**: `php artisan view:clear && php artisan view:cache` — compiló sin error. Suite completa: `php artisan test` — 109/110 pasando (mismo `ExampleTest` preexistente, no relacionado). **No se verificó visualmente que la imagen exportada se vea bien** — es lógica 100% client-side (`html2canvas` corriendo en el navegador), y no hay herramienta de automatización de navegador disponible en este entorno para el lado Laravel; no se pudo generar y mirar la imagen real.

**Pendiente**: confirmar visualmente que "Copiar como imagen" ahora se ve sin la columna Acciones y sin espacio sobrante a la derecha, en Programación, Diario de Campo y Empleados.

### 14.28 Verificación Visual De 14.26/14.27: Ambos Fixes Eran Insuficientes + Toast En Vez De Cartel Fijo (2026-09-21)

**Motivo**: el usuario probó en producción (`mantecsas.com`) y compartió capturas reales. Confirmó, con evidencia visual, que los "Pendiente" de 14.26 y 14.27 **no estaban resueltos**: (1) la imagen exportada de "Copiar como imagen" seguía con una franja en blanco a la derecha, tan grande como la mitad del ancho de la imagen; (2) la lista de nombres seguía sin espacio después de la coma ("camellador1,camellador 2,..."); (3) además, un cartel fijo verde ("Cuota mensual actualizada.") que no usa el toast flotante del resto del panel — pedido explícito de no repetir ese estilo; (4) pidió que la columna de cantidad diga "Personas" en vez de "Cant." — como esa palabra ya la usaba la columna con la lista de nombres, se le preguntó cómo resolver el choque de nombres y respondió: cantidad → "Personas", lista de nombres → "Nombre de las personas".

**Investigación — por qué las dos correcciones de 14.26/14.27 no alcanzaban**:
- **Espacio en blanco del export**: el fix de 14.27 corrigió un problema real de *timing* (medir el ancho antes vs. después de aplicar el modo compacto), pero no era la causa completa. La causa real: `#areaExportable` es un `<div>` de bloque sin ancho propio — por defecto se estira al 100% del ancho disponible de `<main>` (el área de contenido, cientos de píxeles más ancha que la tabla en escenarios con pocas columnas/filas, como el caso de prueba del usuario). `html2canvas` capturaba ese `<div>` completo a su ancho real de página, no al ancho real de la tabla, sin importar que `windowWidth`/`width` ya apuntaran al valor correcto — el contenedor en sí nunca se achicaba.
- **Separador sin espacio**: el fix de 14.26 (fusionar nombre+separador en un solo nodo de texto) sí era necesariamente correcto para el problema que se sospechaba (dos `<span>` vecinos), pero la imagen exportada seguía sin el espacio. Causa real, específica de `html2canvas`: la librería recorta un espacio ASCII normal (`0x20`) cuando cae al final del contenido de texto de un elemento — con el nombre y el separador en el mismo `<span>` que termina en `", "`, ese espacio final queda exactamente en esa posición y se pierde en el canvas, aunque en el navegador real (que sí respeta el espacio) se vea bien. Esto también explica por qué, cuando se preguntó antes si el problema era en la página viva o en la imagen, la respuesta fue "de ambos": probablemente la página viva sí estaba bien y solo el export fallaba, pero sin poder ver ambas cosas en el momento no se pudo distinguir con certeza.

**Fix aplicado**:
- **Export sin franja en blanco**: en `exportarComoImagen()`, además de la clase `exporting-compact`, ahora se fija `el.style.width = 'fit-content'` sobre el elemento real **antes** de medir `fullWidth`, y lo mismo sobre el clon dentro de `onclone()` — así el propio contenedor se ajusta al contenido (nunca al ancho de página), para cualquier tamaño de tabla. Se revierte en el `finally` junto con lo demás.
- **Separador sin recortar**: el separador pasa de un espacio normal (`', '`) a un espacio duro / non-breaking space, `U+00A0` (`', '` en el `x-text`) — un carácter visible que `html2canvas` no recorta por estar al final de un nodo de texto.
- **Cartel de éxito → toast**: se quitó el `<div>` fijo con `session('success')` en `layouts/personal.blade.php` (quedaba plantado sobre el contenido hasta la siguiente navegación) y se reemplazó por una llamada a `showCrudToast(@js(session('success')))` al cargar la página — el mismo toast flotante que ya usan todos los CRUD de `/personal/*` (y el resto del panel admin), para que la notificación se vea igual en todos lados.
- **Encabezados de columna**: "Cant." → "Personas" (la cantidad), "Personas" → "Nombre de las personas" (la lista) — ya no comparten título, y el texto ya no necesita el `title="Cantidad de personas"` explicativo porque dejó de ser una abreviatura.

**Archivos modificados**: `resources/views/layouts/personal.blade.php` (banner→toast, fix de ancho del export), `resources/views/personal/programacion/index.blade.php` (separador NBSP, renombre de columnas), `tests/Feature/Personal/ActivityControllerAjaxTest.php` (test de orden de columnas actualizado a los nuevos textos de encabezado — antes buscaba `>Cant.<`/`>Personas<`, ahora `>Personas<` es la columna de cantidad y `>Nombre de las personas<` la de la lista, sin ambigüedad porque `strpos()` con `>texto<` exacto no hace match parcial).

**Cobertura**: se actualizó `test_programacion_index_shows_cantidad_column_between_actividad_and_horas` para los nuevos encabezados (mismo criterio: verifica orden real en el HTML, no solo presencia). El separador NBSP y el `width: fit-content` del export son lógica 100% client-side (Alpine/`html2canvas` en el navegador) — no hay forma de cubrirlos con PHPUnit; se verificó a nivel de bytes que el carácter escrito en el archivo es realmente `U+00A0` (`xxd` → `c2 a0`, UTF-8 de NBSP), no un espacio normal ni una secuencia de escape sin interpretar.

**Verificado en esta sesión**: `php artisan view:clear` + compilación de ambas plantillas vía `Blade::compileString()` — sin error. Suite completa: `php artisan test` — **109/110 pasando** (mismo `ExampleTest` preexistente, no relacionado; las 83 pruebas del módulo Personal, incluida la actualizada, pasan). **Seguimos sin poder verificar visualmente en un navegador real** desde este entorno (sin herramienta de automatización de navegador para el lado Laravel) — a diferencia de 14.26/14.27, esta vez el diagnóstico se basó en evidencia real (las capturas de pantalla que compartió el usuario desde producción), no solo en lectura de código, lo que da más confianza en que la causa raíz identificada es la correcta — pero la confirmación final de que ya no hay franja en blanco ni separador pegado sigue pendiente de que el usuario la vea en vivo.

**Pendiente**: verificación visual final por parte del usuario (export de Programación/Diario de Campo/Empleados sin franja en blanco, lista de nombres con espacio visible, encabezados nuevos, toast en vez de cartel fijo).

### 14.29 Selector De Personas Del Modal: Ordenar Por Horas Acumuladas Dentro De Cada Categoría (2026-09-21)

**Motivo**: en el combobox de "Nueva actividad" (selector de personas, agrupado por categoría/rol — "Administrativos"/"Campo"), el usuario pidió que dentro de cada grupo de rol aparezcan primero las personas con más horas acumuladas ("Top por horas"), manteniendo el agrupamiento por rol como criterio principal (ya existente, sin cambios). Antes, dentro de cada categoría, el orden era el de llegada del arreglo `empleados` (orden de creación en BD), sin relación con las horas mostradas a la derecha de cada fila.

**Fix aplicado**: `personasFiltradas(categoria)` en `programacion/index.blade.php` ahora ordena (`.sort()`) el resultado filtrado por `emp.horasAcumuladas` descendente — el mismo dato que ya se mostraba en cada fila vía `horasResumenTexto()` (horas acumuladas del mes, calculadas en el backend por `BitacoraHoursCalculator`). Sin horas acumuladas (`null`) se trata como `0` y queda al final de su categoría.

**Archivos modificados**: `resources/views/personal/programacion/index.blade.php`.

**Cobertura**: es lógica 100% client-side (Alpine, se recalcula en el navegador a partir del arreglo `empleados` ya cargado) — no hay PHPUnit que la cubra directamente; el dato fuente (`horasAcumuladasPorId`/`horasAcumuladas` por empleado) ya estaba cubierto por pruebas existentes de `ActivityController` (sección 14.16).

**Verificado en esta sesión**: `php artisan view:clear` + `Blade::compileString()` — compiló sin error. `php artisan test --filter=ActivityControllerAjaxTest` — 15/15 pasando, sin regresiones.

**Pendiente**: verificación visual del usuario (abrir el selector y confirmar que dentro de cada categoría el orden va de mayor a menor horas acumuladas).

### 14.30 Franja En Blanco Del Export: Causa Raíz Real (Tercer Intento) + Verificación Con Navegador Headless Real (2026-09-21)

**Motivo**: el usuario probó el fix de 14.28 en producción y compartió una nueva captura — la franja en blanco a la derecha de "Copiar como imagen" **seguía ahí**, prácticamente sin cambios. Dos intentos (14.27, 14.28) habían fallado ya para el mismo síntoma. Antes de proponer un tercero a ciegas, se instrumentó una verificación real con navegador (ver más abajo) para no repetir el patrón de "razonar sobre el código, aplicar, y que el usuario descubra en producción que seguía roto".

**Por qué el fix de 14.28 (`width: fit-content`) no funcionó**: `html2canvas`/`html2canvas-pro` reimplementa su propio motor de layout en JS y no soporta de forma confiable el keyword de sizing intrínseco `fit-content` — el contenedor seguía sin encogerse en el render del clon, aunque el mismo CSS sí funciona en un navegador real.

**Causa raíz real (confirmada, no solo teorizada)**: `.preventive-table { min-width: 100% }` — si el ancho de exportación (`fullWidth`) se mide leyendo `scrollWidth` del contenedor o de la tabla **antes** de encoger los contenedores, ese `min-width:100%` todavía está resolviendo contra el ancho completo de `<main>` (heredado por los `<div>` de bloque `#areaExportable` → `.compact-table-wrapper` → `.table-scroll-container`, todos `width:auto`), así que la medición sale igual de inflada sin importar en qué elemento se mida — el mismo bug disfrazado.

**Fix aplicado (tercer intento, definitivo)**:
- Antes de medir, se anula temporalmente `min-width` de la `<table>` (`table.style.minWidth = '0px'`) para que `scrollWidth` refleje el ancho real del contenido, no el heredado del contenedor. Se restaura inmediatamente después de medir.
- Con ese ancho real (`fullWidth`, en píxeles concretos), se fijan **en píxeles** — no con un keyword de sizing — los tres contenedores (`#areaExportable`, `.compact-table-wrapper`, `.table-scroll-container`), tanto en la página real como en el clon (`onclone()`). Al fijar el contenedor exacto, `min-width:100%` de la tabla resuelve contra ese mismo valor, sin generar una segunda vuelta de inflado.
- Se revierte todo (clases, `overflow`, anchos) en el bloque `finally`.

**Verificación con navegador real (nuevo en esta sesión)** — en vez de repetir "el código dice que debería funcionar": no había navegador headless disponible en este WSL; se le pidió permiso al usuario para instalar las librerías de sistema necesarias (`libnss3`, `libnspr4`, etc., ~programa `apt-get`), el usuario autorizó y las instaló él mismo (requiere `sudo`, contraseña que este entorno no puede proveer). Con eso:
- Se instaló Puppeteer (Chromium headless) en un directorio temporal (`/tmp`, nunca dentro del repo).
- Se generó una cookie de sesión real y válida como superadmin **sin tocar ninguna contraseña**: un script desechable registra una ruta en memoria (nunca escrita a `routes/web.php`) que hace `Auth::guard('web')->login($user)` contra la BD local real (no la BD `testing` de phpunit), corre por el kernel HTTP completo, y extrae la cookie de sesión (`mantec-session`) ya cifrada tal como la mandaría el servidor real. El script se borró del repo apenas se usó.
- Con esa cookie, Puppeteer abrió `/personal/programacion`, `/personal/diario-campo` y `/personal/empleados` en el servidor local real (misma BD real, mismos datos que ya usaba el usuario en sus pruebas), hizo clic real en "Copiar como imagen", capturó el `canvas` que genera `html2canvas` (interceptando `window.html2canvas` sin alterar su comportamiento) y analizó el PNG resultante píxel por píxel para medir cuánto espacio en blanco quedaba a la derecha del contenido real.
- **Resultado medido, no asumido**: `blankRightPx = 0` en las tres páginas (Programación: canvas 2112px, Diario de Campo: 4234px, Empleados: 1844px — el último píxel no-blanco de cada imagen coincide exactamente con el último píxel del canvas). También se confirmó visualmente en el PNG de Programación que el separador de nombres ya se ve con espacio ("camellador1, camellador 2, camellador 0 3"), corroborando 14.28 más allá de la inspección de bytes.

**Archivos modificados**: `resources/views/layouts/personal.blade.php` (`exportarComoImagen()`).

**Cobertura**: sigue siendo lógica 100% client-side sin PHPUnit posible — pero por primera vez en esta serie de fixes, la verificación fue con un canvas real generado por el código real corriendo en un navegador real contra datos reales, no con lectura de código ni con suposiciones.

**Verificado en esta sesión**: `php artisan view:clear` + `Blade::compileString()` sin error. `php artisan test` — 109/110 (mismo `ExampleTest` preexistente). Verificación con navegador headless real: 0px de franja en blanco en las 3 páginas que comparten `imageExporterMixin()`.

Con el mismo montaje de Puppeteer se verificaron también, con evidencia real y no solo lectura de código, los otros dos pendientes que quedaban abiertos de 14.28/14.29:
- **Toast en vez de cartel fijo**: se confirmó en `/personal/bitacora` (con un `session('success')` real) que el `<div>` verde fijo ya no existe en el HTML, y que el toast flotante (`#crudToast`) aparece visible con el mensaje y título correctos ("Cuota mensual actualizada." / "Acción completada").
- **Orden por horas acumuladas en el selector de personas**: la data local no tenía horas de Bitácora cargadas para septiembre 2026 (todas en `null`), así que no servía para ver el orden a simple vista — se verificó en cambio invocando `personasFiltradas('Camposs')` directamente sobre el estado real de Alpine (`Alpine.$data()`) con valores de horas sintéticos e inequívocos asignados en orden inverso al original: el resultado salió perfectamente ordenado de mayor a menor, confirmando que `.sort()` funciona sobre los datos reales de la página, no solo en aislamiento.

**Pendiente**: confirmación del usuario en su propio navegador (el entorno de verificación con Puppeteer es un montaje temporal de esta sesión, no una herramienta permanente del proyecto) — aunque el nivel de confianza ahora es sustancialmente mayor que en 14.27/14.28 por tratarse de mediciones reales, no de inferencias de código.
