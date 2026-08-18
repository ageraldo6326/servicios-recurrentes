# Funcionalidad: Alertas inteligentes para tarjetas de crédito

## Módulo afectado

Gestión de Compromisos → Tarjetas de Crédito

## Objetivo

Agregar al módulo existente de Gestión de Compromisos una funcionalidad específica para tarjetas de crédito que ayude al usuario a tomar mejores decisiones de pago y de consumo en función de la fecha de corte y la fecha límite de pago.

La funcionalidad debe perseguir dos objetivos principales:

1. Alertar cuando se aproxima la fecha de corte para motivar al usuario a reducir o pagar el balance pendiente antes del cierre del ciclo, cuando esto forme parte de su estrategia financiera.
2. Identificar los días inmediatamente posteriores al corte como la ventana más eficiente para realizar nuevas compras, ya que permiten maximizar la cantidad de días disponibles antes de tener que pagar esos consumos.

---

## Conceptos utilizados

### Fecha de corte

Es la fecha en la que la entidad financiera cierra el ciclo de facturación de la tarjeta.

Los consumos procesados antes o en esa fecha normalmente forman parte del estado de cuenta que se genera en dicho corte.

### Fecha límite de pago

Es la fecha máxima para pagar el balance correspondiente al estado de cuenta sin incurrir en intereses de financiamiento, siempre que se pague el saldo requerido en su totalidad y no existan condiciones especiales del emisor.

### Ventana ideal de compra

Son los días inmediatamente posteriores a la fecha de corte.

Ejemplo:

```text
Fecha de corte: día 2

Días ideales para comprar:
3, 4, 5, 6, 7...
```

Una compra realizada justo después del corte normalmente será incluida en el siguiente ciclo, permitiendo aprovechar prácticamente un ciclo completo más el período entre el próximo corte y su fecha límite de pago.

---

## Principio financiero de la funcionalidad

La aplicación no debe recomendar gastar más.

Debe recomendar utilizar de forma más eficiente el plazo de crédito disponible cuando el usuario ya ha decidido realizar una compra y dispone de capacidad para pagarla.

La regla principal será:

> Realizar compras planificadas preferiblemente después de la fecha de corte y pagar el 100 % del saldo al corte antes de la fecha límite de pago.

La aplicación debe diferenciar claramente entre:

- saldo actual;
- saldo al corte;
- pago mínimo;
- pago total;
- fecha de corte;
- fecha límite de pago;
- fecha recomendada de pago.

Nunca se debe presentar el pago mínimo como estrategia para evitar intereses.

---

# Funcionalidades requeridas

## 1. Alerta previa al corte

El sistema debe detectar cuando una tarjeta se encuentra próxima a su fecha de corte.

Configuración inicial recomendada:

```text
7 días antes del corte  → aviso informativo
3 días antes del corte  → alerta importante
1 día antes del corte   → alerta prioritaria
Día del corte           → alerta de cierre
```

Ejemplo:

> 🟡 Tu tarjeta BHD corta en 3 días, el 2 de septiembre.  
> Balance actual: RD$24,350.  
> Si deseas iniciar el próximo ciclo con menor utilización, considera realizar un pago antes del corte.

Esta alerta debe ser configurable.

---

## 2. Alerta de pago completo

Cuando se acerque la fecha límite de pago, el sistema debe priorizar el pago completo del saldo al corte.

Ejemplo:

> 🔴 Tu tarjeta BHD vence en 3 días.  
> Saldo al corte: RD$18,000.  
> Para evitar financiamiento, procura pagar RD$18,000 antes del 24 de septiembre.

Estados sugeridos:

```text
7 días antes → recordatorio
3 días antes → importante
1 día antes  → urgente
día límite   → crítico
```

La aplicación debe permitir definir una fecha objetivo interna anterior a la fecha límite bancaria.

Ejemplo:

```text
Fecha límite bancaria: 24
Margen de seguridad:    2 días
Fecha objetivo:         22
```

De esta forma, la aplicación recomendará pagar el día 22 aunque el banco permita hacerlo hasta el día 24.

---

## 3. Ventana ideal para nuevas compras

Después de producirse el corte, el sistema debe cambiar el tipo de recomendación.

Ejemplo:

> 🟢 Buen momento para comprar con esta tarjeta.  
> El corte ocurrió ayer.  
> Los nuevos consumos tendrán aproximadamente 50 días antes de su fecha estimada de pago.

La ventana ideal debe calcularse dinámicamente.

Configuración inicial recomendada:

```text
Día 1 al 7 después del corte  → Excelente
Día 8 al 15                   → Buena
Día 16 al 22                  → Regular
Últimos días antes del corte  → Poco conveniente
```

Estos rangos deben ser configurables.

---

## 4. Indicador de eficiencia de compra

Para cada tarjeta se debe mostrar un indicador basado en la fecha actual.

Estados propuestos:

```text
🟢 EXCELENTE
🟢 BUENA
🟡 REGULAR
🟠 POCO CONVENIENTE
🔴 ESPERAR AL PRÓXIMO CORTE
```

Ejemplo de tarjeta en pantalla:

```text
BHD Platinum
Corte: 2 de cada mes
Pago: 24 de cada mes

Estado actual:
🟡 REGULAR PARA COMPRAR

Si compras hoy:
~37 días estimados para pagar

Si esperas hasta el 3 de septiembre:
~51 días estimados para pagar

Ganancia estimada:
14 días adicionales
```

---

# Cálculos

## Próxima fecha de corte

El sistema debe determinar la siguiente fecha de corte a partir de:

```text
día_de_corte
fecha_actual
```

Si el corte del mes actual ya pasó:

```text
próximo_corte = corte del mes siguiente
```

Si todavía no ha pasado:

```text
próximo_corte = corte del mes actual
```

Debe contemplarse correctamente el cambio de mes y de año.

---

## Días hasta el próximo corte

```text
dias_hasta_corte = proximo_corte - fecha_actual
```

---

## Fecha ideal inicial de compra

Por defecto:

```text
fecha_ideal = fecha_de_corte + 1 día
```

Ejemplo:

```text
Corte:       2 septiembre
Fecha ideal: 3 septiembre
```

---

## Días estimados de financiamiento sin intereses

Cuando exista una fecha límite de pago conocida:

```text
dias_estimados =
fecha_limite_asociada_al_ciclo
-
fecha_de_compra
```

Ejemplo:

```text
Compra:             3 septiembre
Próximo corte:      2 octubre
Fecha límite:       24 octubre

Días aproximados:
51
```

Este valor debe mostrarse como estimado debido a que:

- la fecha de contabilización puede diferir de la fecha de consumo;
- el banco puede modificar una fecha límite;
- fines de semana y feriados pueden afectar procesamiento;
- cada emisor puede aplicar reglas particulares.

---

# Flujo de estados

## Fase A: después del corte

```text
CORTE
  ↓
Ventana ideal de compra
  ↓
Ventana buena
  ↓
Ventana regular
```

Objetivo:

Maximizar los días disponibles antes del pago del próximo estado.

---

## Fase B: aproximación al siguiente corte

```text
7 días antes
  ↓
3 días antes
  ↓
1 día antes
  ↓
CORTE
```

Objetivo:

Informar al usuario de que está finalizando el ciclo.

El sistema puede recomendar:

- evitar consumos no urgentes;
- esperar al próximo ciclo si la compra puede posponerse;
- revisar el balance actual;
- realizar un pago anticipado si forma parte de la estrategia del usuario.

---

## Fase C: después del corte y antes del vencimiento

Para el estado de cuenta ya generado:

```text
CORTE
  ↓
Saldo al corte conocido
  ↓
Recordatorio de pago
  ↓
Fecha objetivo interna
  ↓
Fecha límite bancaria
```

Objetivo:

Pagar el saldo al corte completo antes del vencimiento.

---

# Reglas de negocio

## Regla 1

Una compra no debe calificarse solamente como buena o mala.

Debe indicarse:

```text
días estimados para pagar si compra hoy
```

y, cuando corresponda:

```text
días adicionales que obtendría esperando al próximo corte
```

---

## Regla 2

Si faltan pocos días para el corte y la compra no es urgente, el sistema debe recomendar esperar.

Ejemplo:

> 🟠 Esta tarjeta corta dentro de 2 días.  
> Si la compra puede esperar, realizarla después del corte podría darte aproximadamente 28 días adicionales antes del pago.

---

## Regla 3

Si el usuario registra que una compra es urgente, la aplicación no debe bloquearla.

Solo debe mostrar información.

---

## Regla 4

La aplicación nunca debe sugerir realizar una compra únicamente porque existe crédito disponible.

---

## Regla 5

La recomendación de compra debe tomar en cuenta el saldo disponible.

Ejemplo:

```text
Límite:          RD$100,000
Balance actual:  RD$82,000
Disponible:      RD$18,000
Compra prevista: RD$18,000
```

Aunque la fecha sea ideal, la aplicación debe advertir sobre la alta utilización.

---

## Regla 6

Una tarjeta con balance vencido o financiado no debe mostrar simplemente:

```text
Buen momento para comprar
```

Debe priorizar:

> 🔴 Existe un saldo pendiente de pago. Revisa primero la deuda actual antes de realizar nuevos consumos.

---

## Regla 7

Cuando el compromiso esté marcado como pagado, no debe contabilizarse como vencido.

Debe conservarse la funcionalidad existente que muestra cuántos días antes fue realizado el pago.

---

# Datos sugeridos

Si actualmente el compromiso representa una tarjeta, se pueden agregar o reutilizar campos similares a los siguientes:

```text
tipo_compromiso
dia_corte
dia_limite_pago
margen_seguridad_pago
balance_actual
saldo_al_corte
limite_credito
moneda
ultimo_corte
ultima_fecha_pago
ultimo_pago
alertas_compra_activas
alertas_pago_activas
```

Si la arquitectura actual mantiene información específica de tarjetas en otra tabla, estos campos deben ubicarse allí para evitar sobrecargar la tabla general de compromisos.

---

# Configuración por tarjeta

Cada tarjeta debería permitir configurar:

```text
Día de corte
Día límite de pago
Margen de seguridad
Días considerados ideales para comprar
Alertas previas al corte
Alertas previas al pago
Límite de crédito
Moneda
```

Ejemplo:

```text
Tarjeta: BHD
Corte: 2
Pago: 24

Margen de pago:
2 días

Ventana ideal:
3 al 9

Alertas de corte:
7, 3 y 1 día antes

Alertas de pago:
7, 3 y 1 día antes
```

---

# Widget recomendado en el Dashboard

## Tarjeta en período ideal

```text
💳 BHD Platinum

🟢 Excelente momento para comprar

Corte anterior:
2 de septiembre

Próximo corte:
2 de octubre

Pago estimado:
24 de octubre

Si compras hoy:
~49 días para pagar
```

---

## Tarjeta próxima al corte

```text
💳 BHD Platinum

🟠 Próxima al corte

Faltan:
2 días

Próximo corte:
2 de septiembre

Recomendación:
Si la compra no es urgente, espera hasta después del corte.

Mejor fecha:
3 de septiembre
```

---

## Tarjeta próxima al pago

```text
💳 BHD Platinum

🔴 Pago próximo

Saldo al corte:
RD$18,000

Fecha objetivo:
22 de septiembre

Fecha límite:
24 de septiembre

Recomendación:
Pagar RD$18,000 para evitar financiamiento.
```

---

# Alertas globales del módulo

En la pantalla principal de compromisos se puede mostrar una sección:

## Alertas financieras

Ejemplo:

```text
🔴 BHD Platinum
Pago recomendado mañana
RD$18,000

🟠 Visa Reservas
Corta en 2 días
Espera al próximo corte para compras no urgentes

🟢 Mastercard
Cortó ayer
Excelente ventana para nuevas compras
```

---

# Prioridad de alertas

Orden recomendado:

```text
1. Pago vencido
2. Fecha límite hoy
3. Fecha objetivo de pago
4. Saldo al corte pendiente
5. Corte próximo
6. Ventana ideal de compra
7. Información general
```

Una alerta de compra nunca debe tener mayor prioridad visual que una obligación pendiente de pago.

---

# Notificaciones

La funcionalidad debe quedar preparada para soportar:

- notificación dentro del sistema;
- dashboard;
- correo electrónico;
- WhatsApp;
- notificaciones push.

Inicialmente puede implementarse solamente dentro de la aplicación.

---

# Ejemplo práctico

Tarjeta:

```text
Banco: BHD
Corte: día 2
Pago: día 24
```

## 18 de agosto

```text
Próximo corte:
2 septiembre

Días para el corte:
15

Estado:
🟡 Compra aceptable

Si compra hoy:
aprox. 37 días hasta el pago

Mejor próxima fecha:
3 septiembre
```

## 31 de agosto

```text
Próximo corte:
2 septiembre

Faltan:
2 días

Estado:
🟠 Poco conveniente

Mensaje:
Si la compra no es urgente, espera 3 días.
```

## 3 de septiembre

```text
Corte anterior:
2 septiembre

Estado:
🟢 Excelente

Mensaje:
El ciclo acaba de comenzar.
Este es uno de los mejores días para realizar una compra planificada.
```

---

# Posible implementación en Laravel

La lógica debería encapsularse en un servicio y no colocarse directamente en controladores o componentes Livewire.

Ejemplo conceptual:

```text
app/
└── Services/
    └── CreditCardStrategyService.php
```

Responsabilidades:

```text
getNextClosingDate()
getPreviousClosingDate()
getNextPaymentDate()
getRecommendedPaymentDate()
getDaysUntilClosing()
getEstimatedDaysToPay()
getPurchaseEfficiencyScore()
getPurchaseRecommendation()
getPaymentAlert()
getClosingAlert()
```

Esto permitirá reutilizar la lógica desde:

- Dashboard;
- módulo de compromisos;
- componentes Livewire;
- notificaciones;
- procesos programados;
- futura capa de IA.

---

# Scheduler

Las alertas pueden evaluarse diariamente mediante Laravel Scheduler.

Ejemplo conceptual:

```text
php artisan schedule:run
```

Proceso diario:

```text
1. Obtener tarjetas activas.
2. Calcular próximo corte.
3. Calcular próximo pago.
4. Evaluar alertas.
5. Crear o actualizar notificaciones.
6. Evitar notificaciones duplicadas.
```

---

# Integración futura con IA

Esta información debe quedar disponible para el análisis financiero de la aplicación.

Ejemplos de consultas:

> Quiero comprar un televisor de RD$18,000. ¿Cuál de mis tarjetas me conviene usar hoy?

El sistema podría comparar:

```text
Tarjeta A → 48 días estimados
Tarjeta B → 23 días estimados
Tarjeta C → 35 días estimados
```

y responder:

> La Tarjeta A es actualmente la más eficiente para esta compra porque acaba de iniciar su ciclo y te ofrece aproximadamente 48 días antes del pago.

También deberá considerar:

- límite disponible;
- utilización actual;
- compromisos futuros;
- flujo de caja;
- saldo pendiente;
- moneda;
- capacidad de pago.

---

# Criterios de aceptación

La funcionalidad se considerará terminada cuando:

- [ ] Cada tarjeta permita registrar fecha de corte.
- [ ] Cada tarjeta permita registrar fecha límite de pago.
- [ ] Se calcule automáticamente el próximo corte.
- [ ] Se calcule automáticamente la próxima fecha de pago.
- [ ] Se muestre cuántos días faltan para el corte.
- [ ] Se muestre cuántos días faltan para el pago.
- [ ] Se identifique la ventana ideal de compra.
- [ ] Se calculen los días estimados para pagar una compra realizada hoy.
- [ ] Se indique cuánto ganaría el usuario esperando al siguiente corte.
- [ ] Se genere una alerta cuando se aproxime el corte.
- [ ] Se genere una alerta cuando se aproxime la fecha de pago.
- [ ] Se permita definir un margen de seguridad antes del vencimiento.
- [ ] El saldo al corte tenga prioridad sobre recomendaciones de nuevas compras.
- [ ] Una tarjeta vencida o financiada no recomiende nuevas compras sin advertencia.
- [ ] Las recomendaciones sean informativas y no bloqueen operaciones.
- [ ] Las alertas no se dupliquen.
- [ ] La lógica pueda reutilizarse desde Dashboard, Livewire y futuras notificaciones.

---

# Resultado esperado

El módulo de Gestión de Compromisos dejará de funcionar únicamente como calendario de obligaciones y comenzará a ofrecer asistencia financiera contextual para las tarjetas de crédito.

El usuario podrá saber:

```text
¿Cuándo debo pagar?
¿Cuándo corta?
¿Cuántos días faltan?
¿Es buen momento para comprar?
¿Cuántos días tendría para pagar si compro hoy?
¿Cuánto gano esperando al próximo corte?
¿Tengo un saldo que debería priorizar antes de consumir?
```

La finalidad no es incentivar el endeudamiento, sino ayudar a utilizar las tarjetas de crédito con mayor disciplina, previsión y eficiencia de flujo de efectivo.
