# SDD --- Evolución del Módulo de Compromisos Recurrentes

## 1. Objetivo

Evolucionar el módulo de **Compromisos Recurrentes / Agenda Financiera**
para que represente correctamente las obligaciones recurrentes
independientemente de si las obligaciones anteriores fueron pagadas.

El sistema debe responder principalmente:

-   ¿Qué obligaciones tengo?
-   ¿Cuándo nace cada nueva obligación?
-   ¿Cuándo debo pagarla?
-   ¿Cuánto tiempo falta?
-   ¿Cuánto tiempo lleva vencida si no la he pagado?
-   ¿Qué obligaciones ya pagué?
-   ¿Con cuánta anticipación pagué una obligación?

La lógica de recurrencia y la lógica de pago deben ser
**independientes**.

> **Regla fundamental:** La recurrencia determina cuándo nace una
> obligación. El pago determina el estado de esa obligación. El pago de
> una obligación nunca debe impedir la generación de obligaciones
> posteriores.

------------------------------------------------------------------------

# 2. Estado actual conocido

La implementación actual utiliza:

-   `financial_commitments` como plantilla permanente del compromiso.
-   `commitment_payments` como registro mensual de la obligación/pago.
-   `period_start` para identificar el período.
-   `has_cutoff`, `cutoff_day` y `due_day` para calcular fechas.
-   `RegisterCommitmentPayment` para registrar pagos y actualmente
    generar el siguiente período.
-   `FinancialCommitmentAgendaService` para calcular la agenda.

La implementación actual tiene una limitación importante:

> El período se determina por el mes actual y la generación persistente
> del siguiente período depende actualmente de registrar un pago.

Esto debe evolucionar.

------------------------------------------------------------------------

# 3. Nuevo modelo conceptual

El módulo debe distinguir claramente tres conceptos:

``` text
COMPROMISO RECURRENTE
        |
        | genera
        v
OBLIGACIÓN / OCURRENCIA
        |
        | puede recibir uno o varios
        v
PAGO
```

## 3.1 Compromiso recurrente

Representa la regla permanente.

Ejemplo:

``` text
BHD Platinum
Beneficiario: BHD
Categoría: Tarjeta
Frecuencia: Mensual
Día de corte: 15
Día límite: 10
Monto sugerido: RD$20,000
```

## 3.2 Obligación / ocurrencia

Representa una obligación concreta de un período.

Ejemplo:

``` text
Período: Agosto 2026
Fecha de corte: 15/07/2026
Fecha límite: 10/08/2026
Monto esperado/sugerido: RD$20,000
Estado: Pagado
Fecha de pago: 03/08/2026
```

La siguiente obligación debe existir conceptualmente aunque la anterior
no haya sido pagada:

``` text
Período: Septiembre 2026
Fecha de corte: 15/08/2026
Fecha límite: 10/09/2026
Estado: Pendiente
```

## 3.3 Pago

Representa la acción financiera realizada por el usuario.

El pago debe estar asociado a una obligación específica.

El usuario debe poder indicar explícitamente qué obligación está
pagando.

------------------------------------------------------------------------

# 4. Principio de independencia entre recurrencia y pago

Esta es una regla crítica.

La generación de nuevas obligaciones **NO debe depender de que la
obligación anterior haya sido pagada**.

Ejemplo:

``` text
Crediclick
Vencimiento: día 27
Frecuencia: mensual
```

Si no se paga:

``` text
27/07/2026 → Vencido
27/08/2026 → Vencido
27/09/2026 → Próximo
```

No se debe bloquear agosto porque julio no fue pagado.

Otro ejemplo:

``` text
Tarjeta
Corte: día 15
Pago: día 3
```

Si la obligación anterior no fue pagada:

``` text
03/08 → Vencida
15/08 → Nuevo corte
03/09 → Nueva obligación
```

Puede existir más de una obligación pendiente o vencida simultáneamente.

------------------------------------------------------------------------

# 5. Reglas para determinar cuándo nace una nueva obligación

## 5.1 Compromisos con fecha de corte

Cuando un compromiso tiene `has_cutoff = true`, la **fecha de corte es
el disparador de la nueva obligación**.

Ejemplo:

``` text
Corte: 15
Pago límite: 10
```

Al llegar:

``` text
15/08/2026
```

se genera/reconoce la nueva obligación:

``` text
Período: Septiembre 2026
Fecha de corte: 15/08/2026
Fecha límite: 10/09/2026
Estado: Pendiente
```

Esto debe ocurrir independientemente de si la obligación anterior fue
pagada.

### Regla

``` text
fecha_actual >= próxima_fecha_de_corte
        ↓
existe nueva ocurrencia
        ↓
crear/reconocer obligación
```

La fecha de corte representa el nacimiento de un nuevo ciclo de
facturación.

------------------------------------------------------------------------

# 6. Compromisos sin fecha de corte

Para compromisos que no tienen fecha de corte, la recurrencia debe estar
determinada por su frecuencia y fecha de vencimiento.

Ejemplo:

``` text
Crediclick
Frecuencia: mensual
Día de vencimiento: 27
```

Debe existir:

``` text
27/07/2026
27/08/2026
27/09/2026
27/10/2026
```

La generación de la siguiente obligación **no debe esperar a que se
pague la anterior**.

El pago solamente cambia el estado de la obligación correspondiente.

### Regla

``` text
frecuencia + regla de recurrencia
        ↓
determina próximas ocurrencias
```

No debe existir una dependencia:

``` text
pago anterior
    ↓
crear siguiente obligación
```

------------------------------------------------------------------------

# 7. La agenda debe mostrar obligaciones futuras

El cambio de mes calendario NO debe ser el disparador de visibilidad.

Ejemplo:

Hoy:

``` text
10/08/2026
```

Tarjeta:

``` text
Corte: 20/08
Pago: 03/09
```

La obligación del 03/09 debe poder mostrarse desde el 20/08, porque ese
día nace el nuevo ciclo.

No se debe esperar hasta:

``` text
01/09/2026
```

para mostrarla.

Esto es fundamental para que la aplicación funcione como una **agenda
financiera preventiva**.

------------------------------------------------------------------------

# 8. Estados de una obligación

La obligación debe poder encontrarse conceptualmente en:

``` text
pending
partially_paid
paid
overdue
cancelled
```

## 8.1 Pending

La fecha límite todavía no ha pasado y no se ha completado el pago.

## 8.2 Partially paid

Existe uno o más pagos, pero el monto pagado es inferior al monto
esperado.

## 8.3 Paid

La obligación fue completamente satisfecha.

## 8.4 Overdue

La fecha límite ya pasó y la obligación no está completamente pagada.

## 8.5 Cancelled

La obligación fue anulada deliberadamente.

------------------------------------------------------------------------

# 9. Cálculo de días

Debe diferenciarse claramente entre obligaciones pendientes/vencidas y
obligaciones pagadas.

## 9.1 Obligación pendiente

Si todavía no está pagada y la fecha límite es futura:

``` text
days_until_due = due_date - today
```

Ejemplo:

``` text
Fecha límite: 20/08
Hoy: 10/08

Faltan 10 días
```

La interfaz puede mostrar:

> Faltan 10 días

## 9.2 Obligación vencida

Si no está completamente pagada y:

``` text
today > due_date
```

entonces:

``` text
overdue_days = today - due_date
```

Ejemplo:

``` text
Fecha límite: 03/08
Hoy: 10/08

Vencido hace 7 días
```

------------------------------------------------------------------------

# 10. Regla crítica para obligaciones pagadas

Una obligación que ya fue pagada **NO debe mostrar días vencidos**.

Esto es incorrecto:

``` text
BHD Platinum
Pagado
Vencido hace 7 días
```

Una vez que una obligación está completamente pagada:

``` text
overdue_days = 0 / null
```

y nunca debe clasificarse visualmente como vencida.

------------------------------------------------------------------------

# 11. Información útil después de pagar

Aunque no debe mostrar días vencidos, sí debe calcularse cuánto faltaba
para la fecha límite cuando se realizó el pago.

Ejemplo:

``` text
Fecha límite: 10/08/2026
Fecha de pago: 03/08/2026
```

Resultado:

``` text
Pagado 7 días antes
```

La interfaz puede mostrar:

> 🟢 Pagado --- 7 días antes

Esto es útil para evaluar el comportamiento financiero del usuario.

### Regla

Si:

``` text
payment_date < due_date
```

entonces:

``` text
days_paid_early = due_date - payment_date
```

Si:

``` text
payment_date = due_date
```

mostrar:

> Pagado el día del vencimiento

Si:

``` text
payment_date > due_date
```

entonces:

``` text
days_paid_late = payment_date - due_date
```

mostrar:

> Pagado 3 días después

Esto último es diferente de decir que actualmente está vencido.

Una obligación pagada tarde queda:

``` text
Estado: Pagado
Resultado histórico: Pagado 3 días tarde
```

No:

``` text
Estado: Vencido
```

------------------------------------------------------------------------

# 12. Ejemplo completo

## Tarjeta BHD Platinum

Configuración:

``` text
Corte: día 15
Fecha límite: día 10
Monto sugerido: RD$20,000
Frecuencia: mensual
```

### Obligación agosto

``` text
Corte: 15/07/2026
Vencimiento: 10/08/2026
```

Se paga:

``` text
03/08/2026
RD$20,000
```

Resultado:

``` text
Estado: Pagado
Pagado: 7 días antes
Días vencidos: 0
```

### Nueva obligación

Al llegar el corte:

``` text
15/08/2026
```

se genera/reconoce:

``` text
Corte: 15/08/2026
Vencimiento: 10/09/2026
Estado: Pendiente
```

La agenda puede mostrar desde el 15/08:

> BHD Platinum --- vence 10/09 --- faltan 26 días

------------------------------------------------------------------------

# 13. Ejemplo de obligaciones acumuladas por falta de pago

## Crediclick

``` text
Monto: RD$17,000
Vencimiento: día 27
Frecuencia: mensual
```

No se paga julio:

``` text
27/07 → 🔴 Vencido
```

Llega agosto:

``` text
27/08 → 🔴 Vencido
```

Ahora existen dos obligaciones:

``` text
Julio  → RD$17,000 → Vencido
Agosto → RD$17,000 → Vencido
```

El sistema NO debe sustituir julio por agosto.

Si septiembre llega sin pagos:

``` text
Julio      → 🔴 Vencido
Agosto     → 🔴 Vencido
Septiembre → 🔴 Vencido
```

Esto permite visualizar la acumulación real de obligaciones.

------------------------------------------------------------------------

# 14. Registro de pagos

Al registrar un pago, el usuario debe seleccionar la obligación
específica.

Ejemplo:

``` text
Registrar pago

Compromiso:
Crediclick

Obligación:
[ Agosto 2026 — vence 27/08/2026 ]

Fecha de pago:
[ 25/08/2026 ]

Monto:
[ 17,000.00 ]
```

El pago debe afectar únicamente esa obligación.

No debe cambiar automáticamente:

-   la fecha de la siguiente obligación;
-   el período siguiente;
-   la frecuencia;
-   el día de corte;
-   el día de vencimiento.

------------------------------------------------------------------------

# 15. Pagos parciales

El sistema debe permitir que una obligación tenga un monto esperado y un
monto pagado acumulado.

Ejemplo:

``` text
Monto esperado: RD$12,000
Pago 1:          RD$5,000
Pago 2:          RD$7,000
```

Resultado:

``` text
Monto pagado: RD$12,000
Saldo: RD$0
Estado: Paid
```

Mientras:

``` text
Monto esperado: RD$12,000
Monto pagado:    RD$5,000
Saldo:           RD$7,000
```

debe permanecer:

``` text
Partially Paid
```

Si la fecha límite pasa mientras existe saldo:

``` text
Overdue
```

------------------------------------------------------------------------

# 16. Separar recurrencia de pagos

La arquitectura debe evolucionar conceptualmente hacia:

``` text
FinancialCommitment
        |
        | 1:N
        v
CommitmentOccurrence
        |
        | 1:N
        v
CommitmentPayment
```

Sin embargo, antes de crear nuevas tablas, evaluar si
`commitment_payments` puede evolucionar de forma segura para representar
la ocurrencia y sus pagos.

No realizar un rediseño de base de datos innecesario si la estructura
actual puede soportar correctamente las nuevas reglas.

------------------------------------------------------------------------

# 17. Unicidad de ocurrencias

Nunca debe existir más de una ocurrencia para el mismo compromiso y
período/ciclo.

Debe mantenerse una restricción equivalente a:

``` text
UNIQUE(
    financial_commitment_id,
    period_start
)
```

o una clave equivalente si se cambia el modelo.

El generador debe ser idempotente:

``` text
Generar ocurrencia
Generar nuevamente
Generar nuevamente
```

debe producir:

``` text
una sola ocurrencia
```

y no duplicados.

------------------------------------------------------------------------

# 18. Generación de ocurrencias

El sistema debe poder determinar las ocurrencias futuras sin depender de
pagos.

Para cada compromiso:

``` text
1. Determinar la regla de recurrencia.
2. Determinar la próxima fecha de disparo.
3. Si corresponde, crear/reconocer la ocurrencia.
4. Calcular su fecha límite.
5. Mantenerla como pendiente hasta que sea pagada.
6. Continuar calculando las siguientes ocurrencias.
```

La generación debe ser independiente de:

``` text
status = paid
status = pending
status = overdue
```

------------------------------------------------------------------------

# 19. Fecha de corte y fecha límite

Para compromisos con corte:

Si:

``` text
cutoff_day > due_day
```

el corte pertenece al mes anterior al vencimiento.

Ejemplo:

``` text
Corte: 15
Pago: 10

Corte: 15/08
Pago: 10/09
```

Si:

``` text
cutoff_day <= due_day
```

el comportamiento debe seguir las reglas actuales del servicio y
validarse con casos de borde.

La lógica debe manejar correctamente:

-   febrero;
-   meses de 30 días;
-   meses de 31 días;
-   día 31 configurado;
-   cambios de año.

------------------------------------------------------------------------

# 20. Agenda financiera

La agenda debe priorizar obligaciones por cercanía y riesgo.

Orden recomendado:

1.  🔴 Vencidas
2.  🟡 Próximas a vencer
3.  🔵 Futuras
4.  🟢 Pagadas

Una obligación pagada no debe competir con obligaciones pendientes por
prioridad de vencimiento.

Sin embargo, puede conservar información histórica:

``` text
🟢 Pagado
Pagado 7 días antes
```

------------------------------------------------------------------------

# 21. Información visual

Para una obligación pendiente:

``` text
🟡 Pendiente
Vence en 5 días
```

Para una obligación vencida:

``` text
🔴 Vencido hace 3 días
```

Para una obligación pagada antes:

``` text
🟢 Pagado
Pagado 7 días antes
```

Para una obligación pagada el día límite:

``` text
🟢 Pagado
Pagado el día del vencimiento
```

Para una obligación pagada tarde:

``` text
🟢 Pagado
Pagado 4 días después
```

Nunca mostrar simultáneamente:

``` text
🟢 Pagado
🔴 Vencido hace 4 días
```

------------------------------------------------------------------------

# 22. Próxima obligación

El sistema debe poder identificar claramente:

``` text
next_occurrence
```

para cada compromiso.

Ejemplo:

``` text
BHD Platinum
Actual: Agosto → Pagado
Próxima: Septiembre → Pendiente
Corte: 15/08
Vencimiento: 10/09
```

La próxima obligación debe ser visible incluso si todavía faltan semanas
para su vencimiento.

------------------------------------------------------------------------

# 23. Casos de prueba obligatorios

Antes de considerar terminada la implementación, probar como mínimo:

### Caso A --- Pago antes del vencimiento

``` text
Vence: 10/08
Pago: 03/08

Resultado:
Paid
7 días antes
0 días vencidos
```

### Caso B --- Pago el día del vencimiento

``` text
Vence: 10/08
Pago: 10/08

Resultado:
Paid
Pagado el día del vencimiento
0 días vencidos
```

### Caso C --- Pago después del vencimiento

``` text
Vence: 10/08
Pago: 13/08

Resultado:
Paid
Pagado 3 días tarde
0 días vencidos actuales
```

### Caso D --- Sin pago

``` text
Vence: 10/08
Hoy: 13/08

Resultado:
Overdue
3 días vencido
```

### Caso E --- Nueva obligación independiente

``` text
Julio → unpaid
Agosto → nueva obligación
```

Ambas deben existir.

### Caso F --- Tarjeta con corte

``` text
Corte: 15/08
Pago: 03/09
```

El 15/08 debe generarse/reconocerse la obligación de septiembre.

### Caso G --- Tarjeta no pagada

``` text
Obligación agosto → overdue
Nuevo corte → genera obligación septiembre
```

Ambas deben coexistir.

### Caso H --- Pago parcial

``` text
Esperado: 12,000
Pagado: 5,000

Resultado:
Partially Paid
Saldo: 7,000
```

### Caso I --- Dos pagos para una obligación

``` text
Pago 1: 5,000
Pago 2: 7,000

Resultado:
Paid
Saldo: 0
```

### Caso J --- No duplicar ocurrencias

Ejecutar varias veces el proceso de generación.

Resultado:

``` text
una sola ocurrencia por ciclo
```

------------------------------------------------------------------------

# 24. Restricciones

No modificar innecesariamente funcionalidades existentes.

No eliminar historial existente.

No cambiar las reglas financieras actuales sin documentar el impacto.

No asumir que `category = Tarjeta` es suficiente para determinar el
comportamiento. La presencia de fecha de corte (`has_cutoff`) debe
continuar siendo relevante.

No utilizar el estado `paid` como mecanismo para decidir si se genera
una nueva obligación.

No utilizar el cambio de mes calendario como mecanismo principal para
decidir cuándo nace una obligación.

No mostrar una obligación pagada como vencida.

No borrar información histórica al generar nuevas ocurrencias.

------------------------------------------------------------------------

# 25. Resultado esperado

Al finalizar, el módulo debe comportarse conceptualmente así:

``` text
                 COMPROMISO
                     |
                     v
             REGLA RECURRENTE
                     |
          +----------+----------+
          |                     |
      TIENE CORTE          SIN CORTE
          |                     |
          v                     v
    FECHA DE CORTE       FECHA DE RECURRENCIA
          |                     |
          +----------+----------+
                     |
                     v
              NUEVA OBLIGACIÓN
                     |
                     v
                  PENDIENTE
                     |
          +----------+----------+
          |                     |
          v                     v
       PAGADO                NO PAGADO
          |                     |
          v                     v
       🟢 Paid              ¿Venció?
                                |
                         +------+------+
                         |             |
                        NO             SÍ
                         |             |
                         v             v
                    🟡 Pending     🔴 Overdue
```

Y paralelamente:

``` text
REGLA DE RECURRENCIA
        |
        +---- genera obligación 1
        |
        +---- genera obligación 2
        |
        +---- genera obligación 3
        |
        +---- genera obligación 4
```

sin importar si:

``` text
obligación 1 = paid
obligación 2 = overdue
obligación 3 = pending
```

------------------------------------------------------------------------

# 26. Criterio de aceptación principal

La implementación será considerada correcta cuando un usuario pueda
tener simultáneamente:

``` text
Compromiso: Crediclick

Julio 2026       🔴 Vencido
Agosto 2026      🔴 Vencido
Septiembre 2026  🟡 Pendiente
Octubre 2026     🔵 Futuro
```

sin que ninguna obligación bloquee la generación de las siguientes.

Y para una tarjeta:

``` text
BHD Platinum

Agosto 2026
🟢 Pagado
Pagado 7 días antes

Septiembre 2026
🟡 Pendiente
Corte: 15/08
Vencimiento: 10/09
Faltan 31 días
```

Una obligación pagada debe quedar históricamente pagada y puede mostrar
**cuántos días antes o después se pagó**, pero nunca debe mostrar
simultáneamente que está vencida.

------------------------------------------------------------------------

# 27. Estrategia de implementación

Antes de modificar código:

1.  Auditar la implementación actual.
2.  Identificar qué reglas ya están soportadas.
3.  Determinar si `commitment_payments` puede evolucionar para soportar
    las nuevas reglas.
4.  Evitar migraciones estructurales innecesarias.
5.  Implementar primero las reglas de generación de ocurrencias.
6.  Implementar correctamente los estados.
7.  Corregir el cálculo de días.
8.  Corregir el flujo de registro de pagos para seleccionar la
    obligación.
9.  Agregar pruebas automatizadas para los casos críticos.
10. Revisar la interfaz de agenda.

No realizar cambios fuera del alcance del módulo.

------------------------------------------------------------------------

# 28. Regla de negocio resumida

> **La obligación nace por su regla de recurrencia, no por el pago de la
> obligación anterior.**

> **El pago únicamente afecta la obligación específica que el usuario
> está pagando.**

> **Las obligaciones vencidas pueden coexistir con obligaciones
> nuevas.**

> **Una obligación pagada deja de estar vencida, pero conserva el dato
> histórico de si fue pagada antes, el día o después de la fecha
> límite.**

> **La agenda debe mostrar las próximas obligaciones con suficiente
> anticipación para permitir planificación financiera.**
