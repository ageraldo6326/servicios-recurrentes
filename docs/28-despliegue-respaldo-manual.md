# Despliegue — respaldo manual de base de datos

## Requisitos

- La conexión predeterminada de Laravel debe ser `mysql` o `mariadb`.
- El usuario de la base de datos necesita permisos de lectura sobre la estructura y los datos que se respaldarán. Para incluir rutinas, eventos y triggers, necesita los permisos correspondientes.
- El servidor web debe disponer de `mariadb-dump` o `mysqldump` en `PATH`. Si no lo está, configure `DATABASE_BACKUP_DUMP_BINARY` con una ruta absoluta al ejecutable.
- El proceso web debe poder crear archivos temporales únicamente en `storage/app/private/database-backups`.

## Variables de entorno

Las variables se documentan en `.env.example`:

- `DATABASE_BACKUP_DUMP_BINARY`: ruta opcional de `mariadb-dump` o `mysqldump`.
- `DATABASE_BACKUP_TIMEOUT_SECONDS`: máximo de ejecución; predeterminado `900`.
- `DATABASE_BACKUP_LOCK_SECONDS`: vencimiento del bloqueo contra ejecuciones simultáneas; predeterminado `1800`.
- `DATABASE_BACKUP_TEMP_MAX_AGE_SECONDS`: antigüedad máxima para limpiar temporales propios huérfanos; predeterminado `3600`.
- `DATABASE_BACKUP_MAX_UNCOMPRESSED_BYTES`: límite opcional del SQL antes de comprimir; `0` no impone límite.

No se debe colocar ninguna credencial nueva en estas variables. El módulo usa la conexión resuelta de Laravel y crea un archivo temporal de opciones con permisos restrictivos, que se elimina al finalizar.

## Autorización inicial

El módulo usa el permiso específico `can_manage_database_backups`. La migración conserva el acceso de las cuentas operativas existentes y las cuentas nuevas quedan sin acceso de forma predeterminada. Autorice únicamente a administradores de confianza antes de exponer la opción de menú.

## Verificación previa a producción

1. Confirme que el comando de exportación está disponible para el usuario del servidor web.
2. Genere un respaldo desde la pantalla **Respaldos** y compruebe que se descarga un archivo `.sql.gz`.
3. En una base aislada de prueba, descomprima e importe el archivo; no restaure nunca sobre la base de producción.
4. Verifique tablas y una muestra de registros, además de triggers, rutinas y eventos que la aplicación utilice.

El módulo no almacena respaldos permanentemente en el servidor, no incluye archivos adjuntos y no ofrece restauración desde la interfaz.
