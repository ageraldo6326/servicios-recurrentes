# Sistema de Gestión de Servicios Recurrentes

Este proyecto es un MVP para operar un negocio basado en servicios recurrentes y su cobranza mensual.

## ¿Qué resuelve?

La aplicación permite:
- registrar clientes;
- registrar servicios contratados;
- gestionar cobros y pagos;
- registrar gestiones por WhatsApp o llamada;
- proyectar ingresos;
- proyectar costos de proveedores;
- ver el estado operativo y financiero del negocio.

## Enfoque del producto

El sistema no está pensado como un CRM tradicional.
Está pensado como una herramienta de gestión operativa y financiera para servicios recurrentes.

## Estructura de documentación

- `AGENTS.md`: reglas para agentes de IA y desarrolladores.
- `docs/01-product.md`: visión del producto.
- `docs/02-mvp-scope.md`: alcance del MVP.
- `docs/03-business-rules.md`: reglas de negocio.
- `docs/04-domain-model.md`: entidades y relaciones.
- `docs/05-modules.md`: módulos funcionales.
- `docs/06-use-cases.md`: casos de uso principales.
- `docs/07-ui-guidelines.md`: criterios de interfaz.
- `docs/08-coding-standards.md`: estándares de código.
- `docs/09-roadmap.md`: evolución prevista.
- `docs/10-backlog.md`: ideas fuera del MVP.
- `docs/11-decisions.md`: decisiones ya fijadas.

## MVP primero

Cualquier funcionalidad adicional debe pasar por esta pregunta:

> ¿Es indispensable para que el sistema funcione en el día a día?

Si la respuesta es no, va al backlog.

## Stack sugerido

- Laravel 12
- PHP 8.3+
- MySQL
- Blade
- Livewire
- Tailwind CSS

## Orden recomendado de desarrollo

1. Modelo del dominio
2. Base de datos
3. Relaciones Eloquent
4. Validaciones
5. Módulos operativos
6. Dashboards
7. Pruebas
# servicios-recurrentes
