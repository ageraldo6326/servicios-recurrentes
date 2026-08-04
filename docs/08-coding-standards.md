# 08. Estándares de código

## Stack

- Laravel 12
- PHP 8.3+
- MySQL
- Blade
- Livewire
- Tailwind CSS

## Principios

- escribir código simple;
- evitar duplicación;
- mantener controladores delgados;
- usar convenciones Laravel;
- usar nombres claros;
- evitar dependencias innecesarias.

## Modelos

- un modelo por entidad del dominio;
- relaciones Eloquent explícitas;
- atributos calculados solo cuando aporten valor.

## Validación

- usar Form Requests;
- no repetir lógica de validación en varios lugares;
- mantener mensajes claros para el usuario.

## Estados

- usar Enums cuando exista un conjunto finito de estados;
- evitar strings mágicos duplicados por todo el código.

## Lógica de negocio

- preferir Actions o Services para operaciones complejas;
- no colocar lógica de negocio en Blade;
- no mezclar reglas de negocio con presentación.

## Vistas

- no consultar la base de datos desde Blade;
- no calcular reglas importantes en la vista;
- Blade debe mostrar información, no decidir negocio.

## Consultas

- usar Eloquent cuando sea suficiente;
- usar SQL crudo solo si existe una justificación real;
- optimizar solo cuando exista necesidad.

## Pruebas

- cubrir flujos críticos;
- validar creación de cliente;
- validar creación de servicio contratado;
- validar cobro;
- validar pago;
- validar cancelación;
- validar proyección básica.

## Nombres

- modelos en singular;
- tablas en plural;
- métodos descriptivos;
- variables claras;
- evitar abreviaturas oscuras.
