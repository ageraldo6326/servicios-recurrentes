# Commercial Module

## Objetivo

El módulo Comercial será el núcleo del proceso comercial del CRM.

Su propósito es permitir la creación y administración de cotizaciones y facturas para servicios profesionales, consultorías, soporte técnico, implementaciones, auditorías, desarrollos y cualquier otro servicio puntual ofrecido por la empresa.

Este módulo NO pretende ser un sistema contable ni un ERP.

Su objetivo es facilitar la gestión comercial y mantener un historial completo de todos los documentos comerciales asociados a cada cliente.

La experiencia de usuario debe inspirarse en Invoice Ninja por su simplicidad, rapidez y facilidad de uso, sin copiar su código ni su arquitectura.

---

# Alcance del MVP

Esta primera versión implementará únicamente:

- Dashboard Comercial
- Cotizaciones
- Facturas

No se implementarán todavía:

- Cobros
- Pagos
- Contratos
- Facturación recurrente
- Catálogo de servicios
- Notas de crédito
- Portal del cliente

La arquitectura deberá quedar preparada para incorporar estos módulos en futuras versiones sin necesidad de rediseñar la base de datos.

---

# Arquitectura

Seguir todas las reglas establecidas en AGENTS.md.

Aplicar:

- SOLID
- Clean Architecture
- DRY
- KISS
- PSR-12

Toda la lógica de negocio deberá implementarse utilizando Services y Actions.

Los componentes Livewire únicamente administrarán la interfaz.

No colocar lógica de negocio en Blade.

No colocar lógica compleja en Controllers.

---

# Integración con Clientes

El CRM ya posee un módulo de Clientes completamente funcional.

Este módulo deberá reutilizar dicho módulo.

Está prohibido crear una nueva tabla de clientes.

Si el módulo Comercial requiere información adicional deberá extender la estructura existente mediante migraciones.

Nunca duplicar información.

Toda cotización y factura deberá pertenecer a un cliente existente.

---

# Ampliación del módulo Clientes

Agregar únicamente los campos que sean necesarios para soportar el proceso comercial.

Ejemplos:

- contacto_principal
- cargo_contacto
- correo_comercial
- telefono_comercial
- direccion_comercial
- ciudad
- provincia
- pais
- identificacion_fiscal
- condicion_pago
- moneda_preferida
- observaciones_comerciales

Todos estos campos deberán:

- permitir NULL
- ser opcionales
- agregarse mediante migraciones de alteración

Nunca eliminar columnas existentes.

Nunca renombrar columnas existentes.

Nunca borrar información existente.

---

# Migraciones

Todas las migraciones deberán ser aditivas.

Está prohibido:

- eliminar tablas
- eliminar columnas
- borrar registros
- reinicializar la base de datos
- utilizar migrate:fresh

Todas las modificaciones deberán realizarse utilizando Schema::table().

El proyecto debe poder actualizar una base de datos existente sin afectar información en producción.

---

# Menú

Comercial

- Dashboard
- Cotizaciones
- Facturas

---

# Dashboard Comercial

Mostrar indicadores comerciales.

Ejemplos:

- Facturas emitidas este mes
- Monto facturado
- Cotizaciones abiertas
- Cotizaciones aceptadas
- Cotizaciones rechazadas
- Últimas cotizaciones
- Últimas facturas
- Clientes con mayor facturación

La arquitectura deberá permitir agregar nuevos widgets posteriormente.

---

# Cotizaciones

Implementar CRUD completo.

Estados:

- Borrador
- Enviada
- Vista
- Aceptada
- Rechazada
- Expirada
- Convertida

Cada cotización tendrá:

- Cliente
- Número
- Fecha
- Fecha de vencimiento
- Moneda
- Descuento
- Observaciones
- Notas
- Términos
- Comentarios
- Historial
- PDF
- Items

Acciones:

- Guardar
- Duplicar
- Descargar PDF
- Imprimir
- Enviar por correo
- Convertir en factura

---

# Facturas

Implementar CRUD completo.

Estados:

- Borrador
- Pendiente
- Pagada (preparado)
- Parcial (preparado)
- Vencida (preparado)
- Anulada (preparado)

Una factura únicamente podrá:

- crearse manualmente
- generarse desde una cotización

No implementar otros orígenes en esta versión.

---

# Pantalla de edición

Tomar como referencia la experiencia de usuario de Invoice Ninja.

La pantalla deberá incluir:

- Cliente
- Datos generales
- Detalle
- Totales
- Notas
- Términos
- Comentarios
- Historial

Acciones principales:

- Guardar
- Enviar correo
- Ver PDF
- Imprimir
- Descargar
- Duplicar

La prioridad es reducir la cantidad de clics necesarios para crear una factura.

---

# Detalle de documentos

No existirá un catálogo de servicios.

Cada línea será completamente editable.

Campos:

- Concepto
- Descripción
- Cantidad
- Unidad
- Precio Unitario
- Descuento
- Impuestos
- Total

El usuario podrá agregar o eliminar líneas libremente.

---

# Historial Inteligente

Para agilizar la captura, el sistema deberá aprender de la información existente.

Mientras el usuario escribe el concepto, deberá sugerir conceptos previamente utilizados en cotizaciones y facturas.

Ejemplo:

El usuario escribe:

Auditoría

El sistema podrá sugerir:

- Auditoría Técnica
- Auditoría Vicidial
- Auditoría Asterisk

Mostrando además:

- descripción
- precio promedio
- unidad utilizada

Estas sugerencias se generan automáticamente a partir del historial.

No existe un catálogo administrable.

---

# Plantillas

El usuario podrá guardar cualquier línea como plantilla.

Ejemplo:

Servicio:

Auditoría Técnica

↓

Guardar como plantilla

Posteriormente podrá reutilizarla al crear nuevos documentos.

Las plantillas únicamente sirven para agilizar la captura.

No representan inventario.

---

# Integración con IA

Agregar botón:

✨ Mejorar descripción

La IA deberá convertir una descripción corta en una descripción comercial profesional.

Ejemplo:

Entrada:

Auditoría Vicidial

Salida:

Prestación de servicios profesionales de auditoría técnica sobre la plataforma VICIdial, incluyendo revisión de configuración, análisis de rendimiento, evaluación de seguridad e identificación de oportunidades de mejora.

---

Agregar botón:

✨ Mejorar redacción

La IA podrá mejorar el texto escrito por el usuario.

Nunca modificar automáticamente el contenido.

Siempre solicitar confirmación.

---

# PDF

Generar documentos profesionales.

Preparar la arquitectura para permitir personalizar posteriormente:

- Logo
- Encabezado
- Pie de página
- Colores
- Términos
- Notas

No desarrollar editor de plantillas en esta versión.

---

# Auditoría

Registrar:

- Usuario creador
- Usuario modificador
- Fecha de creación
- Fecha de modificación

Preparar la estructura para almacenar historial de cambios.

---

# Seguridad

Utilizar el sistema de autenticación y autorización existente en el proyecto.

No instalar paquetes adicionales de permisos.

No modificar la arquitectura actual de autenticación.

---

# Pruebas

Las pruebas nunca deberán afectar la información existente.

Está prohibido utilizar:

- RefreshDatabase
- DatabaseMigrations
- DatabaseTruncation
- migrate:fresh
- db:wipe
- truncate

Las pruebas deberán:

- crear únicamente información temporal
- eliminar únicamente la información creada por la propia prueba
- preservar completamente la información existente

---

# Compatibilidad

Este módulo será desarrollado sobre un proyecto existente.

Antes de crear:

- Modelos
- Componentes
- Rutas
- Controladores
- Migraciones

deberá verificarse si ya existe una implementación reutilizable.

Siempre reutilizar antes de crear.

Nunca duplicar funcionalidades.

Nunca romper módulos existentes.

---

# Experiencia de Usuario

La creación de una cotización o factura debe tomar menos de un minuto.

La interfaz debe priorizar:

- rapidez
- simplicidad
- productividad

Reducir al mínimo la cantidad de clics necesarios.

Inspirarse visualmente en Invoice Ninja, manteniendo la identidad visual del CRM.

---

# Objetivo Estratégico

Este módulo será el núcleo comercial del CRM.

En futuras versiones deberá integrarse de forma natural con:

- Cobros
- Pagos
- Contratos
- Facturación recurrente
- Portal del Cliente
- Business Coach

Por esta razón, todas las decisiones de diseño deberán facilitar la evolución del sistema sin requerir cambios estructurales importantes.
