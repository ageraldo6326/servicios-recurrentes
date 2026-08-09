# Commercial Module

## Objetivo

El módulo de gastos no planeado, busca registrar, clasificar, monitorear y controlar gastos no planificados, y recodar siempre al usuario que debe registrar los gastos hechos el dia anterior.

Tiene como proposito recodar al usuario el registro clasificado de gastos (entretenimiento, salud, juegos, dispositivos no necesarios, libros, etc) no programados, estos gastos son diferentes a los gastos recurrentes, el objetivo es registrar, saber, monitorear, controlar, y alertar sobre gastos hormiga, como son salidas a restaurantes, compra de pequenos lujos, no indispensables, recreacion, salida al cine, ropa no necesaria, etc.

Este módulo NO pretende ser un sistema contable, ni un sistema de presupuesto general.

Su objetivo es que el sistema recuerda siempre al usuario que registre los gastos, y que ademas el usuario pueda medir, monitorear y saber como estos gastos impactan en su finanza personal o del negocio, o en ambas, estos gastos no son gastos recurrentes, fijos, en su naturaleza tampoco tienen un monto fijo, fecha u horario, son gastos impulsivos, que el usuario necesia controlar.

Este modulo debe servir como una mano ayuda para que el usuario, registre sus gastos no programados, o gastos hormiga.

---

# Alcance del MVP

Esta primera versión implementará únicamente:

- Dashboard de Gastos Hormiga
- Recordatorio para registro
- Alertas sobre montos excesivos de gastos hormiga

No se implementarán todavía:

- CRUD de gastos hormiga no complicados
- CRUD de datos de soporte para completar el registro del CRUD de gastos hormiga
- Clasificacion en cuentas contables
- Recuperacion de gastos
- Integración con otros modulos

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

# Ampliación del módulo Clientes

Agregar únicamente los campos que sean necesarios para soportar el registro de estos gastos.

Ejemplos:

- Nombre del gasto
- Tipo de gasto
- Monto del gasto
- Lugar del gasto
- Fecha del gasto
- Fecha de registro
- observaciones

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
- Gastos Hormiga

---

# Dashboard

Mostrar indicadores de gastos.

Ejemplos:

- Monto gastado
- Monto pagado
- Monto pendiente
- Monto de gastos por tipo
- Últimos gastos
- Tipo de gastos con mayor monto acumulado

La arquitectura deberá permitir agregar nuevos widgets posteriormente.

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

La creación de un gasto debe tomar menos de 30 segundos.

La interfaz debe priorizar:

- rapidez
- simplicidad
- productividad

Reducir al mínimo la cantidad de clics necesarios.

---

# Objetivo Estratégico

Este módulo buscara ayudar al usuario a detectar aquellos gastos no planificados, que puedan estar afectando su flujo de efectio persona y/o de la compañia.

En futuras versiones deberá integrarse de forma natural con:

Por esta razón, todas las decisiones de diseño deberán facilitar la evolución del sistema sin requerir cambios estructurales importantes.

# Modulo de IA para consejos

El usuario podra apoyarse en la IA para que analice el patron de gastos hormiga, y que le sugiera posibles opciones para mejorar sus habitos.
