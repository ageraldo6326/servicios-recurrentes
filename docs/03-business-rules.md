# 03. Reglas de negocio

## Clientes

- Un cliente puede tener múltiples servicios al mismo tiempo.
- Un cliente puede cambiar su número de WhatsApp.
- Es conveniente conservar teléfonos históricos para contexto de gestiones.
- El cliente se identifica por nombre y teléfono en el MVP.

## Servicios

- Un servicio del catálogo representa un producto comercial reusable.
- Un servicio del catálogo puede ser desactivado.
- Si un servicio deja de existir, no debe usarse para nuevos clientes.
- Los servicios del catálogo pueden tener variantes por capacidad o tamaño.

Ejemplos:
- Dialer básico 10 agentes
- Dialer mediano 25 agentes
- Dialer grande 50 agentes

## Servicio contratado

- Un servicio contratado pertenece a un solo cliente.
- Un servicio contratado pertenece a un solo servicio del catálogo.
- El proveedor pertenece al servicio contratado.
- El costo pertenece al servicio contratado.
- El precio puede cambiar y se actualiza sobre el mismo servicio contratado.
- Cambiar el precio, proveedor, costo u otro campo no crea automáticamente otro servicio ni cancela el actual.
- La cancelación es una acción explícita e independiente de la edición.
- Los cobros y gestiones existentes conservan su relación con el servicio contratado.

## Cobranza

- El sistema debe mostrar el mes actual y el siguiente.
- El usuario necesita ver el flujo que debería entrar en un rango de fechas.
- El cobro existe mientras exista el servicio contratado.
- El cobro tiene al menos:
  - estatus;
  - monto;
  - fecha.

## Pago

- El pago es dinero real recibido.
- El cliente puede enviar evidencia del pago por foto o captura.
- El pago debe ser validado manualmente por el usuario.
- Un pago puede cubrir varios servicios.
- Un cobro puede recibir más de un pago.
- Un pago parcial debe permitirse.
- El pago adelantado debe poder imputarse al siguiente período.
- En caso de pago adelantado, el sistema debe permitir reflejarlo en el mes siguiente.

## Gestión

- La comunicación principal es WhatsApp.
- Si el cliente no responde, se sigue con el siguiente.
- Si el cliente promete pagar, se registra la promesa.
- Si el cliente no responde durante 1 o 2 días, el sistema debe sugerir cancelación.
- Toda cancelación debe registrar una razón.

## Cancelación

- La cancelación es inmediata.
- No se conserva la IP como parte activa del servicio.
- El servicio se elimina del proveedor operativamente.
- No existe renovación ni reactivación del mismo servicio como continuidad.
- Si el cliente vuelve a contratar, es un servicio nuevo.

## Activación

- Cuando se activa un servicio, se paga por adelantado el período correspondiente.
- Si el servicio inicia el 15, el pago cubre hasta el 15 del siguiente mes.
- El primer período se considera ya cubierto desde la activación.

## Proveedores

- Los proveedores son fundamentales para proyectar costos mensuales.
- Algunos proveedores aceptan pagos parciales y otros no.
- El sistema debe permitir reflejar esa diferencia.
- El usuario necesita conocer cuánto debe cubrir al final del mes.

## Proyección financiera

- El sistema debe permitir ver ingresos proyectados por rango de fechas.
- El sistema debe permitir ver costos proyectados por rango de fechas.
- El sistema debe permitir ver utilidad proyectada por rango de fechas.
- La moneda operativa puede ser USD en la proyección aunque el cobro al cliente se registre en pesos si así lo requiere la operación.

## Historial

- El historial es importante para revisar rendimiento, cobros y utilidad.
- No todo histórico debe mostrarse en la pantalla principal.
- El sistema debe priorizar lo operativo, pero conservar lo necesario para análisis.
