# Módulo: ToDo / Agenda

## Objetivo

El módulo de **ToDo / Agenda** tiene como objetivo permitir al usuario organizar, programar, consultar y dar seguimiento a sus tareas, compromisos y actividades desde una agenda visual.

La prioridad del módulo será ofrecer una **visualización mediante calendario**, permitiendo al usuario comprender rápidamente:

* Qué tiene pendiente.
* Qué debe hacer hoy.
* Qué debe hacer próximamente.
* Qué tareas están atrasadas.
* Qué tareas fueron completadas.
* Qué actividades tiene programadas para una fecha determinada.
* Qué compromisos requieren atención.

El módulo deberá funcionar como una herramienta de seguimiento diario y no únicamente como un listado de tareas.

El sistema deberá poner especial énfasis en las tareas:

* Pendientes.
* Próximas a vencer.
* Vencidas.
* Recurrentes.
* De alta prioridad.
* Que llevan varios días sin completarse.

El objetivo es que el usuario pueda abrir el módulo y comprender inmediatamente:

> **¿Qué tengo que hacer hoy y qué cosas estoy dejando pendientes?**

---

# Alcance del MVP

Esta primera versión implementará:

* Calendario mensual.
* Calendario semanal.
* Vista diaria.
* Detalle de actividades por día.
* Creación de tareas.
* Edición de tareas.
* Eliminación de tareas.
* Marcar tareas como completadas.
* Reabrir tareas completadas.
* Fecha de inicio.
* Fecha límite.
* Hora opcional.
* Prioridad.
* Estado.
* Descripción.
* Categoría.
* Notas.
* Tareas pendientes.
* Tareas atrasadas.
* Tareas para hoy.
* Tareas próximas.
* Notificaciones.
* Recordatorios.
* Seguimiento de tareas atrasadas.
* Indicadores de cumplimiento.
* Filtros.
* Búsqueda.
* Vista de detalle de una tarea.
* Configuración básica de recordatorios.

No se implementarán todavía:

* Gestión avanzada de proyectos.
* Dependencias complejas entre tareas.
* Gestión de recursos.
* Gestión avanzada de equipos.
* Gantt.
* Kanban avanzado.
* Gestión de reuniones.
* Videoconferencias.
* Integración con Google Calendar.
* Integración con Microsoft Outlook.
* Integración con calendarios externos.
* Automatización avanzada mediante IA.
* Gestión empresarial completa de proyectos.

La arquitectura deberá quedar preparada para incorporar estas funcionalidades posteriormente.

---

# Principio Fundamental

El módulo no debe limitarse a mostrar una lista de tareas.

Debe responder permanentemente a tres preguntas:

```text
¿Qué debo hacer?

¿Cuándo debo hacerlo?

¿Qué estoy dejando atrasado?
```

El sistema deberá priorizar la visibilidad de los compromisos pendientes y atrasados.

---

# Arquitectura

Seguir todas las reglas establecidas en `AGENTS.md`.

Aplicar:

* SOLID
* Clean Architecture
* DRY
* KISS
* PSR-12

Toda la lógica de negocio deberá implementarse utilizando:

* Services.
* Actions.
* DTOs cuando aporten claridad.
* Enums para estados y prioridades.
* Policies cuando sean necesarias.

Los componentes Livewire únicamente deberán administrar:

* Interfaz.
* Estado visual.
* Interacciones.
* Eventos de usuario.

No colocar lógica de negocio en Blade.

No colocar lógica compleja en Controllers.

La lógica relacionada con:

* fechas
* vencimientos
* tareas atrasadas
* recurrencias
* recordatorios
* prioridades
* estados
* cálculo de pendientes

deberá permanecer fuera de la capa de presentación.

---

# Arquitectura Orientada a Calendario

El calendario será el elemento principal del módulo.

La navegación principal deberá permitir visualizar:

```text
Mes
Semana
Día
```

La experiencia deberá estar diseñada alrededor de la fecha seleccionada.

No se deberá diseñar primero un CRUD y posteriormente agregarle un calendario.

El calendario deberá ser considerado una parte central de la arquitectura del módulo.

---

# Vista Principal

Al ingresar al módulo deberá mostrarse una agenda visual.

Ejemplo conceptual:

```text
                 AGOSTO 2026

      L     M     M     J     V     S     D
     ───────────────────────────────────────
      3     4     5     6     7     8     9
     10    11    12    13    14    15    16
     17    18    19    20    21    22    23
     24    25    26    27    28    29    30
     31

Hoy: 9 de agosto

Pendientes: 7
Atrasadas: 2
Alta prioridad: 3
```

El usuario deberá poder seleccionar cualquier día.

---

# Detalle por Día

Al seleccionar una fecha deberá mostrarse el detalle de las actividades correspondientes a ese día.

Ejemplo:

```text
Lunes 10 de agosto

08:00
Reunión comercial

10:00
Llamar a cliente ABC

12:00
Almuerzo

14:00
Revisar propuesta

16:30
Enviar reporte

Sin hora
Preparar documentación
```

Las tareas sin hora deberán aparecer separadas de las actividades programadas con horario.

---

# Navegación del Calendario

El usuario deberá poder:

* Ir al mes anterior.
* Ir al mes siguiente.
* Ir a hoy.
* Seleccionar una fecha.
* Cambiar entre mes, semana y día.

La navegación deberá ser rápida.

La fecha seleccionada deberá mantenerse al cambiar de vista cuando sea apropiado.

---

# Tarea

Una tarea deberá representar una actividad concreta que el usuario necesita realizar.

Datos mínimos:

* Título.
* Descripción.
* Fecha.
* Hora opcional.
* Fecha límite.
* Prioridad.
* Estado.
* Categoría.
* Notas.

La arquitectura deberá permitir posteriormente agregar:

* Archivos.
* Enlaces.
* Contactos relacionados.
* Clientes relacionados.
* Proyectos.
* Etiquetas.
* Subtareas.
* Dependencias.
* Asignación a usuarios.

---

# Fecha de Inicio

Una tarea podrá tener:

* Fecha de inicio.
* Fecha límite.

Ejemplo:

```text
Inicio:
10 agosto

Fecha límite:
12 agosto
```

Esto permitirá diferenciar entre:

* tareas programadas
* tareas pendientes
* tareas atrasadas

---

# Hora

La hora deberá ser opcional.

Una tarea puede ser:

```text
10:00 AM
Llamar a cliente
```

o:

```text
Sin hora
Preparar propuesta
```

Las tareas sin hora deberán seguir apareciendo correctamente en el calendario y en la agenda diaria.

---

# Prioridades

El sistema deberá manejar prioridades.

Valores iniciales:

```text
LOW
NORMAL
HIGH
URGENT
```

La interfaz deberá representar claramente la prioridad.

La prioridad no deberá depender exclusivamente del color.

Deberá existir una indicación textual o visual adicional.

---

# Estados

Las tareas deberán manejar estados explícitos.

Estados iniciales:

```text
PENDING
IN_PROGRESS
COMPLETED
CANCELLED
```

La arquitectura deberá permitir posteriormente agregar:

* WAITING
* BLOCKED
* DEFERRED

Los estados deberán implementarse mediante Enum o mecanismo equivalente.

---

# Flujo de una Tarea

Flujo normal:

```text
Creada
  ↓
Pendiente
  ↓
En progreso
  ↓
Completada
```

Si no se completa:

```text
Pendiente
  ↓
Fecha límite alcanzada
  ↓
ATRASADA
```

Una tarea atrasada no necesariamente deberá tener un estado independiente.

Puede determinarse combinando:

```text
status = PENDING
+
due_date < today
```

Esto evita duplicar información.

---

# Tareas Atrasadas

Este será uno de los elementos prioritarios del módulo.

El sistema deberá identificar automáticamente las tareas cuyo vencimiento haya pasado y que todavía no estén completadas.

Ejemplo:

```text
⚠️ Tareas atrasadas

2 días
Enviar propuesta comercial

1 día
Llamar proveedor

5 días
Revisar contrato
```

Las tareas atrasadas deberán tener una visualización claramente diferenciada.

---

# Seguimiento de Atrasos

El sistema deberá mostrar cuánto tiempo lleva atrasada una tarea.

Ejemplos:

```text
Atrasada 1 día
Atrasada 3 días
Atrasada 1 semana
Atrasada 2 semanas
```

Esto permitirá identificar tareas que están siendo constantemente postergadas.

---

# Tareas de Hoy

El sistema deberá mostrar claramente las tareas correspondientes al día actual.

Ejemplo:

```text
HOY

08:30  Reunión
10:00  Llamar cliente
13:00  Almuerzo
15:00  Revisar propuesta

Sin hora

□ Preparar documentación
□ Revisar pendientes
```

La sección "Hoy" deberá ser una de las principales áreas de la experiencia.

---

# Próximas Tareas

El sistema deberá mostrar las tareas próximas.

Ejemplo:

```text
PRÓXIMAMENTE

Mañana
Enviar contrato

Miércoles
Reunión con proveedor

Viernes
Presentar informe
```

Esto permitirá anticipar compromisos.

---

# Resumen del Dashboard

El módulo deberá mostrar indicadores rápidos.

Ejemplo:

```text
┌──────────────┐
│ HOY          │
│      6       │
└──────────────┘

┌──────────────┐
│ PENDIENTES   │
│      12      │
└──────────────┘

┌──────────────┐
│ ATRASADAS    │
│      3       │
└──────────────┘

┌──────────────┐
│ COMPLETADAS  │
│      18      │
└──────────────┘
```

Estos indicadores deberán ser accionables.

Al hacer clic sobre:

**Atrasadas**

deberá mostrarse el listado correspondiente.

---

# Notificaciones

El sistema deberá notificar al usuario sobre tareas que requieren atención.

Tipos iniciales:

* Recordatorio antes de una tarea.
* Recordatorio al momento de la tarea.
* Aviso de tarea atrasada.
* Aviso de tareas pendientes.
* Resumen de tareas del día.

Las notificaciones deberán poder configurarse posteriormente.

---

# Recordatorios

Una tarea podrá tener un recordatorio.

Ejemplos:

```text
15 minutos antes
30 minutos antes
1 hora antes
1 día antes
```

La arquitectura deberá permitir otros intervalos.

---

# Notificación de Tarea

Ejemplo:

```text
🔔 Recordatorio

En 15 minutos:

Reunión con cliente ABC

10:00 AM
```

La notificación deberá permitir acceder directamente al detalle de la tarea.

---

# Notificación de Tarea Atrasada

Cuando una tarea pase a estar atrasada, el sistema podrá generar una alerta.

Ejemplo:

```text
⚠️ Tarea atrasada

"Enviar propuesta comercial"

Venció ayer.

[ Ver tarea ]
```

La arquitectura deberá evitar generar notificaciones repetitivas innecesarias.

---

# Seguimiento de Pendientes

El sistema deberá proporcionar una vista específica para tareas pendientes.

Ejemplo:

```text
PENDIENTES

Alta prioridad
────────────────
□ Enviar propuesta
□ Revisar contrato

Normal
────────────────
□ Actualizar documentación
□ Revisar correo

Baja
────────────────
□ Organizar archivos
```

Los filtros deberán permitir encontrar rápidamente las tareas que necesitan atención.

---

# Filtros

El usuario deberá poder filtrar por:

* Estado.
* Prioridad.
* Fecha.
* Categoría.
* Tareas atrasadas.
* Tareas de hoy.
* Tareas próximas.
* Tareas completadas.

La arquitectura deberá permitir incorporar posteriormente filtros adicionales.

---

# Búsqueda

El módulo deberá permitir buscar tareas por:

* Título.
* Descripción.
* Notas.
* Categoría.

La búsqueda deberá ser rápida.

---

# Categorías

Las tareas podrán clasificarse.

Ejemplos:

* Personal.
* Trabajo.
* Comercial.
* Clientes.
* Administración.
* Finanzas.
* Tecnología.
* Reuniones.
* Seguimiento.

La lista definitiva deberá adaptarse a las necesidades del proyecto.

La arquitectura deberá permitir administrar categorías posteriormente.

---

# Recurrencia

La arquitectura deberá quedar preparada para tareas recurrentes.

Ejemplos:

```text
Diaria
Semanal
Mensual
Anual
```

No es necesario implementar recurrencias complejas en el MVP si esto aumenta significativamente la complejidad.

Si se implementa recurrencia en el MVP, deberá diseñarse de forma que las ocurrencias puedan rastrearse individualmente.

---

# Tareas Completadas

El usuario deberá poder marcar una tarea como completada.

Ejemplo:

```text
□ Enviar propuesta

↓ completar

✓ Enviar propuesta
```

Al completarse:

* Dejar de aparecer como pendiente.
* Dejar de considerarse atrasada.
* Registrar fecha y hora de finalización.
* Mantenerse disponible en el historial.

---

# Reapertura de Tarea

El usuario deberá poder reabrir una tarea completada.

Al reabrir:

```text
COMPLETED
    ↓
PENDING
```

La fecha original de finalización deberá conservarse en el historial si existe infraestructura de auditoría.

---

# Eliminación

Las tareas no deberán eliminarse físicamente cuando sea necesario preservar historial.

Siempre que sea compatible con la arquitectura existente, utilizar:

* Soft Deletes.
* Estado cancelado.

La eliminación definitiva deberá reservarse para casos administrativos específicos.

---

# Detalle de Tarea

El detalle deberá mostrar:

```text
Título

Estado
Prioridad

Fecha de inicio
Fecha límite
Hora

Categoría

Descripción

Notas

Recordatorios

Historial
```

Deberá existir una acción clara para:

* Editar.
* Completar.
* Reabrir.
* Cancelar.
* Eliminar.

---

# Historial

La arquitectura deberá permitir conocer cambios importantes de una tarea.

Ejemplo:

```text
09 Ago 10:30
Tarea creada

09 Ago 11:00
Prioridad cambiada a Alta

10 Ago 08:20
Tarea iniciada

10 Ago 15:40
Tarea completada
```

No necesariamente deberá mostrarse todo el historial en el MVP, pero la estructura deberá quedar preparada.

---

# Auditoría

Registrar cuando corresponda:

* Usuario creador.
* Usuario modificador.
* Fecha de creación.
* Fecha de modificación.
* Fecha de finalización.
* Usuario que completó la tarea.

Preparar la estructura para almacenar historial de cambios.

---

# Calendario y Estados Visuales

El calendario deberá comunicar el estado de las tareas de manera rápida.

Ejemplo conceptual:

```text
        L   M   M   J   V   S   D
       ───────────────────────────
        3   4   5   6   7   8   9
       •   •       •
       10  11  12  13  14  15  16
           ⚠       •
```

Las tareas deberán poder representarse mediante:

* indicadores
* etiquetas
* contadores
* estados
* prioridades

No depender exclusivamente del color.

---

# Vista Mensual

La vista mensual deberá permitir comprender la carga de trabajo del mes.

Deberá mostrar:

* tareas por día
* cantidad de tareas
* tareas prioritarias
* tareas atrasadas
* eventos con horario

Al seleccionar un día deberá abrirse su detalle.

---

# Vista Semanal

La vista semanal deberá permitir analizar las actividades próximas.

Ejemplo:

```text
          LUN   MAR   MIÉ   JUE   VIE

08:00      ●
09:00            ●
10:00      ●           ●
11:00                  ●
14:00            ●
15:00                        ●
```

La implementación deberá priorizar legibilidad.

---

# Vista Diaria

La vista diaria será la más detallada.

Deberá mostrar:

```text
09:00
Reunión

10:30
Llamar cliente

12:00
Almuerzo

14:00
Preparar propuesta

Sin horario
----------------
Revisar documentación
Actualizar CRM
```

Las tareas deberán poder marcarse como completadas directamente desde esta vista.

---

# Tareas Sin Fecha

La arquitectura deberá permitir posteriormente tareas sin fecha.

Sin embargo, en el MVP se recomienda diferenciar claramente:

**Tarea sin fecha**

de

**Tarea programada.**

Una tarea sin fecha no deberá aparecer automáticamente en un día del calendario.

Deberá existir una sección:

```text
Sin fecha
```

para evitar perderla.

---

# Tareas Atrasadas y Reprogramación

Cuando una tarea esté atrasada, el usuario deberá poder:

* Completarla.
* Reprogramarla.
* Cambiar la fecha.
* Cambiar prioridad.
* Cancelarla.

Ejemplo:

```text
⚠️ Atrasada 3 días

Enviar propuesta

[ Completar ]
[ Reprogramar ]
[ Cancelar ]
```

La acción de reprogramar deberá ser sencilla y rápida.

---

# Reprogramación Rápida

La interfaz deberá facilitar acciones como:

```text
Hoy
Mañana
Próxima semana
Elegir fecha
```

Esto será especialmente importante para tareas atrasadas.

---

# Seguimiento de Tareas Críticas

El sistema deberá destacar tareas:

* Urgentes.
* De alta prioridad.
* Atrasadas.
* Próximas a vencer.

Una tarea que cumpla varias condiciones deberá tener una visibilidad superior.

Ejemplo:

```text
⚠️ URGENTE
Atrasada 2 días

Enviar documentación al cliente
```

---

# Ordenamiento

Los listados deberán poder ordenarse por:

* Fecha.
* Prioridad.
* Estado.
* Fecha límite.
* Creación.

El orden predeterminado deberá priorizar las tareas que requieren atención.

Por ejemplo:

1. Atrasadas.
2. Urgentes.
3. Alta prioridad.
4. Vencen hoy.
5. Próximas.
6. Sin prioridad.

---

# Arquitectura de Fechas

Toda la lógica relacionada con fechas deberá centralizarse.

No duplicar cálculos de:

* hoy
* mañana
* atrasado
* próximo
* vencido

en múltiples componentes.

Crear servicios o clases de dominio reutilizables cuando sea necesario.

Esto permitirá evitar inconsistencias.

---

# Zona Horaria

Las fechas y horas deberán respetar la configuración del usuario o de la aplicación.

La arquitectura deberá evitar asumir una zona horaria fija dentro de la lógica de negocio.

Las fechas deberán almacenarse y presentarse siguiendo las convenciones existentes del proyecto.

---

# Notificaciones Globales

Las notificaciones de tareas deberán poder mostrarse independientemente del módulo actual.

Por ejemplo, si el usuario está en:

```text
Clientes
```

y tiene una tarea cuyo recordatorio debe aparecer, la alerta deberá poder mostrarse.

No deberá ser necesario estar dentro del módulo ToDo.

La arquitectura deberá seguir el mismo principio utilizado por el módulo de descansos:

> **Las notificaciones son transversales a la aplicación.**

---

# Integración con el Módulo de Descansos

Si ambos módulos existen dentro de la misma aplicación, deberán coexistir sin duplicar mecanismos globales.

El sistema podrá posteriormente utilizar el servicio global de notificaciones para:

* recordatorio de pausa
* recordatorio de tarea
* tarea atrasada
* eventos del calendario

No se deberá crear un sistema independiente de notificaciones para cada módulo si ya existe una infraestructura global reutilizable.

---

# Menú

La ubicación deberá adaptarse a la estructura actual de la aplicación.

Ejemplo:

```text
Productividad

- Agenda
- Tareas
- Calendario
```

Se deberá evitar crear múltiples entradas para funcionalidades que conceptualmente pertenecen al mismo módulo.

La recomendación para el MVP es utilizar una única entrada:

**Agenda**

y desde ella acceder a tareas y calendario.

---

# Dashboard Global

La arquitectura deberá permitir posteriormente mostrar un resumen de tareas fuera del módulo.

Por ejemplo, en el dashboard principal:

```text
Mis tareas de hoy

□ Llamar cliente
□ Revisar propuesta
⚠️ Enviar contrato
□ Preparar reunión

3 pendientes
1 atrasada
```

No es obligatorio implementarlo en el MVP, pero deberá evitarse diseñar el módulo de manera que impida esta integración.

---

# Base de Datos

Posibles entidades:

```text
tasks
task_categories
task_reminders
task_recurrences
task_history
```

Los nombres definitivos deberán respetar las convenciones existentes del proyecto.

Antes de crear cualquier tabla deberá verificarse si ya existe una estructura reutilizable.

---

# Relaciones Futuras

La arquitectura deberá quedar preparada para relacionar tareas con otras entidades.

Ejemplos:

```text
Task
 ├── User
 ├── Customer
 ├── Project
 ├── Category
 ├── Reminder
 └── Related Entity
```

No es necesario implementar todas estas relaciones en el MVP.

---

# Migraciones

Todas las migraciones deberán ser aditivas.

Está prohibido:

* eliminar tablas existentes
* eliminar columnas
* borrar información existente
* reinicializar la base de datos
* utilizar `migrate:fresh`
* utilizar `db:wipe`
* utilizar `truncate`

Las modificaciones deberán realizarse mediante migraciones compatibles con una base de datos existente.

Nunca romper información de producción.

---

# Compatibilidad

Este módulo será desarrollado sobre un proyecto existente.

Antes de crear:

* Modelos.
* Componentes.
* Controllers.
* Services.
* Actions.
* Rutas.
* Migraciones.
* Notificaciones.
* Componentes de calendario.

deberá verificarse si ya existe una implementación reutilizable.

Siempre:

> **Reutilizar antes de crear.**

Nunca duplicar funcionalidades.

Nunca romper módulos existentes.

---

# Seguridad

Utilizar el sistema de autenticación y autorización existente.

No instalar paquetes adicionales de permisos.

No modificar la arquitectura actual de autenticación.

Cada usuario deberá poder acceder únicamente a sus propias tareas, salvo que posteriormente se implemente explícitamente colaboración o tareas compartidas.

---

# Experiencia de Usuario

La experiencia deberá priorizar:

* rapidez
* claridad
* seguimiento
* mínima cantidad de clics
* visibilidad de atrasos
* navegación rápida por fechas

Crear una tarea deberá ser sencillo.

Completar una tarea deberá requerir una sola acción.

Reprogramar una tarea atrasada deberá requerir el mínimo número de pasos posible.

---

# Atajos de Productividad

La arquitectura deberá permitir posteriormente incorporar acciones rápidas:

```text
+ Nueva tarea

Hoy
Mañana
Esta semana

Completar
Reprogramar
Cancelar
```

Esto deberá facilitar el trabajo diario.

---

# Responsive

El módulo deberá funcionar correctamente en:

* Desktop
* Laptop
* Tablet
* Mobile

En desktop deberá priorizarse la visualización del calendario.

En dispositivos pequeños deberá adaptarse a:

* agenda diaria
* lista de tareas
* calendario simplificado

sin perder información importante.

---

# Accesibilidad

La interfaz deberá considerar:

* navegación mediante teclado
* contraste adecuado
* etiquetas claras
* botones accesibles
* no depender únicamente del color
* indicadores textuales para estados importantes

Una tarea atrasada deberá poder identificarse incluso sin distinguir colores.

---

# Internacionalización

Idioma inicial:

**Español.**

Si el proyecto ya dispone de infraestructura de traducciones, utilizarla.

No colocar textos críticos directamente en Blade o componentes cuando exista un sistema de traducción.

Las fechas deberán mostrarse de acuerdo con la configuración regional existente.

---

# Pruebas

Las pruebas nunca deberán afectar información existente.

Está prohibido utilizar:

* `RefreshDatabase`
* `DatabaseMigrations`
* `DatabaseTruncation`
* `migrate:fresh`
* `db:wipe`
* `truncate`

Las pruebas deberán:

* crear únicamente información temporal
* eliminar únicamente la información creada por la propia prueba
* preservar completamente la información existente

---

# Pruebas Funcionales Mínimas

## Tareas

* [ ] Crear tarea.
* [ ] Editar tarea.
* [ ] Completar tarea.
* [ ] Reabrir tarea.
* [ ] Cancelar tarea.
* [ ] Eliminar tarea cuando corresponda.

## Fechas

* [ ] Crear tarea para hoy.
* [ ] Crear tarea futura.
* [ ] Crear tarea atrasada.
* [ ] Detectar automáticamente tareas atrasadas.
* [ ] Reprogramar tarea.
* [ ] Manejar tareas sin hora.
* [ ] Manejar tareas sin fecha si se implementan.

## Prioridad

* [ ] Crear tarea de prioridad baja.
* [ ] Crear tarea normal.
* [ ] Crear tarea alta.
* [ ] Crear tarea urgente.
* [ ] Filtrar por prioridad.

## Calendario

* [ ] Mostrar tareas en vista mensual.
* [ ] Mostrar tareas en vista semanal.
* [ ] Mostrar tareas en vista diaria.
* [ ] Seleccionar día.
* [ ] Navegar entre meses.
* [ ] Ir a hoy.
* [ ] Mostrar correctamente tareas con y sin horario.

## Notificaciones

* [ ] Crear recordatorio.
* [ ] Ejecutar recordatorio.
* [ ] Mostrar notificación.
* [ ] Evitar notificaciones duplicadas.
* [ ] Mostrar alerta de tarea atrasada cuando corresponda.

## Seguridad

* [ ] Un usuario no puede consultar tareas de otro usuario.
* [ ] Un usuario no puede modificar tareas de otro usuario.
* [ ] Un usuario no puede eliminar tareas de otro usuario.

---

# Criterios de Aceptación del MVP

El módulo será considerado funcional cuando:

* [ ] El calendario sea la visualización principal.
* [ ] Se pueda cambiar entre mes, semana y día.
* [ ] Se pueda seleccionar un día y ver su detalle.
* [ ] Se puedan crear tareas.
* [ ] Las tareas puedan tener fecha.
* [ ] Las tareas puedan tener hora opcional.
* [ ] Las tareas puedan tener fecha límite.
* [ ] Las tareas puedan tener prioridad.
* [ ] Las tareas puedan tener estado.
* [ ] Se puedan completar tareas.
* [ ] Se puedan reabrir tareas.
* [ ] El sistema detecte automáticamente tareas atrasadas.
* [ ] Las tareas atrasadas tengan una visualización destacada.
* [ ] Se muestre cuánto tiempo llevan atrasadas.
* [ ] Se puedan reprogramar rápidamente.
* [ ] Se puedan configurar recordatorios.
* [ ] Las notificaciones funcionen independientemente del módulo actual.
* [ ] Se puedan consultar tareas de hoy.
* [ ] Se puedan consultar tareas próximas.
* [ ] Se puedan consultar tareas atrasadas.
* [ ] Existan filtros.
* [ ] Exista búsqueda.
* [ ] La información sea independiente por usuario.
* [ ] El calendario sea usable en desktop y dispositivos móviles.
* [ ] Las pruebas no afecten información existente.
* [ ] La implementación respete `AGENTS.md`.
* [ ] No se rompan módulos existentes.

---

# Evolución Futura

La arquitectura deberá permitir incorporar posteriormente:

### Proyectos

```text
Proyecto
 └── Tareas
```

### Subtareas

```text
Tarea
 ├── Subtarea
 ├── Subtarea
 └── Subtarea
```

### Colaboración

Permitir asignar tareas a otros usuarios.

### Dependencias

```text
Tarea A
   ↓
Tarea B
   ↓
Tarea C
```

### Integración con Clientes

Relacionar una tarea con un cliente.

### Integración con CRM

Relacionar actividades con oportunidades, prospectos o clientes.

### Integración con calendarios externos

* Google Calendar.
* Microsoft Outlook.
* iCal.

### IA

La IA podrá posteriormente analizar:

* tareas atrasadas
* volumen de trabajo
* prioridades
* patrones de postergación
* cumplimiento
* carga diaria

y sugerir:

* qué hacer primero
* qué tareas reprogramar
* qué tareas agrupar
* qué tareas llevan demasiado tiempo pendientes
* cómo organizar el día

---

# IA para Productividad

Una futura integración de IA podrá responder preguntas como:

> ¿Qué debería hacer primero hoy?

> ¿Qué tareas tengo atrasadas?

> ¿Qué tareas llevan más tiempo pendientes?

> ¿Tengo demasiadas tareas programadas para mañana?

> Organiza mis tareas de hoy por prioridad.

La IA deberá actuar como asistente de productividad y no modificar tareas automáticamente sin autorización explícita del usuario.

---

# Objetivo Estratégico

El módulo deberá convertirse en el **centro de productividad personal del sistema**.

No deberá percibirse simplemente como una lista de tareas.

Su función principal será proporcionar una visión clara de:

**Hoy → Próximamente → Pendiente → Atrasado → Completado.**

La experiencia principal deberá estar basada en el calendario, mientras que el sistema deberá trabajar activamente para evitar que las tareas pendientes se pierdan.

El usuario deberá poder entrar al módulo y obtener inmediatamente una respuesta a:

> **¿Qué tengo que hacer hoy?**

> **¿Qué viene después?**

> **¿Qué tengo atrasado?**

> **¿Qué necesita mi atención?**

Por esta razón, todas las decisiones de arquitectura deberán priorizar:

**Calendario → Seguimiento → Alertas → Prioridad → Cumplimiento → Historial.**

---

# Principio Fundamental

> **Una tarea que no se sigue, se pierde.**

El sistema no debe limitarse a almacenar tareas.

Debe ayudar al usuario a **recordarlas, priorizarlas, ejecutarlas, completarlas o reprogramarlas**, manteniendo siempre visibles aquellas que requieren atención.

La experiencia ideal será:

```text
                AGENDA
                   │
        ┌──────────┼──────────┐
        ↓          ↓          ↓
       HOY      PRÓXIMAS   ATRASADAS
        │          │          │
        └──────────┼──────────┘
                   ↓
                TAREAS
                   ↓
             NOTIFICACIONES
                   ↓
               SEGUIMIENTO
                   ↓
               COMPLETADAS
```

El módulo deberá priorizar:

**Visibilidad → Seguimiento → Acción → Cumplimiento → Historial → Evolución.**
