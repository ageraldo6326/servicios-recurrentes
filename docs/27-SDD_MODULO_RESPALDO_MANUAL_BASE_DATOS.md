# SDD — Módulo de respaldo manual de base de datos

## 1. Información general

### 1.1 Nombre del módulo

Respaldo manual de base de datos.

### 1.2 Objetivo

Implementar dentro del sistema una funcionalidad administrativa que permita generar manualmente un respaldo comprimido de la base de datos MySQL/MariaDB y descargarlo inmediatamente a la computadora del usuario.

El módulo también deberá recordar al usuario cuándo corresponde realizar un nuevo respaldo, utilizando un intervalo configurable de cada N días.

### 1.3 Contexto

El sistema se encuentra en producción y contiene información que debe protegerse ante errores, daños o pérdida de datos. En esta primera etapa se requiere una solución sencilla, controlada y manual.

El respaldo incluirá la estructura y los datos de la base de datos. No incluirá los archivos físicos cargados en el módulo de cuadernos ni otros archivos almacenados por la aplicación.

## 2. Alcance

### 2.1 Incluido en esta versión

- Pantalla administrativa para consultar el estado de los respaldos.
- Configuración del intervalo de recordatorio en días.
- Botón para iniciar manualmente la generación del respaldo.
- Generación de un archivo SQL de la base de datos.
- Compresión del respaldo en formato `.sql.gz`.
- Descarga del archivo comprimido mediante el navegador.
- Registro de la fecha y hora del último respaldo generado correctamente.
- Cálculo de la próxima fecha recomendada para realizar un respaldo.
- Notificación visual cuando corresponda realizar uno nuevo.
- Historial mínimo de intentos y resultados, sin almacenar el contenido del respaldo.
- Controles de autorización, seguridad, concurrencia, auditoría y manejo de errores.

### 2.2 Fuera de alcance

- Respaldos automáticos mediante cron, scheduler, colas o scripts externos.
- Respaldos programados sin intervención del usuario.
- Respaldo de adjuntos, imágenes o documentos cargados en el cuaderno.
- Respaldo de otros directorios de almacenamiento de la aplicación.
- Almacenamiento permanente de los archivos de respaldo en el servidor.
- Envío del respaldo por correo electrónico.
- Carga del respaldo a servicios externos o almacenamiento en la nube.
- Restauración de la base de datos desde la interfaz.
- Eliminación o modificación de datos existentes durante el proceso.

La automatización, los respaldos de archivos y el proceso de restauración podrán evaluarse como fases posteriores independientes.

## 3. Principios de implementación

- Inspeccionar primero la arquitectura, autenticación, configuración, componentes, patrones visuales y sistema de permisos existentes en el repositorio.
- Las versiones instaladas y la arquitectura real del proyecto serán la fuente de verdad. No actualizar Laravel, PHP, Livewire, Filament, MySQL/MariaDB ni otras dependencias como parte de este módulo.
- Reutilizar servicios, componentes, layouts, notificaciones y convenciones existentes.
- Usar migraciones aditivas y reversibles.
- No ejecutar `migrate:fresh`, `db:wipe`, `truncate` ni operaciones destructivas sobre la base de datos de producción.
- No instalar un paquete de roles o permisos si el sistema ya cuenta con autorización mediante Policies, Gates, middleware u otro mecanismo propio.
- Mantener el módulo desacoplado del módulo de cuadernos.

## 4. Actores y permisos

### 4.1 Administrador autorizado

Podrá:

- Acceder a la pantalla de respaldos.
- Consultar el estado y el historial.
- Cambiar el intervalo de recordatorio.
- Generar y descargar un respaldo.

### 4.2 Usuario no autorizado

No podrá:

- Acceder a las rutas del módulo.
- Consultar metadatos de respaldos.
- Generar ni descargar respaldos.
- Modificar la configuración.

La autorización debe validarse en el servidor para cada acción. Ocultar el enlace o el botón en la interfaz no será suficiente.

### 4.3 Permisos sugeridos

Adaptar los nombres al sistema de autorización ya existente:

- `database-backups.view`
- `database-backups.create`
- `database-backups.configure`

Si el sistema utiliza solamente roles, estas acciones deberán limitarse inicialmente al rol administrador.

## 5. Requerimientos funcionales

### RF-01. Acceso al módulo

El sistema deberá mostrar una opción de menú denominada `Respaldos` o `Respaldo de base de datos`, visible únicamente para usuarios autorizados.

### RF-02. Panel de estado

La pantalla principal deberá mostrar:

- Fecha y hora del último respaldo completado correctamente.
- Usuario que lo generó.
- Estado actual: `Al día`, `Próximo a vencer`, `Pendiente` o `Nunca realizado`.
- Intervalo configurado en días.
- Próxima fecha recomendada.
- Días restantes o días de atraso.
- Botón `Generar y descargar respaldo`.
- Acceso al historial de ejecuciones.

Si nunca se ha realizado un respaldo, el estado será `Nunca realizado` y deberá mostrarse la recomendación inmediatamente.

### RF-03. Configuración del recordatorio

Un usuario con permiso de configuración podrá indicar cada cuántos días debe realizarse el respaldo.

Reglas:

- El valor será un número entero positivo.
- Rango permitido sugerido: entre 1 y 365 días.
- Valor inicial sugerido: 7 días.
- El cambio deberá quedar auditado.
- La próxima fecha se calculará a partir del último respaldo completado correctamente.
- Cambiar el intervalo no deberá modificar la fecha del último respaldo.

### RF-04. Notificación de respaldo pendiente

Cuando la fecha actual sea igual o posterior a la próxima fecha recomendada, el sistema deberá mostrar una notificación visible al usuario autorizado.

La notificación podrá mostrarse:

- En el panel principal o dashboard administrativo.
- En el menú del módulo mediante un indicador.
- Dentro de la pantalla de respaldos.

Mensaje sugerido:

> El respaldo de la base de datos está pendiente. El último respaldo fue realizado hace N días.

La notificación no deberá bloquear el uso del sistema. Desaparecerá cuando se complete correctamente un nuevo respaldo o cuando deje de estar vencido por un cambio válido de configuración.

No se requiere una tarea programada para calcular el vencimiento: puede determinarse al cargar las pantallas correspondientes, comparando la fecha actual con la fecha calculada.

### RF-05. Confirmación previa

Antes de iniciar, el sistema deberá presentar una confirmación que informe:

- Que se respaldarán la estructura y los datos de la base de datos.
- Que los archivos adjuntos del cuaderno no están incluidos.
- Que el archivo contiene información sensible y debe guardarse en un lugar seguro.
- Que el proceso puede tardar según el tamaño de la base de datos.

El usuario deberá confirmar expresamente la acción.

### RF-06. Generación del respaldo

Al confirmar, el backend deberá:

1. Verificar nuevamente la autenticación y autorización.
2. Evitar que el mismo usuario o varios usuarios inicien respaldos simultáneos.
3. Obtener la conexión MySQL/MariaDB utilizada por la aplicación desde la configuración resuelta del framework.
4. Ejecutar una herramienta compatible de respaldo, preferiblemente `mysqldump` o `mariadb-dump` según lo disponible en el servidor.
5. Incluir la estructura y los datos de la base de datos.
6. Incluir rutinas, triggers y eventos cuando existan y la cuenta de base de datos tenga los permisos necesarios.
7. Comprimir la salida con Gzip.
8. Validar que el archivo exista, no esté vacío y sea un Gzip íntegro.
9. Registrar el resultado.
10. Entregar el archivo al navegador para su descarga.
11. Eliminar del servidor el archivo temporal al terminar la respuesta o mediante un mecanismo de limpieza segura.

Comando técnico de referencia, adaptado de forma segura a la configuración real:

```bash
mysqldump --single-transaction --quick --routines --triggers --events DATABASE | gzip
```

No se deberá construir un comando concatenando directamente valores sin escapar. Utilizar un ejecutor de procesos seguro que permita pasar los argumentos por separado.

### RF-07. Nombre del archivo

Formato sugerido:

```text
backup_nombre-sistema_YYYY-MM-DD_HH-mm-ss.sql.gz
```

El nombre no deberá exponer credenciales, direcciones internas, nombre de usuario de la base de datos ni otra información sensible.

### RF-08. Descarga local

El respaldo se descargará mediante una respuesta del navegador.

El sistema podrá confirmar que el archivo fue generado y que inició su entrega, pero no puede garantizar que el usuario lo haya conservado correctamente en su computadora. La interfaz deberá usar la expresión `Respaldo generado` y evitar afirmar `Respaldo guardado en su computadora`.

### RF-09. Registro del último respaldo

La fecha del último respaldo exitoso se actualizará únicamente cuando:

- El proceso de exportación termine sin errores.
- El archivo generado no esté vacío.
- La integridad de la compresión sea válida.

Un intento fallido no deberá cambiar la fecha del último respaldo exitoso ni ocultar la notificación pendiente.

### RF-10. Historial

El módulo deberá conservar metadatos de cada intento:

- Usuario que inició el proceso.
- Fecha y hora de inicio.
- Fecha y hora de finalización.
- Estado: `Procesando`, `Completado` o `Fallido`.
- Tamaño final del archivo, si fue completado.
- Duración del proceso.
- Nombre lógico del archivo.
- Mensaje de error sanitizado, si corresponde.

El historial no almacenará:

- El contenido del respaldo.
- Contraseñas.
- Comandos completos con credenciales.
- Archivos `.sql` o `.sql.gz` persistentes.

### RF-11. Prevención de procesos duplicados

Solo podrá existir un respaldo en ejecución a la vez para la misma base de datos.

Si ya existe uno, el sistema mostrará:

> Ya existe un respaldo en proceso. Espere a que termine antes de iniciar otro.

El bloqueo deberá tener vencimiento para evitar que un proceso interrumpido deje el módulo bloqueado indefinidamente.

### RF-12. Manejo de errores

Si ocurre un error:

- No se descargará un archivo incompleto como si fuera válido.
- Se eliminarán los archivos temporales creados por el intento.
- Se liberará el bloqueo de ejecución.
- Se registrará el intento como fallido.
- Se mostrará al usuario un mensaje comprensible y un identificador de seguimiento.
- Los detalles técnicos se enviarán al log protegido de la aplicación sin incluir credenciales.

Mensajes posibles:

- `No fue posible generar el respaldo. Revise la configuración del servidor.`
- `La herramienta de respaldo no está disponible en el servidor.`
- `El respaldo excedió el tiempo máximo permitido.`
- `No hay espacio temporal suficiente para completar el respaldo.`

## 6. Flujo principal

1. El administrador entra al módulo.
2. El sistema consulta el último respaldo exitoso y el intervalo configurado.
3. El sistema muestra el estado y la próxima fecha recomendada.
4. El administrador pulsa `Generar y descargar respaldo`.
5. El sistema muestra la confirmación y las exclusiones.
6. El administrador confirma.
7. El backend valida permisos, crea el bloqueo y registra el intento como `Procesando`.
8. El backend genera el SQL y lo comprime en una ubicación temporal privada.
9. El backend valida el tamaño y la integridad del Gzip.
10. El intento cambia a `Completado` y se actualiza la referencia del último respaldo exitoso.
11. El navegador inicia la descarga.
12. El archivo temporal se elimina del servidor.
13. El panel muestra la nueva fecha y el estado `Al día`.

## 7. Reglas de negocio

### RN-01. Cálculo de próxima fecha

```text
proxima_fecha = ultimo_respaldo_exitoso + intervalo_dias
```

### RN-02. Estado del recordatorio

- `Nunca realizado`: no existe un respaldo exitoso.
- `Al día`: faltan más de 24 horas para la próxima fecha.
- `Próximo a vencer`: faltan 24 horas o menos.
- `Pendiente`: la fecha actual es igual o posterior a la próxima fecha.

El proyecto podrá adaptar el umbral de `Próximo a vencer` a sus convenciones visuales existentes.

### RN-03. Intentos fallidos

Los intentos fallidos quedarán en el historial, pero no reiniciarán el conteo de N días.

### RN-04. Archivos excluidos

El módulo respaldará únicamente la base de datos configurada para la aplicación. No recorrerá `storage`, `public`, directorios de adjuntos ni rutas del módulo de cuadernos.

### RN-05. Temporalidad del archivo

El archivo será temporal y existirá en el servidor únicamente durante el tiempo necesario para generarlo, validarlo y entregarlo. No habrá una biblioteca de respaldos descargables desde el servidor en esta versión.

### RN-06. Zona horaria

Las fechas se almacenarán siguiendo la convención actual del proyecto, preferiblemente en UTC, y se mostrarán en la zona horaria configurada para el sistema o el usuario.

## 8. Diseño de datos propuesto

Antes de crear tablas nuevas, verificar si el sistema ya dispone de una tabla o servicio general de configuraciones y auditoría. Reutilizarlos cuando sea apropiado.

### 8.1 Configuración

Si existe una tabla general de configuraciones, agregar una clave equivalente a:

```text
database_backup_reminder_days = 7
```

Si no existe un mecanismo de configuración, crear una tabla específica:

#### `database_backup_settings`

| Campo | Tipo sugerido | Descripción |
| --- | --- | --- |
| `id` | bigint | Identificador |
| `reminder_interval_days` | unsigned small integer | Intervalo N entre respaldos |
| `updated_by` | foreign key nullable | Usuario que cambió la configuración |
| `created_at` | timestamp | Fecha de creación |
| `updated_at` | timestamp | Fecha de modificación |

Debe existir una sola configuración activa para la base de datos de la aplicación.

### 8.2 Historial

#### `database_backup_runs`

| Campo | Tipo sugerido | Descripción |
| --- | --- | --- |
| `id` | bigint | Identificador |
| `user_id` | foreign key | Usuario que inició el respaldo |
| `status` | string o enum lógico | `processing`, `completed`, `failed` |
| `file_name` | string nullable | Nombre lógico, no ruta física |
| `file_size_bytes` | unsigned bigint nullable | Tamaño del archivo comprimido |
| `started_at` | timestamp | Inicio |
| `completed_at` | timestamp nullable | Finalización |
| `duration_seconds` | unsigned integer nullable | Duración |
| `error_code` | string nullable | Código interno sanitizado |
| `error_message` | text nullable | Mensaje seguro, sin credenciales |
| `created_at` | timestamp | Auditoría |
| `updated_at` | timestamp | Auditoría |

El último respaldo exitoso podrá obtenerse consultando el último registro `completed`. No es obligatorio duplicar esa fecha en configuración.

## 9. Diseño técnico

### 9.1 Componentes sugeridos

Adaptar nombres y ubicación a la arquitectura real del repositorio:

- Pantalla o componente administrativo `DatabaseBackups`.
- Servicio `DatabaseBackupService`.
- Objeto de resultado `DatabaseBackupResult`, si las convenciones del proyecto lo justifican.
- Policy, Gate o middleware de autorización.
- Servicio o componente de notificación reutilizable.
- Modelos `DatabaseBackupRun` y, solo si hace falta, `DatabaseBackupSetting`.

La interfaz puede implementarse con Livewire, Filament o el patrón que ya utilice el área administrativa. No introducir una segunda arquitectura de interfaz para este módulo.

### 9.2 Herramienta de exportación

En el despliegue deberá verificarse cuál herramienta está disponible:

- `mariadb-dump`, o
- `mysqldump`.

La aplicación no deberá instalar paquetes del sistema operativo automáticamente desde una petición web.

### 9.3 Credenciales

- Obtener host, puerto, base de datos, usuario y contraseña desde la configuración resuelta de Laravel.
- No leer ni mostrar directamente el archivo `.env` en la interfaz.
- No incluir la contraseña en logs, mensajes de error, historial ni nombre de archivo.
- Evitar colocar la contraseña directamente en los argumentos visibles del proceso cuando la herramienta y el entorno permitan un mecanismo más seguro, como un archivo temporal de opciones con permisos restrictivos.
- El archivo temporal de credenciales deberá eliminarse en un bloque de limpieza garantizada.

### 9.4 Consistencia del respaldo

- Usar `--single-transaction` para tablas transaccionales como InnoDB.
- Usar `--quick` para procesar los registros por flujo y reducir el consumo de memoria.
- No bloquear innecesariamente las tablas de producción.
- Documentar que la consistencia de tablas no transaccionales puede requerir un tratamiento distinto si existen en el esquema.

### 9.5 Compresión y validación

- La compresión deberá realizarse sin cargar todo el respaldo en memoria.
- Validar que el proceso de exportación y el de compresión terminen con código exitoso.
- Verificar que el archivo sea mayor de cero bytes.
- Ejecutar una validación equivalente a `gzip -t`.
- Un Gzip válido no garantiza por sí solo que el SQL sea restaurable; por eso deberá comprobarse también el resultado del proceso de exportación.

### 9.6 Ubicación temporal

- Usar una ruta privada no accesible directamente desde Internet.
- No guardar el respaldo dentro de `public`.
- Crear nombres aleatorios internamente para evitar colisiones.
- Aplicar permisos restrictivos al directorio y al archivo.
- Programar una limpieza defensiva de temporales huérfanos desde el propio flujo del módulo. Esta limpieza no constituye un respaldo automático.
- Nunca borrar archivos fuera del directorio temporal específico del módulo.

### 9.7 Límites operativos

El servicio deberá definir valores configurables a nivel de despliegue para:

- Tiempo máximo del proceso.
- Tamaño máximo temporal permitido, si corresponde.
- Antigüedad máxima de archivos temporales huérfanos.
- Tiempo de vencimiento del bloqueo de concurrencia.

Los valores deben ajustarse al tamaño real de la base de datos y a los límites del servidor web o proxy.

### 9.8 Descarga y limpieza

Usar una respuesta de descarga que elimine el archivo temporal después de enviarlo, junto con un mecanismo de limpieza en `finally` para errores previos al envío.

Si el framework o servidor no garantiza la eliminación después de una desconexión del cliente, el módulo deberá limpiar archivos huérfanos en el siguiente acceso o ejecución autorizada, limitado a su propio directorio temporal.

## 10. Interfaz propuesta

### 10.1 Tarjeta de estado

- Estado actual con color e icono.
- Último respaldo.
- Próximo respaldo recomendado.
- Intervalo actual.
- Usuario que realizó el último respaldo.

### 10.2 Acciones

- `Generar y descargar respaldo`.
- `Configurar recordatorio`, solo con permiso.
- `Ver historial`.

### 10.3 Estado durante el proceso

Mientras se genera el respaldo:

- Deshabilitar el botón para evitar doble envío.
- Mostrar `Generando respaldo...`.
- Informar que no se cierre la ventana hasta que comience la descarga.
- No mostrar una barra de porcentaje falsa si el backend no puede calcular el progreso real.

## 11. Seguridad

- Requerir autenticación y autorización en backend.
- Proteger las solicitudes con CSRF.
- Utilizar `POST` para iniciar el respaldo y `PUT` o `PATCH` para modificar la configuración.
- Aplicar límite de frecuencia a la acción de generación.
- Considerar confirmación reciente de contraseña para la descarga, reutilizando el mecanismo existente si ya está disponible.
- Impedir inyección de comandos mediante argumentos separados y validación estricta.
- No aceptar desde el navegador el nombre de la base de datos, rutas del servidor ni parámetros arbitrarios del comando.
- Usar siempre la base de datos configurada por la aplicación.
- No permitir descargar archivos indicando una ruta en la URL.
- Añadir cabeceras para evitar caché del contenido sensible.
- No registrar el contenido SQL.
- Sanitizar la salida estándar y de errores antes de almacenarla o mostrarla.
- Auditar generación, resultado y cambios de configuración.
- Garantizar que el usuario del proceso web tenga solo los permisos de sistema estrictamente necesarios.
- Revisar que el usuario de MySQL/MariaDB tenga permisos suficientes para exportar, sin otorgarle privilegios administrativos innecesarios.

## 12. Observabilidad y auditoría

Registrar en los logs de la aplicación:

- Inicio y fin del proceso.
- Identificador del intento.
- Usuario responsable.
- Duración.
- Tamaño final.
- Herramienta de respaldo utilizada.
- Resultado general y código de error sanitizado.

No registrar:

- Contraseña de la base de datos.
- Contenido del respaldo.
- Comando completo si contiene información sensible.
- Rutas internas innecesarias en mensajes visibles al usuario.

## 13. Pruebas requeridas

### 13.1 Pruebas de autorización

- Un administrador autorizado puede ver el módulo.
- Un usuario sin permiso recibe una respuesta 403 aunque conozca la URL.
- Un usuario con permiso de lectura, pero sin permiso de creación, no puede iniciar un respaldo.
- Un usuario sin permiso de configuración no puede cambiar el intervalo.

### 13.2 Pruebas de configuración

- Acepta valores enteros dentro del rango.
- Rechaza cero, negativos, decimales, texto y valores fuera del rango.
- Recalcula la próxima fecha sin alterar el último respaldo.

### 13.3 Pruebas del recordatorio

- Muestra `Nunca realizado` cuando no hay respaldos exitosos.
- Muestra `Al día` antes de la fecha.
- Muestra `Próximo a vencer` según el umbral.
- Muestra `Pendiente` al alcanzar o superar la fecha.
- Un intento fallido no elimina la notificación.
- Un respaldo exitoso reinicia el conteo.

### 13.4 Pruebas de generación

- Genera un archivo `.sql.gz` descargable.
- El Gzip supera la prueba de integridad.
- El SQL contiene estructura y datos esperados en una base de prueba.
- Incluye triggers, rutinas y eventos cuando existan y sean accesibles.
- Maneja correctamente una contraseña con caracteres especiales.
- No expone credenciales en logs ni en la respuesta.
- No incluye archivos del cuaderno.

### 13.5 Pruebas de fallos

- Herramienta de exportación ausente.
- Credenciales inválidas.
- Permisos insuficientes de MySQL/MariaDB.
- Espacio temporal insuficiente.
- Tiempo de ejecución agotado.
- Fallo de compresión.
- Archivo vacío o corrupto.
- Desconexión del navegador.
- Proceso duplicado.

En todos los casos, confirmar que se liberan los bloqueos, se limpian los temporales propios y no se actualiza la fecha del último respaldo exitoso.

### 13.6 Prueba de restauración fuera de producción

Aunque la restauración no forma parte del módulo, antes de aprobarlo deberá tomarse al menos un respaldo generado por esta funcionalidad y restaurarlo manualmente en una base de datos aislada de prueba. Luego se verificará:

- Que el archivo se descomprime.
- Que el SQL se importa sin errores críticos.
- Que existen las tablas esperadas.
- Que una muestra de registros coincide con la base de origen.
- Que triggers, rutinas y eventos requeridos estén presentes.

Esta prueba no deberá ejecutarse contra la base de datos de producción.

## 14. Criterios de aceptación

1. Solo un usuario autorizado puede entrar al módulo y generar un respaldo.
2. El administrador puede configurar un intervalo válido de N días.
3. El sistema calcula correctamente el estado a partir del último respaldo exitoso.
4. El recordatorio aparece cuando corresponde y no bloquea el sistema.
5. Al pulsar el botón y confirmar, se genera y descarga un archivo `.sql.gz` válido.
6. El respaldo contiene la estructura y los datos de la base de datos configurada.
7. Los adjuntos y archivos físicos del cuaderno no se incluyen.
8. El archivo temporal no queda disponible públicamente ni permanece almacenado después del flujo normal.
9. Las credenciales no aparecen en la interfaz, historial ni logs.
10. Un fallo no cambia la fecha del último respaldo exitoso.
11. No pueden ejecutarse dos respaldos simultáneos para la misma base de datos.
12. El historial conserva metadatos útiles sin guardar el contenido del respaldo.
13. Un respaldo generado se restaura satisfactoriamente en un entorno aislado de prueba.
14. La implementación no modifica ni elimina datos existentes y no cambia versiones o dependencias fuera del alcance.

## 15. Entregables esperados

- Migraciones aditivas necesarias.
- Modelos y relaciones requeridos.
- Servicio seguro de generación y compresión.
- Pantalla administrativa y componentes visuales.
- Rutas y acciones protegidas.
- Integración del recordatorio con el dashboard o navegación existente.
- Auditoría e historial.
- Pruebas automatizadas y documentación de la prueba manual de restauración.
- Nota de despliegue con dependencias del sistema operativo, permisos requeridos y valores de configuración operativa.

## 16. Consideraciones para una fase futura

Sin implementarlas en esta entrega, el diseño deberá permitir incorporar posteriormente:

- Ejecución automática mediante Laravel Scheduler o script del servidor.
- Política de retención.
- Respaldo cifrado.
- Almacenamiento externo.
- Respaldo de archivos del cuaderno.
- Verificación automática periódica.
- Notificaciones por correo u otros canales.
- Procedimiento administrativo de restauración con controles adicionales.

Estas funciones deberán desarrollarse mediante requisitos y controles de seguridad específicos; no deben quedar parcialmente activadas en la primera versión.

