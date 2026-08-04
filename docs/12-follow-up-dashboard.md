# 12. Dashboard de Seguimiento

## Objetivo

El Dashboard de Seguimiento es la pantalla principal utilizada por el operador de cobranza.

Su propósito no es mostrar indicadores.

Su propósito es decirle al usuario exactamente con cuáles servicios contratados debe trabajar hoy.

El operador nunca debería preguntarse:

> "¿A quién debo escribir ahora?"

El sistema debe responder esa pregunta automáticamente.

---

# Filosofía

El sistema trabaja sobre Servicios Contratados.

No sobre clientes.

No sobre cobros.

No sobre pagos.

Cada tarjeta del dashboard representa un Servicio Contratado que requiere una acción.

---

# Pregunta que responde

¿Qué servicios requieren seguimiento hoy?

---

# Fuente de información

La lista debe construirse utilizando:

- estado del servicio contratado
- fecha del próximo seguimiento
- fecha del último seguimiento
- promesas de pago
- fecha de vencimiento
- estatus del cobro
- cantidad de días sin contacto
- pagos pendientes de validar

Nunca debe depender únicamente del estado del cobro.

---

# Tipos de seguimiento

## Cobrar

Servicios con pago pendiente.

---

## Recordar

Clientes cuyo vencimiento está próximo.

---

## Promesa de pago

Clientes cuya promesa vence hoy.

---

## Segundo contacto

Clientes que no respondieron al primer intento.

---

## Riesgo de cancelación

Clientes con varios días sin respuesta.

---

## Pago pendiente de validar

Clientes que enviaron evidencia de pago.

---

# Orden de prioridad

El dashboard debe ordenar automáticamente.

Prioridad sugerida:

1. Promesas vencidas

2. Cobros vencidos

3. Promesas para hoy

4. Cobros de hoy

5. Clientes sin respuesta

6. Próximos vencimientos

---

# Información por tarjeta

Cada tarjeta debe mostrar únicamente la información necesaria para tomar una decisión.

Debe incluir:

Nombre del cliente

Servicio

Monto pendiente

Fecha de vencimiento

Días de atraso

Última gestión

Resultado de la última gestión

Próximo seguimiento

Proveedor (opcional)

Estado del servicio

---

# Acciones rápidas

Cada tarjeta debe permitir:

Enviar WhatsApp

Llamar

Registrar gestión

Registrar pago

Validar pago

Reprogramar seguimiento

Cancelar servicio

Abrir detalle completo

Todo debe realizarse desde la misma pantalla.

---

# Indicadores superiores

La parte superior del Dashboard debe mostrar:

Servicios pendientes de seguimiento

Promesas para hoy

Cobros vencidos

Pagos pendientes de validar

Clientes en riesgo de cancelación

---

# Filtros

Debe ser posible filtrar por:

Proveedor

Tipo de servicio

Estado

Gestor

Fecha

Cliente

---

# Colores sugeridos

Rojo

Servicios vencidos

Naranja

Promesas de hoy

Amarillo

Seguimientos programados

Azul

Pago pendiente de validar

Verde

Servicio al día

---

# Objetivo UX

El operador debe poder trabajar toda la mañana desde esta pantalla.

No debería abrir múltiples módulos para realizar su trabajo.

---

# Reglas de negocio

Nunca eliminar seguimientos.

Todo seguimiento genera historial.

Cada seguimiento puede generar un próximo seguimiento.

Un servicio puede tener múltiples seguimientos.

El último seguimiento determina el estado operativo del servicio.

---

# Definición de terminado

Este módulo estará terminado cuando un operador pueda:

1. Abrir el Dashboard.

2. Ver inmediatamente con quién debe trabajar.

3. Contactar al cliente.

4. Registrar el resultado.

5. Programar el siguiente seguimiento.

6. Continuar con el siguiente cliente sin abandonar la pantalla.

El Dashboard de Seguimiento debe convertirse en la pantalla principal del sistema y representar la cola de trabajo diaria del operador.