# AGENTS.md

> Alcance: todo el repositorio.
>
> Este documento es la guía principal para cualquier agente de IA o desarrollador
> que trabaje en este proyecto.

## Compatibilidad hacia atrás (Backward Compatibility)

El sistema evolucionará mediante módulos incrementales.

Está prohibido modificar o eliminar funcionalidades existentes para implementar nuevas características.

Toda nueva funcionalidad debe integrarse respetando el comportamiento actual del sistema.

Las migraciones deben ser siempre aditivas.

Nunca modificar una migración ya ejecutada.

Nunca eliminar columnas existentes.

Nunca eliminar tablas existentes.

Nunca borrar datos del usuario.

Si un cambio requiere alterar información histórica, deberá implementarse mediante una estrategia de migración de datos compatible y reversible.

## Diseño de la interfaz (Obligatorio)

Toda nueva interfaz debe respetar el diseño existente del proyecto.

Antes de implementar cualquier vista, el agente deberá revisar la carpeta:

docs/diseno/

Esta carpeta contiene el diseño de referencia generado en Google Stitch.

Está prohibido:

- inventar un nuevo estilo visual;
- cambiar colores principales;
- cambiar tipografía;
- cambiar espaciados;
- cambiar estructura de navegación;
- utilizar componentes visuales distintos a los ya definidos.

Toda nueva pantalla debe parecer parte del mismo sistema.

El objetivo es que el usuario no pueda distinguir cuáles pantallas fueron desarrolladas primero y cuáles fueron agregadas posteriormente.

## 1. Propósito del proyecto

Este proyecto es un MVP para gestionar un negocio basado en servicios recurrentes.

No es un ERP completo.
No es un sistema contable.
No es un CRM tradicional.

El sistema existe para ayudar a operar y proyectar un negocio que vende servicios como:

- PBX
- Dialers
- DID
- Troncales SIP
- VPS
- Servidores dedicados
- Hosting
- Servicios administrados

El objetivo es responder tres preguntas de negocio:

1. ¿Qué debo gestionar hoy?
2. ¿Cuánto dinero debería entrar en el período seleccionado?
3. ¿Podré cubrir los pagos a mis proveedores?

Todo lo demás es secundario.

## 2. Filosofía del MVP

El MVP debe permanecer pequeño, claro y funcional.

Si una funcionalidad no es indispensable para operar el negocio diario, no pertenece al MVP.

Cuando exista duda:

- favorecer simplicidad;
- evitar sobreingeniería;
- evitar abstracciones innecesarias;
- evitar características futuras antes de tiempo.

## 3. Principio rector del dominio

El corazón del sistema es el **Servicio Contratado**.

Si no existe un servicio contratado, no existe nada más que administrar:

- no hay cobro;
- no hay pago;
- no hay gestión;
- no hay proyección;
- no hay seguimiento.

Todo gira alrededor del servicio contratado.

## 4. Estructura de trabajo para IA

Antes de modificar cualquier cosa, el agente debe leer:

1. `docs/02-mvp-scope.md`
2. `docs/03-business-rules.md`
3. `docs/04-domain-model.md`
4. `docs/05-modules.md`
5. `docs/06-use-cases.md`
6. `docs/09-coding-standards.md`

Si existe contradicción:

- `03-business-rules.md` tiene prioridad sobre todos los demás.
- `04-domain-model.md` tiene prioridad sobre módulos y UI.
- `09-coding-standards.md` tiene prioridad sobre la implementación técnica.

## 5. Módulos del MVP

El MVP incluye únicamente:

- Dashboard operativo
- Dashboard ejecutivo
- Dashboard de seguimiento
- Clientes
- Catálogo de servicios
- Servicios contratados
- Cobros
- Pagos
- Gestiones
- Proveedores

Cualquier otro módulo pertenece al backlog.

## 6. Reglas obligatorias para la IA

- No inventar requisitos.
- No modificar reglas de negocio sin aprobación explícita.
- No crear entidades fuera del dominio acordado.
- No agregar campos "por si acaso".
- No agregar pantallas "por si acaso".
- No convertir el MVP en una versión avanzada.
- No eliminar historial.
- No simplificar el negocio sacrificando trazabilidad.
- No asumir que un servicio contratado puede pertenecer a varios clientes.
- No asumir que los cobros se generan sin relación con un servicio contratado.

## 7. Estilo de implementación

El código debe ser:

- simple;
- legible;
- consistente con Laravel;
- fácil de extender sin complicar el MVP.

Prioridades:

1. Correctitud funcional
2. Legibilidad
3. Mantenibilidad
4. Rendimiento razonable
5. Extensibilidad futura

## 8. Reglas técnicas generales

Stack preferido:

- Laravel 12
- PHP 8.3+
- MySQL
- Blade
- Livewire
- Tailwind CSS

Preferencias:

- usar Form Requests;
- usar Eloquent relations;
- usar Enums para estados;
- usar Policies donde aplique;
- mantener controladores delgados;
- evitar lógica de negocio en Blade;
- evitar consultas en vistas;
- evitar SQL crudo salvo necesidad justificada.

## 9. Definición de terminado

Una funcionalidad solo se considera terminada si:

- respeta las reglas de negocio;
- tiene validación;
- tiene relaciones correctas;
- está integrada con el flujo del MVP;
- no rompe el historial;
- no introduce complejidad innecesaria;
- queda alineada con la documentación.

## 10. Diseño y look and feel

El diseño debe ser responsivo ante todo movil first

- Debe basar su diseño en las imagenes y codigos incluidos en la carpeta docs/diseno
- Amigable
- CSS3
- HTML5
- Tailwind
- Diseño limpio, intuitivo
- Gestion de darkmode
- no ejecutar ninguna migracion para fines de modificar o mejora el diseño

## 11. Reactividad

- Usar livewire para la reactividad
- El sistema debe ser reactivo en busquedas y otros aspectos
- Mostrar interaccion moderna, sin recargar la pantalla
- Mostrar reloj o indicadores, cuando se requiera tiempo para refrescar
- Priorizar la velocidad

## 11. Login

Integrar el sistema de login y registro de laravel ya instalado

- laravel/breeze con livewire
- proteger las rutas en base a login
- aplicar las seguridad correspondiente

## 12. Regla final

Si algo mejora el futuro pero no ayuda al MVP a funcionar hoy, se pospone.

## 13. Protección obligatoria de datos y pruebas

La base de datos de desarrollo puede contener información real del usuario.
Su protección tiene prioridad absoluta.

- Está prohibido ejecutar `migrate:fresh`, `db:wipe`, `migrate:refresh`, `schema:drop`, `DROP DATABASE`, `DROP TABLE`, `TRUNCATE` o comandos equivalentes contra la conexión definida en `.env`.
- Está prohibido ejecutar `php artisan test` hasta verificar que `APP_ENV=testing`, `DB_CONNECTION=sqlite` y `DB_DATABASE=:memory:` estén forzados por `phpunit.xml` o `.env.testing`.
- Nunca se deben usar pruebas con MariaDB, MySQL o la base de desarrollo real.
- Antes de cualquier prueba que use `RefreshDatabase`, confirmar la conexión efectiva; no basta con revisar `.env.example`.
- No ejecutar migraciones como parte de pruebas, diseño visual o compilación de assets.
- Antes de cualquier comando que pueda modificar una base, crear o verificar un respaldo y confirmar explícitamente la base objetivo.
- Si el estado de la conexión es incierto, detenerse y pedir autorización; nunca asumir que la base es de pruebas.
- Los datos del usuario no se deben borrar, truncar, sobrescribir ni reemplazar. Si ocurre un incidente, detener todas las escrituras y recuperar mediante respaldo o binlog antes de continuar.
