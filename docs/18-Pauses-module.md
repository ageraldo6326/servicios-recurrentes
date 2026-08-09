# Módulo: Descansos y Ejercicios Aeróbicos

## Objetivo

El módulo de **Descansos y Ejercicios Aeróbicos** tiene como objetivo ayudar al usuario a mantener pausas activas durante su jornada de trabajo, mediante un ciclo configurable de trabajo y descanso.

El sistema deberá recordar al usuario cuándo corresponde realizar una pausa y sugerir ejercicios sencillos que puedan realizarse dentro de un ambiente de trabajo.

El módulo deberá funcionar de forma **transversal a toda la aplicación**.

El usuario no deberá permanecer dentro del módulo para recibir las alertas.

Por ejemplo, si el usuario se encuentra trabajando en:

- Dashboard
- Clientes
- Comercial
- Gastos
- Reportes
- Configuración
- Cualquier otro módulo

el sistema deberá continuar controlando el ciclo de trabajo y descanso.

La configuración predeterminada será:

**30 minutos de trabajo + 5 minutos de pausa.**

El ciclo deberá repetirse mientras el sistema de pausas esté habilitado.

Este módulo **NO pretende ser un sistema médico, fisioterapéutico, deportivo ni de diagnóstico**.

Los ejercicios serán sugerencias generales de movimiento y pausas activas para personas que trabajan frente a una computadora.

---

# Alcance del MVP

Esta primera versión implementará:

- Configuración de pausas.
- Configuración del tiempo de trabajo.
- Configuración de la duración de la pausa.
- Activación/desactivación del sistema.
- Ciclo automático de trabajo y descanso.
- Alerta visual al momento de iniciar una pausa.
- Alerta sonora al momento de iniciar una pausa.
- Confirmación para iniciar la pausa.
- Opción para cancelar/omitir la pausa.
- Temporizador de pausa.
- Sugerencia de ejercicios.
- Catálogo básico de ejercicios.
- Alerta visual al finalizar la pausa.
- Alerta sonora al finalizar la pausa.
- Confirmación para regresar al trabajo.
- Registro de pausas realizadas.
- Registro de pausas canceladas/omitidas.
- Funcionamiento global independientemente del módulo actual.

No se implementarán todavía:

- Planes personalizados de entrenamiento.
- Diagnóstico médico.
- Recomendaciones médicas.
- Seguimiento médico.
- Integración con dispositivos wearables.
- Conteo automático de repeticiones.
- Detección de movimientos mediante cámara.
- Reconocimiento de ejercicios mediante IA.
- Cálculo de calorías.
- Evaluación física del usuario.
- Integración con relojes inteligentes.
- Rutinas deportivas avanzadas.

La arquitectura deberá quedar preparada para incorporar estas funcionalidades posteriormente sin necesidad de rediseñar completamente el módulo.

---

# Concepto del Ciclo

El funcionamiento principal del módulo será un ciclo continuo:

```text
┌─────────────────────────────┐
│        TRABAJO              │
│        30 minutos           │
└──────────────┬──────────────┘
               │
               ▼
      🔔 ALARMA + ALERTA
               │
               ▼
┌─────────────────────────────┐
│      PAUSA PENDIENTE        │
│                             │
│  ¿Deseas tomar tu pausa?    │
│                             │
│ [Tomar pausa] [Cancelar]    │
└──────────────┬──────────────┘
               │
               ▼
┌─────────────────────────────┐
│        PAUSA                │
│        5 minutos             │
│                             │
│   Ejercicio sugerido        │
│   Temporizador              │
│                             │
│   [Finalizar pausa]         │
└──────────────┬──────────────┘
               │
               ▼
      🔔 ALARMA + ALERTA
               │
               ▼
┌─────────────────────────────┐
│     REGRESAR AL TRABAJO     │
│                             │
│ [Comenzar trabajo]          │
└──────────────┬──────────────┘
               │
               ▼
        NUEVO CICLO
               │
               ▼
        30 minutos
```

El sistema deberá mantener este ciclo mientras las pausas estén habilitadas.

---

# Arquitectura

Seguir todas las reglas establecidas en `AGENTS.md`.

Aplicar:

- SOLID
- Clean Architecture
- DRY
- KISS
- PSR-12

Toda la lógica de negocio deberá implementarse utilizando:

- Services
- Actions
- DTOs cuando aporten claridad
- Enums para estados cuando corresponda

Los componentes Livewire únicamente deberán administrar:

- presentación
- interacción
- eventos de interfaz

No colocar lógica de negocio en Blade.

No colocar lógica compleja en Controllers.

La lógica relacionada con:

- cálculo del próximo descanso
- duración del trabajo
- duración de la pausa
- estados del ciclo
- programación
- cancelación
- confirmación
- selección de ejercicios

deberá permanecer fuera de la capa de presentación.

---

# Servicio Global de Pausas

El módulo deberá implementar conceptualmente un servicio global encargado de controlar el ciclo de trabajo y descanso.

Este servicio deberá poder determinar:

- si las pausas están habilitadas
- cuándo comenzó el período de trabajo
- cuándo termina el período de trabajo
- cuándo corresponde una pausa
- si la pausa está pendiente de confirmación
- cuándo comenzó la pausa
- cuándo termina la pausa
- si el usuario canceló la pausa
- si el usuario confirmó el regreso al trabajo

El servicio deberá ser independiente de la pantalla que esté visualizando el usuario.

---

# Funcionamiento Global

El sistema deberá funcionar desde cualquier módulo de la aplicación.

No se deberá implementar el temporizador exclusivamente dentro de la página:

```text
/descansos
```

La aplicación deberá contar con un mecanismo global integrado en el layout principal o en la arquitectura transversal existente.

Ejemplo:

```text
Usuario en Dashboard
        ↓
Temporizador funcionando

Usuario entra en Clientes
        ↓
Temporizador continúa

Usuario entra en Comercial
        ↓
Temporizador continúa

Usuario entra en Reportes
        ↓
Temporizador continúa

Se cumplen 30 minutos
        ↓
🔔 Alerta global
```

El cambio de módulo no deberá reiniciar el ciclo.

---

# Configuración Predeterminada

El sistema deberá utilizar los siguientes valores iniciales:

| Configuración             |      Valor |
| ------------------------- | ---------: |
| Pausas activas            |         Sí |
| Tiempo de trabajo         | 30 minutos |
| Tiempo de pausa           |  5 minutos |
| Sonido al iniciar pausa   |         Sí |
| Sonido al finalizar pausa |         Sí |
| Alerta visual             |         Sí |

Estos valores deberán poder ser modificados posteriormente.

---

# Configuración de Usuario

Cada usuario deberá poder configurar su propio ciclo.

Ejemplo:

```text
Pausas activas
[ ✓ ]

Tiempo de trabajo
[ 30 ] minutos

Duración de pausa
[ 5 ] minutos

Sonido al iniciar pausa
[ ✓ ]

Sonido al finalizar pausa
[ ✓ ]
```

No asumir que todos los usuarios utilizan la misma configuración.

---

# Tiempo de Trabajo

El tiempo de trabajo representa el período durante el cual el usuario puede trabajar antes de recibir una alerta de pausa.

Valor predeterminado:

**30 minutos.**

Ejemplos de configuraciones posibles:

- 20 minutos
- 30 minutos
- 45 minutos
- 60 minutos
- 90 minutos

La arquitectura deberá permitir otros valores.

---

# Duración de la Pausa

El usuario podrá definir cuánto tiempo desea que dure su pausa.

Valor predeterminado:

**5 minutos.**

Ejemplos:

- 2 minutos
- 5 minutos
- 10 minutos
- 15 minutos

---

# Inicio del Período de Trabajo

Un período de trabajo deberá comenzar cuando el usuario confirme:

**Comenzar trabajo**

El sistema deberá registrar el timestamp correspondiente.

Ejemplo:

```text
10:00 AM
↓
Comienza trabajo

10:30 AM
↓
Termina período de trabajo
```

El contador deberá utilizar timestamps reales.

---

# Inicio de la Pausa

Cuando finalice el período de trabajo, el sistema deberá:

1. Reproducir una alarma sonora.
2. Mostrar una alerta visual.
3. Informar al usuario que corresponde una pausa.
4. Mostrar la duración configurada.
5. Mostrar un ejercicio sugerido.
6. Permitir confirmar la pausa.
7. Permitir cancelar/omitir la pausa.

Ejemplo:

```text
🔔 ¡Hora de hacer una pausa!

Has trabajado durante 30 minutos.

Es recomendable realizar una pausa activa
de 5 minutos.

Ejercicio sugerido:

🧘 Marcha suave en el lugar

[ Tomar pausa ]

[ Cancelar ]
```

---

# Confirmación de la Pausa

La pausa **no deberá considerarse iniciada** únicamente porque apareció la alerta.

El usuario deberá confirmar:

**Tomar pausa**

Solamente después de esta acción deberá comenzar el temporizador de la pausa.

Esto permitirá diferenciar entre:

- pausa notificada
- pausa aceptada
- pausa realizada

---

# Cancelar Pausa

El usuario deberá tener la opción:

**Cancelar**

o **Omitir**, según la terminología utilizada en la interfaz.

Si el usuario cancela:

- la pausa no deberá registrarse como completada
- deberá registrarse como cancelada/omitida
- el sistema deberá continuar con el ciclo de trabajo
- no deberá iniciar el temporizador de pausa

Ejemplo:

```text
30 minutos de trabajo
        ↓
Alerta
        ↓
Usuario selecciona Cancelar
        ↓
BREAK_CANCELLED
        ↓
Nuevo período de trabajo
```

No se deberá generar inmediatamente otra alerta por la misma pausa.

---

# Pausa Activa

Cuando el usuario confirme la pausa, se deberá iniciar oficialmente el período de descanso.

La interfaz deberá mostrar:

- ejercicio sugerido
- nombre
- instrucciones
- temporizador
- tiempo restante
- progreso
- opción para finalizar

Ejemplo:

```text
       🧘 PAUSA ACTIVA

       Marcha en el lugar

Camina suavemente sin desplazarte.
Mantén un ritmo cómodo.

           04:32

████████████░░░░░░░░

     [ Finalizar pausa ]
```

---

# Ejercicios

Los ejercicios deberán ser sencillos y apropiados para realizar en un ambiente laboral.

No deberán requerir equipamiento especial.

Ejemplos:

- Movilidad de cuello.
- Rotación de hombros.
- Estiramiento de brazos.
- Estiramiento de muñecas.
- Movilidad de espalda.
- Elevación de talones.
- Marcha en el lugar.
- Caminata corta.
- Sentarse y levantarse.
- Movilidad de piernas.
- Respiración y relajación.
- Movilidad general.

---

# Catálogo de Ejercicios

Cada ejercicio deberá poder contener:

- Nombre.
- Descripción.
- Instrucciones.
- Categoría.
- Duración recomendada.
- Nivel de dificultad.
- Estado activo/inactivo.

La arquitectura podrá soportar posteriormente:

- Imagen.
- Video.
- Animación.
- Repeticiones.
- Equipamiento.
- Advertencias.

---

# Categorías de Ejercicios

El catálogo deberá permitir clasificar los ejercicios.

Categorías iniciales:

- Cuello
- Hombros
- Brazos
- Muñecas
- Espalda
- Piernas
- Movilidad general
- Respiración
- Caminata
- Aeróbico ligero

---

# Selección de Ejercicios

Durante una pausa el sistema deberá seleccionar un ejercicio activo.

Para el MVP podrá utilizarse selección aleatoria.

La arquitectura deberá permitir posteriormente implementar:

- rotación
- rutinas
- categorías
- preferencias
- historial
- selección basada en comportamiento

El sistema deberá evitar que un ejercicio inactivo sea seleccionado.

---

# Temporizador de Pausa

El temporizador deberá comenzar únicamente después de que el usuario confirme:

**Tomar pausa.**

El temporizador deberá:

- mostrar tiempo restante
- actualizarse en tiempo real
- mostrar progreso
- detectar finalización
- sobrevivir a cambios de pestaña
- sobrevivir a navegación interna
- poder reconstruir el estado mediante timestamps

No utilizar únicamente un `setInterval()` como fuente de verdad.

El tiempo real de la pausa deberá determinarse mediante timestamps.

---

# Finalización de la Pausa

Cuando finalice el período de pausa:

1. Reproducir alarma sonora.
2. Mostrar alerta visual.
3. Informar que terminó la pausa.
4. Indicar que es momento de regresar al trabajo.
5. Solicitar confirmación del usuario.

Ejemplo:

```text
🔔 ¡Pausa finalizada!

Han terminado tus 5 minutos de descanso.

Es momento de continuar trabajando.

[ Comenzar trabajo ]
```

---

# Confirmación de Regreso al Trabajo

El nuevo período de trabajo deberá comenzar cuando el usuario confirme:

**Comenzar trabajo**

El sistema deberá registrar el timestamp de inicio del nuevo período.

No se deberá iniciar automáticamente el contador de trabajo solamente porque terminó el temporizador de pausa.

Flujo:

```text
Pausa finaliza
      ↓
Alarma
      ↓
Alerta visual
      ↓
Usuario confirma
      ↓
Comienza trabajo
      ↓
Nuevo ciclo de 30 minutos
```

---

# Alarmas Sonoras

El sistema deberá utilizar sonido en ambas transiciones.

## Alarma de inicio de pausa

Se reproducirá cuando termine el período de trabajo.

```text
TRABAJO
   ↓
30 minutos
   ↓
🔔 SONIDO
   ↓
ALERTA VISUAL
   ↓
PAUSA
```

## Alarma de regreso al trabajo

Se reproducirá cuando termine la pausa.

```text
PAUSA
   ↓
5 minutos
   ↓
🔔 SONIDO
   ↓
ALERTA VISUAL
   ↓
TRABAJO
```

El usuario deberá poder activar o desactivar los sonidos.

---

# Restricciones del Navegador

La implementación deberá considerar las políticas de los navegadores relacionadas con la reproducción automática de audio.

El sistema deberá solicitar la interacción inicial del usuario cuando sea necesario para habilitar la reproducción sonora.

Una vez autorizada la reproducción, las alertas deberán poder utilizar el mecanismo de sonido configurado.

La arquitectura deberá evitar depender de que el usuario tenga abierta exclusivamente la página del módulo de descansos.

---

# Alertas Visuales

Las alertas deberán ser visibles desde cualquier módulo.

La alerta deberá identificar claramente:

### Inicio de pausa

```text
🔔 HORA DE DESCANSAR
```

### Finalización de pausa

```text
🔔 HORA DE VOLVER AL TRABAJO
```

La interfaz deberá distinguir claramente ambos eventos.

---

# Estados del Ciclo

El ciclo deberá manejar estados explícitos.

Estados recomendados:

```text
WORKING
BREAK_PENDING
BREAK_CANCELLED
BREAK_ACTIVE
BREAK_COMPLETED
WORK_PENDING
```

Flujo normal:

```text
WORKING
   ↓
BREAK_PENDING
   ↓
BREAK_ACTIVE
   ↓
BREAK_COMPLETED
   ↓
WORK_PENDING
   ↓
WORKING
```

Cuando se cancela:

```text
BREAK_PENDING
   ↓
BREAK_CANCELLED
   ↓
WORK_PENDING
   ↓
WORKING
```

Los estados deberán implementarse mediante Enum o mecanismo equivalente.

No utilizar strings arbitrarios dispersos por el código.

---

# Persistencia

La arquitectura deberá permitir registrar la configuración y el historial.

Entidades potenciales:

```text
break_settings
breaks
exercises
exercise_categories
```

Los nombres definitivos deberán respetar las convenciones existentes del proyecto.

Antes de crear nuevas tablas deberá verificarse si existe una estructura reutilizable.

---

# Registro de Pausas

Cada ciclo deberá poder registrar:

- Usuario.
- Fecha.
- Hora programada.
- Hora de notificación.
- Hora de inicio.
- Hora de finalización.
- Duración configurada.
- Duración real.
- Estado.
- Ejercicio seleccionado.
- Fecha de creación.

Esto permitirá generar estadísticas posteriormente.

---

# Auditoría

Registrar:

- Usuario creador.
- Usuario modificador.
- Fecha de creación.
- Fecha de modificación.

Preparar la estructura para almacenar historial de cambios.

Las acciones del ciclo deberán permitir conocer:

- cuándo fue programada la pausa
- cuándo fue notificada
- cuándo fue aceptada
- cuándo fue cancelada
- cuándo comenzó
- cuándo terminó
- cuándo el usuario regresó al trabajo

---

# Migraciones

Todas las migraciones deberán ser aditivas.

Está prohibido:

- eliminar tablas
- eliminar columnas
- borrar información existente
- reinicializar la base de datos
- utilizar `migrate:fresh`
- utilizar `db:wipe`
- utilizar `truncate`

Cuando sea necesario modificar estructuras existentes deberá utilizarse:

```php
Schema::table()
```

Nunca se deberán realizar cambios destructivos sobre información existente.

---

# Arquitectura Transversal

El sistema de pausas deberá estar integrado en la arquitectura global de la aplicación.

No deberá depender de una ruta específica.

No deberá requerir que el usuario visite:

```text
/descansos
```

para funcionar.

Se deberá identificar el layout principal o mecanismo global utilizado actualmente por el proyecto.

La solución deberá procurar que exista:

**Un único controlador global del ciclo de pausas.**

No crear un temporizador independiente por cada módulo.

---

# Múltiples Pestañas

El sistema deberá evitar notificaciones duplicadas cuando el usuario tenga abiertas varias pestañas.

Ejemplo incorrecto:

```text
Pestaña 1 → 🔔
Pestaña 2 → 🔔
Pestaña 3 → 🔔
```

El comportamiento esperado es:

```text
Pestaña 1
Pestaña 2
Pestaña 3
      ↓
UNA SOLA ALERTA
      ↓
UNA SOLA ALARMA
```

La arquitectura podrá utilizar mecanismos del navegador como:

- BroadcastChannel
- eventos de `localStorage`
- identificadores de instancia
- mecanismos de sincronización disponibles

La implementación definitiva deberá adaptarse al stack existente.

---

# Sesión del Usuario

El sistema deberá contemplar:

- inicio de sesión
- cierre de sesión
- expiración de sesión
- reapertura de sesión
- múltiples pestañas
- múltiples ventanas

El estado deberá asociarse correctamente al usuario autenticado.

No deberá generarse una pausa para un usuario diferente.

---

# Ausencia del Usuario

El MVP podrá utilizar un ciclo simple basado en timestamps.

Sin embargo, la arquitectura deberá permitir posteriormente incorporar:

- detección de inactividad
- actividad del teclado
- actividad del mouse
- visibilidad de la ventana
- horario laboral
- períodos fuera de jornada

No es necesario implementar estas funcionalidades en el MVP.

---

# Horario Laboral

No se implementará un calendario laboral completo en esta versión.

La arquitectura deberá quedar preparada para soportar posteriormente:

- hora de inicio
- hora de finalización
- días laborales
- períodos excluidos
- días feriados

Esto permitirá evitar alertas fuera del horario de trabajo.

---

# Seguridad

Utilizar el sistema de autenticación y autorización existente.

No instalar paquetes adicionales de permisos.

No modificar la arquitectura actual de autenticación.

Las configuraciones y registros deberán estar asociados al usuario autenticado.

Un usuario no deberá poder modificar o consultar la configuración o historial de otro usuario.

---

# Experiencia de Usuario

La experiencia deberá ser sencilla y de baja fricción.

El usuario deberá poder trabajar normalmente sin visitar el módulo.

La experiencia esperada es:

```text
Trabajar
   ↓
🔔 Alerta
   ↓
Tomar pausa
   ↓
Ejercicio
   ↓
5 minutos
   ↓
🔔 Alerta
   ↓
Comenzar trabajo
   ↓
Trabajar
   ↓
...
```

El usuario deberá tener control explícito sobre ambas transiciones.

---

# Diseño de la Alerta de Pausa

La alerta deberá contener como mínimo:

```text
🔔 HORA DE HACER UNA PAUSA

Has trabajado durante 30 minutos.

Es recomendable tomar una pausa activa.

Ejercicio sugerido:
Marcha suave en el lugar

Duración:
5 minutos

[ TOMAR PAUSA ]

[ CANCELAR ]
```

---

# Diseño de la Alerta de Regreso

La alerta deberá contener:

```text
🔔 HORA DE VOLVER AL TRABAJO

Tu pausa de 5 minutos ha terminado.

Cuando estés listo, comienza un nuevo
período de trabajo.

[ COMENZAR TRABAJO ]
```

---

# Accesibilidad

La interfaz deberá considerar:

- contraste adecuado
- textos legibles
- botones grandes y claros
- navegación mediante teclado
- indicadores visuales
- mensajes comprensibles
- no depender únicamente del color

Las alarmas sonoras no deberán ser el único mecanismo de notificación.

Las alertas visuales deberán funcionar aunque el sonido esté desactivado.

---

# Responsive

El módulo deberá funcionar correctamente en:

- Desktop
- Laptop
- Tablet
- Mobile

La experiencia principal estará orientada a usuarios que trabajan desde computadoras.

---

# Internacionalización

El idioma inicial será:

**Español.**

Si el proyecto ya dispone de infraestructura de traducciones, todos los textos deberán integrarse utilizando dicho mecanismo.

No colocar textos críticos directamente en Blade o componentes si existe un sistema de traducción establecido.

---

# Dashboard

El dashboard podrá mostrar:

- Estado actual.
- Tiempo restante de trabajo.
- Próxima pausa.
- Tiempo restante de pausa.
- Pausas realizadas hoy.
- Pausas canceladas.
- Tiempo total de descanso.
- Ejercicios realizados.

Ejemplo:

```text
ESTADO ACTUAL

🟢 Trabajando

Próxima pausa:
18:30

Tiempo restante:
12:34

Pausas realizadas:
4

Pausas canceladas:
1
```

La arquitectura deberá permitir agregar nuevos widgets posteriormente.

---

# Estadísticas Futuras

La arquitectura deberá permitir calcular posteriormente:

- Pausas realizadas por día.
- Pausas realizadas por semana.
- Porcentaje de cumplimiento.
- Pausas canceladas.
- Tiempo total de descanso.
- Tiempo promedio de pausa.
- Ejercicios más utilizados.
- Horarios con mayor cumplimiento.
- Tendencia de cumplimiento.

Estas métricas no son obligatorias para el MVP.

---

# IA

La arquitectura deberá quedar preparada para una futura integración con IA.

La IA podrá analizar posteriormente:

- frecuencia de pausas
- pausas canceladas
- ejercicios realizados
- duración de las sesiones
- horarios
- comportamiento histórico

y proporcionar sugerencias generales.

Ejemplo:

> "Has trabajado durante períodos prolongados esta semana. Podrías considerar mantener pausas de 5 minutos cada 30 minutos."

La IA no deberá realizar diagnósticos médicos ni recomendaciones clínicas.

---

# Compatibilidad

Este módulo será desarrollado sobre un proyecto existente.

Antes de crear:

- Modelos
- Componentes
- Controllers
- Services
- Actions
- Rutas
- Migraciones
- Layouts
- Notificaciones

deberá verificarse si ya existe una implementación reutilizable.

Siempre:

**Reutilizar antes de crear.**

Nunca duplicar funcionalidades.

Nunca modificar innecesariamente componentes globales existentes.

Nunca romper módulos existentes.

---

# Pruebas

Las pruebas nunca deberán afectar información existente.

Está prohibido utilizar:

- `RefreshDatabase`
- `DatabaseMigrations`
- `DatabaseTruncation`
- `migrate:fresh`
- `db:wipe`
- `truncate`

Las pruebas deberán:

- crear únicamente información temporal
- eliminar únicamente información creada por la propia prueba
- preservar completamente la información existente

---

# Pruebas Funcionales Mínimas

## Configuración

- [ ] Crear configuración.
- [ ] Modificar tiempo de trabajo.
- [ ] Modificar duración de pausa.
- [ ] Activar pausas.
- [ ] Desactivar pausas.
- [ ] Activar/desactivar sonido.

## Ciclo

- [ ] Iniciar período de trabajo.
- [ ] Calcular finalización del trabajo.
- [ ] Generar pausa pendiente.
- [ ] Confirmar pausa.
- [ ] Cancelar pausa.
- [ ] Iniciar temporizador.
- [ ] Finalizar pausa.
- [ ] Confirmar regreso al trabajo.
- [ ] Iniciar nuevo ciclo.

## Alarmas

- [ ] Generar alarma al comenzar pausa.
- [ ] Mostrar alerta visual al comenzar pausa.
- [ ] Generar alarma al finalizar pausa.
- [ ] Mostrar alerta visual al finalizar pausa.
- [ ] Respetar configuración de sonido.

## Ejercicios

- [ ] Obtener ejercicios activos.
- [ ] Seleccionar ejercicio.
- [ ] Mostrar instrucciones.
- [ ] Evitar ejercicios inactivos.

## Seguridad

- [ ] Un usuario no puede consultar configuración de otro usuario.
- [ ] Un usuario no puede modificar configuración de otro usuario.
- [ ] Un usuario no puede modificar pausas de otro usuario.

## Persistencia

- [ ] Registrar pausa aceptada.
- [ ] Registrar pausa cancelada.
- [ ] Registrar pausa completada.
- [ ] Registrar timestamps correctamente.

## Integración global

- [ ] El ciclo continúa al cambiar de módulo.
- [ ] El ciclo no se reinicia al navegar.
- [ ] La alerta aparece desde cualquier módulo.
- [ ] No se generan alertas duplicadas en múltiples pestañas.

---

# Consideraciones de Tiempo

El tiempo deberá manejarse mediante timestamps reales.

No utilizar exclusivamente contadores JavaScript como fuente de verdad.

Por ejemplo:

```text
work_started_at
work_ends_at

break_started_at
break_ends_at
```

El frontend podrá utilizar JavaScript para mostrar el contador visual, pero el estado real deberá poder reconstruirse a partir de timestamps.

Esto permitirá manejar:

- cambio de pestaña
- suspensión del navegador
- pérdida temporal de conexión
- navegación
- actualización de Livewire
- recarga de página
- múltiples pestañas

---

# Persistencia del Estado

El estado del ciclo no deberá depender únicamente del estado de un componente Livewire.

Si el usuario navega:

```text
Dashboard
    ↓
Clientes
    ↓
Reportes
    ↓
Comercial
```

el sistema deberá conservar el estado.

Ejemplo:

```text
10:00
Inicio de trabajo

10:15
Usuario cambia de módulo

10:29
Usuario entra a Reportes

10:30
Alerta de pausa
```

La navegación no deberá reiniciar el contador.

---

# Principio Fundamental

> **El usuario no debe tener que recordar cuándo debe descansar ni cuándo debe volver a trabajar. El sistema debe avisarle de ambas cosas.**

El módulo debe comportarse como un asistente de pausas activas integrado en toda la aplicación.

El ciclo predeterminado será:

**30 minutos de trabajo → 5 minutos de pausa → 30 minutos de trabajo → 5 minutos de pausa → ...**

Cada transición deberá estar acompañada por:

**🔔 sonido + 👁️ alerta visual + acción explícita del usuario.**

El sistema deberá priorizar:

**Simplicidad → Confiabilidad → Baja intrusión → Control del usuario → Escalabilidad.**
