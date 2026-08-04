# 13. Agenda Financiera

## Objetivo

La Agenda Financiera es un módulo independiente del sistema.

Su objetivo es ayudar al usuario a cumplir todos sus compromisos financieros antes de su vencimiento.

No es un módulo contable.

No es un módulo de tesorería.

No calcula estados financieros.

Su única responsabilidad es organizar, priorizar y dar seguimiento a los compromisos financieros recurrentes.

---

# Filosofía

Este módulo debe responder una única pregunta.

> ¿Qué debo pagar y cuándo debo hacerlo?

El sistema debe ayudar al usuario a evitar incumplimientos mediante una agenda ordenada y un sistema de recordatorios.

La prioridad es evitar olvidos.

No mostrar información histórica.

No mostrar gráficos innecesarios.

Debe ser una herramienta de trabajo diario.

---

# Compatibilidad con el sistema existente

## Regla obligatoria

Este módulo es completamente nuevo.

Su implementación NO debe afectar ningún módulo existente.

Está estrictamente prohibido:

- modificar reglas existentes;
- eliminar funcionalidades;
- cambiar comportamiento del sistema actual;
- eliminar columnas;
- modificar datos existentes;
- borrar registros;
- alterar migraciones anteriores.

Toda modificación deberá realizarse mediante nuevas migraciones.

Nunca modificando migraciones ya ejecutadas.

Toda nueva funcionalidad debe ser compatible con la información existente.

---

# Entidades

## Beneficiario

Representa la entidad a la cual se realiza el pago.

Ejemplos

- Banco Popular
- Banreservas
- Claro
- Altice
- Edesur
- Vultr
- AWS
- Netflix
- Colegio ABC

Campos mínimos

Nombre

Tipo

Activo

Observaciones

---

## Compromiso Financiero

Representa un compromiso permanente.

Ejemplos

Tarjeta Visa

Préstamo vehículo

Internet oficina

Colegio

Hosting

Servidor dedicado

Campos mínimos

Nombre

Beneficiario

Categoría

Frecuencia

Monto sugerido (opcional)

Tiene fecha de corte

Día de corte (opcional)

Día límite de pago

Activo

Observaciones

---

# Frecuencia

Inicialmente únicamente:

Mensual

En futuras versiones podrán existir:

Semanal

Quincenal

Trimestral

Semestral

Anual

---

# Fechas

Dependiendo del compromiso pueden existir dos eventos importantes.

## Fecha de corte

Aplica principalmente para:

Tarjetas de crédito

Líneas de crédito

Otros compromisos similares

La fecha de corte indica cuándo se genera el estado de cuenta.

No representa una obligación de pago.

Representa un evento informativo.

---

## Fecha límite de pago

Representa el último día para cumplir con el compromiso.

Es el evento más importante.

Debe generar recordatorios.

---

# Casos de uso

## Caso 1

Internet

No tiene fecha de corte.

Tiene únicamente fecha límite de pago.

---

## Caso 2

Tarjeta de crédito

Tiene:

Fecha de corte

Fecha límite

Ambas deben mostrarse.

---

## Caso 3

Préstamo

Generalmente únicamente posee fecha de pago.

---

# Dashboard

La pantalla principal NO será un calendario.

La pantalla principal será una tabla ordenada automáticamente.

---

# Orden

La tabla siempre deberá mostrarse por prioridad.

Primero:

Compromisos vencidos.

Luego:

Compromisos que vencen hoy.

Luego:

Compromisos próximos.

Después:

Eventos de corte.

Finalmente:

Compromisos futuros.

---

# Filtros

Periodo

Hoy

Esta semana

Este mes

Rango personalizado

Estado

Todos

Pendientes

Pagados

Vencidos

Beneficiario

Categoría

---

# Tabla principal

Cada fila representa un compromiso.

Debe mostrar como mínimo.

Prioridad

Compromiso

Beneficiario

Categoría

Fecha de corte

Fecha límite

Días para corte

Días para pago

Monto sugerido

Estado

Acción

---

# Días restantes

Este cálculo es obligatorio.

No mostrar únicamente la fecha.

Mostrar:

Días restantes para corte.

Días restantes para pago.

Ejemplo

5 días

2 días

Hoy

Vencido hace 3 días

Esto permite interpretar la prioridad mucho más rápido.

---

# Colores

Rojo

Pago vencido

Naranja

Pago hoy

Amarillo

Pago en menos de 3 días

Azul

Evento de corte

Verde

Pagado

Gris

Eventos futuros

---

# Acción principal

Cada fila tendrá un botón.

Registrar Pago

Al registrar el pago el sistema deberá:

Marcar el compromiso como pagado.

Guardar:

Fecha

Monto pagado (opcional)

Observaciones

Comprobante (opcional)

Después deberá calcular automáticamente el siguiente período.

Nunca deberá crear registros manuales para el siguiente mes.

El sistema debe hacerlo automáticamente.

---

# Notificaciones

Las notificaciones son una parte fundamental del módulo.

El sistema deberá generar recordatorios automáticos.

Como mínimo:

7 días antes.

3 días antes.

1 día antes.

El mismo día.

Mientras permanezca vencido.

Las cantidades deberán ser configurables en el futuro.

---

# Prioridad automática

El sistema deberá calcular una prioridad automáticamente.

Ejemplo

Pago vencido

Prioridad crítica

Pago hoy

Alta

Pago en 2 días

Media

Pago en 7 días

Baja

Evento de corte

Informativa

La prioridad determinará el orden de la tabla.

---

# Objetivo UX

El usuario debe abrir el módulo y saber inmediatamente:

Qué debe pagar.

Qué vence hoy.

Qué vencerá pronto.

Qué ya incumplió.

No debe interpretar un calendario.

Debe recibir una lista de trabajo ordenada por prioridad.

---

# Definición de terminado

Este módulo estará terminado cuando el usuario pueda:

Registrar beneficiarios.

Registrar compromisos.

Visualizar automáticamente los compromisos ordenados por prioridad.

Conocer cuántos días faltan para el corte y para el pago.

Registrar el pago desde la misma tabla.

Recibir recordatorios antes del vencimiento.

Todo ello sin afectar el funcionamiento de los módulos existentes y sin modificar la estructura o los datos ya implementados.
