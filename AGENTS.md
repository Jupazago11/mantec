# AGENTS

Este archivo define el protocolo obligatorio para cualquier agente que vaya a modificar este repositorio.

## 1. Regla Principal

Antes de proponer, editar o ejecutar cambios de codigo, el agente debe leer contexto suficiente del sistema. No debe saltar directo a implementar por intuicion.

Si no ha revisado el contexto minimo exigido en este archivo, debe detenerse y hacerlo primero.

## 2. Orden Obligatorio De Contexto

Antes de modificar codigo debe revisar, como minimo, en este orden:

1. [ANALISIS_SISTEMA_LARAVEL.md](/home/jupazago/Documentos/mantecv1/mantec/ANALISIS_SISTEMA_LARAVEL.md)
2. [ARRANCAR_LOCAL.txt](/home/jupazago/Documentos/mantecv1/mantec/ARRANCAR_LOCAL.txt) si el cambio depende de entorno, servicios, base de datos, Railway, Vite o Sail.
3. Estado actual de Git.
4. Rutas relacionadas.
5. Controladores o endpoints relacionados.
6. Modelos relacionados.
7. Servicios relacionados.
8. Migraciones y estructura de datos relacionadas.
9. Policies, Gates o reglas de autorizacion relacionadas, si existen.
10. Componentes Livewire relacionados, si existen.
11. Vistas Blade, Alpine o JavaScript relacionado.
12. Pruebas existentes.
13. Dependencias involucradas en composer.json, package.json o config asociada.
14. Estado de contenedores o servicios del proyecto si el cambio requiere verificar entorno.

## 3. Contexto Minimo Tecnico Que Debe Revisar

Antes de tocar codigo debe verificar explicitamente:

- Archivos relacionados al flujo.
- Modelos implicados.
- Migraciones implicadas.
- Servicios implicados.
- Policies o autorizaciones del servidor.
- Componentes Livewire implicados, si aplica.
- Vistas Blade implicadas.
- Rutas web o API implicadas.
- Pruebas existentes.
- Dependencias o paquetes afectados.
- Estado de Git.
- Contenedores Docker/Sail y servicios auxiliares cuando aplique.

## 4. Declaracion Obligatoria De Alcance

Antes de escribir codigo, el agente debe indicar explicitamente:

- Problema que va a resolver.
- Comportamiento esperado.
- Archivos que probablemente se modificaran.
- Tablas, modelos o migraciones afectadas.
- Riesgos tecnicos.
- Pruebas que deben correrse o escribirse.
- Documentacion que debe actualizarse.

Si no puede definir ese alcance, todavia no tiene suficiente contexto.

## 5. Reglas De Arquitectura

El agente debe:

- Realizar cambios minimos y coherentes.
- Evitar refactorizaciones no relacionadas.
- Reutilizar servicios existentes antes de crear nuevos.
- Evitar duplicar logica.
- Mantener separacion por dominios.
- No poner logica pesada en Blade, Alpine o controladores.
- No crear componentes Livewire gigantes.
- No agregar dependencias sin justificar.
- No cambiar versiones de paquetes sin necesidad real.
- Implementar por bloques pequenos y verificables.

## 6. Seguridad Y Autorizacion

Toda operacion debe comprobar en servidor, no solo en frontend:

- Usuario autenticado.
- Permiso tecnico.
- Modulo habilitado.
- Feature habilitada.
- Limite disponible.
- Propiedad o alcance del registro.
- Estado valido del registro.

El agente no debe confiar unicamente en:

- IDs enviados por URL.
- IDs enviados por request.
- Campos ocultos.
- Validacion frontend.
- Ocultamiento visual de botones.

La autorizacion real debe ejecutarse en backend.

## 7. Base De Datos Y Migraciones

Si el cambio modifica datos o estructura, debe revisar:

- Si realmente se necesita migracion.
- Que la migracion sea reversible.
- Tipos adecuados para PostgreSQL.
- Indices necesarios.
- Claves foraneas.
- Restricciones unicas cuando aplique.
- Si debe contemplarse company_id, client_id o alcance equivalente.
- Si aplica soft delete.
- Si aplica auditoria.
- Si afecta datos historicos o sincronizacion con Android.

## 8. Pruebas Obligatorias

Todo cambio funcional debe incluir pruebas nuevas o ajuste de pruebas existentes, salvo que el usuario pida explicitamente no hacerlo o exista una limitacion tecnica documentada.

Segun el caso, evaluar:

- Unit tests.
- Feature tests.
- Livewire tests.
- Authorization tests.
- Multi-tenant isolation tests.
- Database tests.
- Integration tests.

Como minimo debe contemplar:

- Camino exitoso.
- Validaciones.
- Usuario sin permiso.
- Acceso a empresa o cliente ajeno.
- Estado invalido.
- Feature deshabilitada.
- Limite agotado.
- Registro archivado o inactivo.
- Doble envio, si aplica.
- Rollback ante error, si aplica.

## 9. Documentacion Obligatoria

Todo cambio que altere comportamiento, arquitectura, permisos, rutas, base de datos, integraciones o flujo operativo debe actualizar documentacion.

Como minimo evaluar si debe actualizar:

- [ANALISIS_SISTEMA_LARAVEL.md](/home/jupazago/Documentos/mantecv1/mantec/ANALISIS_SISTEMA_LARAVEL.md)
- [ARRANCAR_LOCAL.txt](/home/jupazago/Documentos/mantecv1/mantec/ARRANCAR_LOCAL.txt)
- README del proyecto.
- Comentarios tecnicos puntuales en codigo, si ayudan.

## 10. Verificacion De Entorno

Si el cambio depende de ejecucion local, el agente debe revisar:

- si PostgreSQL local o Sail son parte del flujo
- si Vite o build de assets esta involucrado
- si existen contenedores necesarios levantados
- si hay puertos en conflicto
- si el cambio afecta Railway, R2 o variables de entorno

## 11. Formato Obligatorio Antes De Implementar

Antes de editar, el agente debe comunicar un mini preflight con esta estructura:

- Contexto revisado.
- Problema a resolver.
- Alcance previsto.
- Riesgos.
- Validacion o pruebas previstas.
- Documentacion a actualizar.

No debe empezar a codificar antes de tener esto claro.

## 12. Regla De Cambios Pequenos

Cada funcionalidad debe implementarse por bloques verificables.

Preferencias:

- 1 cambio logico por vez.
- 1 migracion por necesidad concreta.
- 1 prueba o grupo pequeno de pruebas por comportamiento.
- 1 actualizacion documental al cerrar el cambio.

## 13. Regla De No Suposicion

Si el agente sospecha que un flujo toca API, sincronizacion Android, reportes, storage o permisos, debe confirmarlo en codigo antes de responder.

No debe asumir que una vista web es el unico punto afectado.

## 14. Regla De Estado Del Repositorio

Antes de editar debe revisar git status y evitar sobrescribir trabajo ajeno.

Si encuentra cambios no suyos en archivos relacionados, debe:

- entender si son compatibles con su tarea
- trabajar sobre ellos con cuidado
- detenerse y avisar solo si hay conflicto real

## 15. Regla Para Agentes Nuevos En El Proyecto

Si un agente llega sin contexto previo, debe leer primero:

1. AGENTS.md
2. ANALISIS_SISTEMA_LARAVEL.md
3. el codigo puntual del flujo pedido

## 17. Secuencia Operativa Obligatoria

Todo cambio debe seguir esta secuencia, en este orden:

1. Leer contexto.
2. Inspeccionar codigo y entorno relacionado.
3. Definir alcance.
4. Implementar.
5. Autorizar y asegurar.
6. Probar.
7. Verificar interfaz.
8. Revisar efectos colaterales.
9. Documentar.
10. Revisar diff.
11. Informar resultado.

El agente no debe saltarse pasos ni mezclar implementacion con conclusiones sin haber pasado por validacion.

## 18. Informe Final Obligatorio

Al terminar, el agente debe entregar un informe final claro y honesto.

Debe incluir siempre estas secciones:

### Implementado

- Que cambio.
- Que comportamiento nuevo existe.

### Archivos afectados

- Archivos principales.
- Migraciones.
- Pruebas.
- Documentacion.

### Validaciones

- Pruebas ejecutadas.
- Formato.
- Analisis estatico.
- Build frontend.

### Resultado

- Que paso correctamente.
- Que fallo.
- Que no pudo verificarse.

### Pendientes

- Riesgos.
- Decisiones abiertas.
- Proximo bloque recomendado.

## 19. Regla De Honestidad Tecnica

El agente no debe ocultar pruebas fallidas.

El agente no debe presentar un exito parcial como si fuera un exito completo.

Si no pudo verificar algo, debe decirlo de forma explicita.

Si una validacion no corrio, debe indicarlo.

Si el cambio quedo incompleto, debe reportarlo como incompleto.

## 20. Endurecimiento Del Flujo

Para volver este protocolo mas estricto, el repositorio incluye:

- `CHANGE_REQUEST_TEMPLATE.md` para pedir cambios con alcance y preflight definidos.
- `.github/PULL_REQUEST_TEMPLATE.md` para no cerrar cambios sin validaciones y resultados reales.
- `.githooks/commit-msg` para bloquear commits cuyo mensaje no declare preflight o validacion.
- `scripts/install-git-hooks.sh` para activar `core.hooksPath` en el clon local.

Si quieres que el hook quede activo en tu clon actual, ejecutar:

`bash scripts/install-git-hooks.sh`

## 20. Archivos Canonicos De Contexto

Los archivos de referencia para contexto del proyecto son:

- [AGENTS.md](/home/jupazago/Documentos/mantecv1/mantec/AGENTS.md)
- [ANALISIS_SISTEMA_LARAVEL.md](/home/jupazago/Documentos/mantecv1/mantec/ANALISIS_SISTEMA_LARAVEL.md)
- [ARRANCAR_LOCAL.txt](/home/jupazago/Documentos/mantecv1/mantec/ARRANCAR_LOCAL.txt)

