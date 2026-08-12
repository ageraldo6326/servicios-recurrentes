# SDD — Estados de Compromisos Recurrentes

## 1. Objetivo

Modificar el módulo de **Compromisos Recurrentes / Agenda Financiera** para que los estados representen correctamente la situación real de cada obligación.

Actualmente, el sistema puede mostrar como `pending` compromisos cuyo período futuro ya fue generado, aunque todavía no haya ocurrido el evento que realmente crea la obligación.

Ejemplo:

Una tarjeta con:

- Fecha de corte: 15/08/2026
- Fecha límite: 10/09/2026

No debe aparecer como `pending` el 11/08/2026.

Debe aparecer como:

`projected`

La obligación pasa a `pending` únicamente cuando llega su fecha de corte.

---

# 2. Principio general

Debe separarse conceptualmente:

- Existencia del período.
- Generación de la obligación.
- Estado financiero de la obligación.

El hecho de que exista un período futuro, por ejemplo:

`09/2026`

no significa automáticamente que exista una obligación pendiente de pago.

Un período puede existir para efectos de calendario y proyección financiera sin que todavía exista una deuda exigible.

---

# 3. Estados funcionales

El módulo manejará los siguientes estados funcionales:

| Estado      | Nombre visual | Significado                                                                          |
| ----------- | ------------- | ------------------------------------------------------------------------------------ |
| `projected` | Proyectado    | El compromiso existe en el calendario, pero todavía no se ha generado la obligación. |
| `pending`   | Pendiente     | La obligación ya fue generada y está pendiente de pago.                              |
| `paid`      | Pagado        | La obligación fue pagada completamente.                                              |
| `overdue`   | Vencido       | La fecha límite pasó y todavía existe saldo pendiente.                               |
| `cancelled` | Cancelado     | El compromiso o período fue cancelado y ya no debe ser exigible.                     |

---

# 4. Flujo principal

El flujo normal será:

```text
PROYECTADO
     ↓
PENDIENTE
     ↓
PAGADO
```

Cuando no se realiza el pago:

```text
PROYECTADO
     ↓
PENDIENTE
     ↓
VENCIDO
     ↓
PAGADO
```

También podrá existir:

```text
PROYECTADO
     ↓
CANCELADO
```

o:

```text
PENDIENTE
     ↓
CANCELADO
```

si la lógica actual del módulo permite cancelar períodos u obligaciones.

---

# 5. Concepto de disparador

Cada compromiso debe tener un evento que determine cuándo deja de ser una proyección y se convierte en una obligación real.

Este evento será denominado:

`trigger`

o conceptualmente:

**Disparador de obligación**

El disparador no debe confundirse con la fecha límite de pago.

---

# 6. Compromisos con fecha de corte

Para compromisos que poseen:

- Fecha de corte.
- Fecha límite de pago.

La fecha de corte será el disparador.

El caso principal son las tarjetas de crédito.

## Regla

Si:

```text
fecha_actual < fecha_corte
```

estado:

```text
projected
```

Si:

```text
fecha_actual >= fecha_corte
AND
fecha_actual <= fecha_limite
AND
saldo > 0
```

estado:

```text
pending
```

Si:

```text
fecha_actual > fecha_limite
AND
saldo > 0
```

estado:

```text
overdue
```

Si:

```text
saldo <= 0
```

estado:

```text
paid
```

---

# 7. Ejemplo: Tarjeta APAP

Datos:

```text
Período: 09/2026
Corte: 13/08/2026
Fecha límite: 04/09/2026
Monto: RD$7,000
```

## 11/08/2026

Todavía no ocurrió el corte.

Resultado:

```text
Proyectado
```

## 13/08/2026

Ocurre el corte.

Resultado:

```text
Pendiente
```

## 04/09/2026

Si todavía existe saldo:

```text
Pendiente
```

## 05/09/2026

Si continúa existiendo saldo:

```text
Vencido
```

## Si se registra el pago completo

En cualquier momento:

```text
Pagado
```

---

# 8. Compromisos sin fecha de corte

Existen compromisos que solamente tienen una fecha límite.

Ejemplos:

- Préstamos.
- Servicios.
- Suscripciones.
- Electrodomésticos.
- Otros pagos recurrentes.

En estos casos no existe una fecha de corte que pueda funcionar como disparador.

Debe introducirse el concepto:

`activation_days_before_due`

Nombre visual recomendado:

**Días de anticipación**

Este campo indica cuántos días antes de la fecha límite debe considerarse generada la obligación.

---

# 9. Valor predeterminado

Para compromisos sin fecha de corte se utilizará inicialmente:

```text
15 días
```

como valor predeterminado.

Este valor debe ser configurable por compromiso.

Ejemplo:

```text
Fecha límite:
30/08/2026

Días de anticipación:
15
```

Fecha de activación:

```text
15/08/2026
```

---

# 10. Cálculo del disparador sin fecha de corte

La fecha de activación será:

```text
trigger_date =
due_date - activation_days_before_due
```

Entonces:

Si:

```text
fecha_actual < trigger_date
```

estado:

```text
projected
```

Si:

```text
fecha_actual >= trigger_date
AND
fecha_actual <= due_date
AND
saldo > 0
```

estado:

```text
pending
```

Si:

```text
fecha_actual > due_date
AND
saldo > 0
```

estado:

```text
overdue
```

Si:

```text
saldo <= 0
```

estado:

```text
paid
```

---

# 11. Ejemplo: Préstamo Guagua

Datos:

```text
Fecha límite:
30/08/2026

Monto:
RD$23,000

Días de anticipación:
15
```

El disparador será:

```text
15/08/2026
```

## 11/08/2026

Estado:

```text
Proyectado
```

## 15/08/2026

Estado:

```text
Pendiente
```

## 30/08/2026

Si no ha sido pagado:

```text
Pendiente
```

## 31/08/2026

Si mantiene saldo:

```text
Vencido
```

---

# 12. Prioridad de evaluación del estado

El sistema debe evaluar los estados en un orden definido.

La prioridad será:

```text
1. cancelled
2. paid
3. overdue
4. pending
5. projected
```

Esto evita inconsistencias.

Por ejemplo, un compromiso pagado después de su fecha límite no debe continuar mostrándose como:

`overdue`

Debe aparecer:

`paid`

y adicionalmente puede mostrar:

`Pagado 2 días después`

---

# 13. Estado pagado

Un compromiso se considera pagado cuando:

```text
saldo <= 0
```

o cuando la suma de pagos registrados alcance o supere el monto exigible.

La fecha no debe alterar este estado.

Ejemplo:

```text
Fecha límite:
02/08/2026

Fecha de pago:
03/08/2026
```

Estado:

```text
paid
```

Descripción complementaria:

```text
Pagado 1 día después
```

No debe aparecer como vencido después de haberse registrado el pago completo.

---

# 14. Estado vencido

`overdue` debe ser principalmente un estado calculado.

Debe cumplirse:

```text
fecha_actual > fecha_limite
AND
saldo > 0
```

No debería ser necesario que un usuario cambie manualmente un compromiso a vencido.

El sistema puede determinarlo automáticamente.

---

# 15. Diferencia entre estado persistente y estado calculado

Se recomienda revisar la arquitectura actual antes de agregar nuevos valores directamente a la base de datos.

Debe evaluarse separar:

## Datos persistentes

Datos que representan acciones reales realizadas por el usuario:

```text
paid
cancelled
```

## Datos calculados

Estados derivados de fechas y saldo:

```text
projected
pending
overdue
```

Ejemplo:

Un registro puede mantener internamente información como:

```text
payment_status = unpaid
```

y el sistema calcular:

```text
projected
pending
overdue
```

según las fechas.

La implementación final debe ajustarse a la arquitectura actual y evitar migraciones innecesarias si el estado puede obtenerse de forma confiable mediante cálculo.

---

# 16. Servicio centralizado de estados

La lógica no debe estar duplicada en:

- Controladores.
- Blade.
- Livewire.
- Consultas.
- Dashboard.
- Calendario.

Debe existir una única fuente de verdad.

Ejemplo conceptual:

```text
CommitmentStatusService
```

o:

```text
CommitmentStatusResolver
```

Responsabilidad:

```text
resolve(commitment, period, payments, currentDate)
```

Resultado:

```text
projected
pending
paid
overdue
cancelled
```

Todas las vistas deben consumir esta misma lógica.

---

# 17. Fecha de referencia

No utilizar directamente `now()` disperso por toda la aplicación.

Centralizar la fecha de evaluación para facilitar:

- Pruebas.
- Simulación de períodos.
- Corrección de errores.
- Tests automatizados.

Ejemplo conceptual:

```php
$currentDate = today();
```

El resolver recibirá esta fecha como dependencia o parámetro.

---

# 18. Información complementaria del estado

Además del estado principal, el resolver podrá devolver información útil.

Ejemplo conceptual:

```text
status: pending

days_until_due: 9

days_overdue: 0

days_before_payment: null

trigger_date: 10/08/2026

due_date: 20/08/2026
```

Para pagados:

```text
status: paid

days_before_payment: 5
```

o:

```text
days_after_payment: 1
```

La vista podrá utilizar esos valores sin volver a implementar cálculos.

---

# 19. Visualización

Los nombres mostrados al usuario serán siempre en español.

## Proyectado

```text
Proyectado
```

Representa una obligación futura.

## Pendiente

```text
Pendiente
```

Representa una obligación ya generada.

## Vencido

```text
Vencido
```

Representa una obligación cuya fecha límite pasó.

## Pagado

```text
Pagado
```

Representa una obligación satisfecha completamente.

## Cancelado

```text
Cancelado
```

---

# 20. Colores sugeridos

Mantener consistencia en toda la aplicación.

```text
Proyectado → azul / gris azulado

Pendiente → amarillo / ámbar

Vencido → rojo

Pagado → verde

Cancelado → gris
```

Los colores deben utilizar las clases y componentes visuales existentes del proyecto.

No introducir un sistema visual paralelo.

---

# 21. Tratamiento de los períodos futuros

El sistema puede continuar generando períodos futuros.

Ejemplo:

```text
Prestamo Guagua

Período 09/2026

Fecha límite:
30/09/2026

Monto:
RD$23,000
```

Pero si todavía no se ha alcanzado su disparador:

```text
Estado:
Proyectado
```

Esto permite conservar la capacidad de visualizar compromisos futuros sin tratarlos erróneamente como deuda pendiente.

---

# 22. Tarjetas de crédito

Para compromisos categoría:

```text
Tarjeta
```

o aquellos configurados explícitamente con fecha de corte:

```text
trigger_date = fecha_corte
```

No utilizar:

```text
fecha_limite
```

como disparador.

Ejemplo:

```text
BHD Platinum

Corte:
15/08/2026

Pago:
10/09/2026
```

Antes del 15/08:

```text
Proyectado
```

Desde el 15/08:

```text
Pendiente
```

Después del 10/09, si no se paga:

```text
Vencido
```

---

# 23. No inferir únicamente por categoría

Aunque inicialmente las tarjetas son el principal caso con corte, la lógica no debe depender exclusivamente de:

```text
category == Tarjeta
```

Debe utilizarse preferentemente:

```text
si tiene fecha de corte
    usar fecha de corte como disparador
```

Esto permitirá que en el futuro otro tipo de compromiso pueda tener:

- Fecha de corte.
- Fecha límite.

sin modificar la arquitectura.

---

# 24. Pagos parciales

Si actualmente el módulo permite pagos parciales, la lógica debe respetarlos.

Ejemplo:

```text
Monto:
RD$23,000

Pagado:
RD$10,000

Saldo:
RD$13,000
```

Si todavía no venció:

```text
Pendiente
```

Si venció:

```text
Vencido
```

Solamente será:

```text
Pagado
```

cuando:

```text
saldo <= 0
```

---

# 25. Corrección del saldo visual de compromisos pagados

Actualmente existen casos donde puede mostrarse:

```text
Estado:
Pagado

Monto:
RD$23,000

Saldo:
RD$23,000
```

Esto debe corregirse.

Si el compromiso fue completamente pagado:

```text
Saldo:
RD$0
```

o el saldo puede omitirse de la vista.

Nunca debe mostrarse el monto original como saldo pendiente de una obligación pagada.

---

# 26. Migraciones

Cualquier migración requerida deberá:

- Ser aditiva.
- No eliminar datos existentes.
- No recrear tablas existentes.
- No ejecutar `drop`.
- No utilizar `migrate:fresh`.
- No utilizar `RefreshDatabase` sobre la base de datos existente.
- Mantener compatibilidad con los registros actuales.

Si se agrega:

```text
activation_days_before_due
```

deberá ser nullable o tener un valor predeterminado seguro.

Ejemplo recomendado:

```text
15
```

La implementación deberá revisar primero la estructura existente antes de decidir el tipo exacto de migración.

---

# 27. Compatibilidad con compromisos existentes

Los compromisos actuales no deben perder:

- Pagos.
- Historial.
- Períodos.
- Beneficiarios.
- Categorías.
- Montos.
- Fechas.
- Evidencias.
- Observaciones.

La nueva lógica debe reinterpretar correctamente su estado.

No deben recrearse registros históricos simplemente para adoptar los nuevos estados.

---

# 28. Pruebas obligatorias

Crear pruebas para al menos los siguientes escenarios.

## Tarjeta antes del corte

```text
Hoy:
11/08/2026

Corte:
15/08/2026

Límite:
10/09/2026
```

Resultado:

```text
projected
```

---

## Tarjeta el día del corte

Resultado:

```text
pending
```

---

## Tarjeta después del corte y antes del vencimiento

Resultado:

```text
pending
```

---

## Tarjeta el día límite

Resultado:

```text
pending
```

---

## Tarjeta un día después del límite

Con saldo:

```text
overdue
```

---

## Tarjeta pagada antes del límite

Resultado:

```text
paid
```

Información:

```text
Pagado N días antes
```

---

## Tarjeta pagada después del límite

Resultado:

```text
paid
```

Información:

```text
Pagado N días después
```

---

## Préstamo antes del disparador

Resultado:

```text
projected
```

---

## Préstamo en fecha de activación

Resultado:

```text
pending
```

---

## Préstamo vencido

Resultado:

```text
overdue
```

---

## Pago parcial antes del vencimiento

Resultado:

```text
pending
```

---

## Pago parcial después del vencimiento

Resultado:

```text
overdue
```

---

## Pago completo

Resultado:

```text
paid
```

Saldo:

```text
0
```

---

# 29. Pruebas y datos de producción

Las pruebas no deben borrar ni modificar permanentemente los datos existentes.

Debe evitarse cualquier estrategia que ejecute:

```text
migrate:fresh
```

```text
db:wipe
```

```text
RefreshDatabase
```

sobre la base de datos de trabajo o producción.

Las pruebas deberán utilizar:

- Base de datos independiente de testing.
- Transacciones cuando corresponda.
- Factories.
- Datos temporales controlados.

---

# 30. Criterios de aceptación

La implementación se considerará terminada cuando:

1. Los períodos futuros puedan mostrarse sin aparecer necesariamente como pendientes.

2. Los compromisos con fecha de corte permanezcan `projected` antes del corte.

3. Al llegar la fecha de corte pasen automáticamente a `pending`.

4. Los compromisos sin fecha de corte utilicen un disparador configurable por días de anticipación.

5. Los compromisos vencidos sean identificados automáticamente.

6. Un compromiso completamente pagado nunca aparezca como vencido.

7. Los pagos parciales mantengan correctamente el saldo.

8. Los compromisos pagados muestren saldo cero.

9. El cálculo de estado esté centralizado.

10. Las vistas no contengan reglas independientes para determinar estados.

11. La información histórica actual se conserve.

12. Las pruebas automatizadas cubran todos los escenarios críticos.

---

# 31. Fuera de alcance

Esta modificación no incluirá todavía:

- Recordatorios.
- Notificaciones.
- Alarmas.
- WhatsApp.
- Correo electrónico.
- Análisis financiero con IA.
- Forecast avanzado.
- Indicadores de flujo de efectivo.
- Priorización automática.
- Dashboard financiero nuevo.

Estos elementos deberán tratarse en especificaciones independientes.

El objetivo de esta modificación es exclusivamente establecer una base confiable para:

**Período + Disparador + Estado + Saldo**

antes de agregar nuevas funcionalidades al módulo.
