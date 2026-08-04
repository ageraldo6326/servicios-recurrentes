# 04. Modelo de dominio

## Entidades principales

### Cliente
Representa a una persona o empresa que contrata servicios.

Campos mínimos:
- nombre;
- teléfono.

Relaciones:
- tiene muchos servicios contratados;
- puede tener muchos teléfonos históricos si se decide ampliar luego.

### Servicio del catálogo
Representa el producto comercial.

Ejemplos:
- PBX;
- Dialer;
- DID;
- VPS;
- IA.

Relaciones:
- puede ser usado por muchos servicios contratados.

### Servicio contratado
Representa la contratación concreta de un cliente.

Es la entidad central del sistema.

Contiene:
- cliente;
- servicio del catálogo;
- precio;
- costo;
- proveedor;
- IP opcional;
- día de cobro;
- estatus;
- fecha de inicio;
- observaciones.

Relaciones:
- pertenece a un cliente;
- pertenece a un servicio del catálogo;
- pertenece a un proveedor;
- genera cobros;
- recibe pagos indirectamente;
- registra gestiones.

### Cobro
Representa la obligación de cobro en un período.

Contiene:
- estatus;
- monto;
- fecha.

Relaciones:
- pertenece a un servicio contratado;
- puede recibir uno o varios pagos.

### Pago
Representa el dinero recibido del cliente.

Contiene:
- monto;
- fecha;
- evidencia;
- validación.

Relaciones:
- puede aplicarse a uno o varios cobros.

### Gestión
Representa una interacción operativa con el cliente.

Ejemplos:
- WhatsApp;
- llamada;
- promesa de pago;
- observación.

Relaciones:
- pertenece a un servicio contratado;
- pertenece a un cliente;
- puede referenciar un teléfono usado en ese momento.

### Proveedor
Representa el tercero al que la empresa debe pagar para sostener el servicio.

Contiene:
- nombre;
- forma de pago;
- acepta pagos parciales o no;
- observaciones.

Relaciones:
- puede tener muchos servicios contratados;
- puede tener facturas mensuales.

### Factura de proveedor
Entidad futura o complementaria para el control del costo mensual del proveedor.

En el MVP puede modelarse de forma simple, pero el dominio la reconoce como parte del negocio.

## Relación conceptual principal

Cliente
→ Servicio contratado
→ Cobro
→ Pago

Cliente
→ Servicio contratado
→ Gestión

Servicio contratado
→ Proveedor

## Regla central

Si no existe un servicio contratado, no existe movimiento operativo ni financiero que administrar.
