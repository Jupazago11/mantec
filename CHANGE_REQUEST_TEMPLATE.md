# Change Request Template

Usar esta plantilla cada vez que se solicite un cambio relevante en el proyecto.

## 1. Objetivo

- Problema a resolver:
- Resultado esperado:
- Restricciones de negocio:
- Restricciones tecnicas:

## 2. Alcance

- Modulos implicados:
- Flujo web implicado:
- Flujo API implicado:
- Base de datos implicada:
- Frontend implicado:
- Integraciones implicadas:

## 3. Requisitos Obligatorios Para El Agente

Antes de implementar debe completar este flujo:

1. Leer contexto.
2. Inspeccionar.
3. Definir alcance.
4. Implementar.
5. Autorizar y asegurar.
6. Probar.
7. Verificar interfaz.
8. Revisar efectos colaterales.
9. Documentar.
10. Revisar diff.
11. Informar resultado.

## 4. Preflight Obligatorio

El agente debe informar antes de editar:

- Contexto revisado.
- Problema a resolver.
- Alcance previsto.
- Archivos probablemente afectados.
- Tablas o migraciones afectadas.
- Riesgos.
- Validaciones previstas.
- Documentacion a actualizar.

## 5. Informe Final Obligatorio

El agente debe cerrar con este formato:

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

## 6. Regla De Honestidad

No ocultar pruebas fallidas.
No presentar exito parcial como exito completo.
No omitir validaciones no ejecutadas.
