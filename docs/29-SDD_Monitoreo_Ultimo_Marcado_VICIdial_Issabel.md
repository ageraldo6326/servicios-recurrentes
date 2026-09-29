# SDD — Monitoreo del último marcado saliente en VICIdial e Issabel

**Proyecto:** PersonalSyS  
**Versión:** 1.2
**Fecha:** 29 de septiembre de 2026  
**Estado:** Especificación para implementación

## 1. Objetivo

Crear en PersonalSyS una API de recepción y un panel que permitan identificar cuándo fue la última vez que cada servidor de un cliente realizó **un intento de llamada saliente**, independientemente de que la llamada haya sido contestada. El objetivo comercial y operativo es detectar con anticipación que un cliente ha dejado de utilizar el servicio.

El panel debe distinguir entre **falta de actividad de llamadas** y **falta de reportes del servidor**. Los scripts que consultarán VICIdial e Issabel se implementarán en una etapa posterior; este SDD define el contrato que deberán cumplir.

## 2. Alcance

### Incluido

- Identificación de un servidor monitoreado y de su cliente mediante la IP registrada en PersonalSyS.
- Endpoint HTTPS para recibir la IP y la fecha/hora del último intento saliente.
- Autenticación inicial mediante una única clave compartida, almacenada en el `.env` de PersonalSyS y en la configuración local de los futuros scripts.
- Registro de la última fecha de marcado y de la última fecha de reporte, por separado.
- Panel de consulta, búsqueda, filtros, indicadores de estado y umbrales de alerta configurables.
- Alerta visual cuando el tiempo desde el último marcado saliente supera 5 días (120 horas).
- Historial básico de recepciones para diagnosticar fallos y verificar la continuidad de los reportes.
- Preparación de la integración para servidores VICIdial e Issabel PBX.

### Fuera de esta versión

- Desarrollo e instalación de los scripts que extraen información en cada servidor.
- Consultas remotas desde PersonalSyS a las bases de datos o archivos de los PBX.
- Envío de números telefónicos, grabaciones, CDR completos o datos personales de las llamadas.
- Alertas por correo, WhatsApp o SMS; la primera versión presenta alertas dentro del panel.
- Facturación automática, suspensión de servicios o cambio automático del estado contractual del cliente.

## 3. Definiciones funcionales

**Último marcado saliente:** momento en que se inició el intento más reciente de llamada desde el servidor hacia un destino externo. Cuenta aunque el resultado sea no contestada, ocupada o fallida, siempre que el origen de datos demuestre que hubo un intento real de marcado. No cuentan llamadas entrantes, llamadas entre extensiones, pruebas internas ni la mera preparación de una llamada que nunca se intentó cursar. La clasificación concreta la hará el futuro script de cada plataforma.

**Último reporte:** momento en que la API recibió y aceptó el último POST del servidor. Se calcula en PersonalSyS con su reloj; no lo proporciona el servidor remoto.

**Servidor sin llamadas nuevas:** el script reportó correctamente, pero la fecha del último marcado sigue siendo la misma. Esto es un reporte válido.

**Servidor sin reportes:** la API no recibió reportes dentro del plazo configurado, por lo que no es posible afirmar que la falta de nuevas llamadas represente inactividad real.

**Sin historial de marcado:** un servidor válido informa `null` porque todavía no encuentra ningún intento saliente. El panel lo presenta explícitamente como “Sin llamadas registradas”.

## 4. Flujo general

1. Un administrador registra o confirma en PersonalSyS la IP del servidor, el cliente relacionado, el tipo de plataforma y el umbral de inactividad.
2. Un script local, cuya implementación queda para la siguiente etapa, obtiene la fecha del último intento saliente y realiza un POST autenticado a la API. Se ejecutará al menos una vez al día, incluso sin llamadas nuevas.
3. La API valida la clave, el formato, la IP registrada y la fecha. Registra el momento de recepción y actualiza la última fecha de marcado únicamente si el dato recibido es más reciente.
4. El panel muestra por servidor el cliente, la plataforma, el último marcado, el tiempo transcurrido, el último reporte y el estado correspondiente.

La API recibe **exactamente dos campos de negocio**: `server_ip` y `last_outbound_at`. La clave viaja en la cabecera HTTP y la hora de recepción la asigna el sistema.

## 5. Contrato de la API

### 5.1 Endpoint

`POST /api/v1/server-call-activity`

Cabeceras:

```http
Content-Type: application/json
Authorization: Bearer <CLAVE_COMPARTIDA>
```

Cuerpo de ejemplo:

```json
{
  "server_ip": "203.0.113.10",
  "last_outbound_at": "2026-09-29T14:30:00Z"
}
```

Cuando todavía no exista ninguna llamada saliente:

```json
{
  "server_ip": "203.0.113.10",
  "last_outbound_at": null
}
```

`server_ip` es obligatorio y debe ser una dirección IP válida que identifique de forma inequívoca un servidor registrado. `last_outbound_at` es obligatorio, admite `null` y, si tiene valor, debe ser una fecha ISO 8601 con zona horaria explícita (`Z` o desfase). Los futuros scripts convertirán la hora local del PBX a este formato. PersonalSyS normalizará y almacenará las fechas en UTC; la interfaz podrá mostrarlas en la zona horaria configurada del usuario.

Respuesta aceptada, incluso si la fecha del marcado no cambió:

```json
{
  "ok": true,
  "server_id": 42,
  "last_outbound_at": "2026-09-29T14:30:00Z",
  "last_reported_at": "2026-09-29T15:02:11Z",
  "updated_last_outbound": false
}
```

La respuesta devuelve la **fecha efectiva guardada**, que puede ser posterior a la reportada si el POST llegó atrasado. No devolverá información del cliente que no sea necesaria para el script.

### 5.2 Reglas de recepción

1. Rechazar peticiones sin clave o con clave incorrecta (`401`). Comparar la clave de manera segura. Exigir HTTPS en producción.
2. Validar los dos campos y rechazar fechas sin zona horaria, mal formadas o irrazonablemente futuras. Como regla inicial, admitir hasta **5 minutos** por posible diferencia de relojes; un exceso devuelve `422`.
3. Buscar exactamente un servidor activo por `server_ip` en el inventario de PersonalSyS. Una IP desconocida devuelve `404`; una asociación ambigua devuelve `409` y no actualiza ningún registro. No crear clientes ni servidores automáticamente a partir de un POST.
4. Registrar `last_reported_at` con la hora del servidor de PersonalSyS en cada reporte válido, aunque `last_outbound_at` sea igual o `null`.
5. Actualizar `last_outbound_at` solo si el valor recibido es mayor que el valor guardado. Un valor anterior o `null` no debe borrar ni retroceder una fecha existente.
6. Tratar reenvíos del mismo reporte como válidos e idempotentes respecto de la última fecha de marcado. Guardar un evento de recepción para diagnóstico, sin registrar una llamada por cada POST.
7. Registrar errores técnicos y rechazos con suficiente contexto operativo, sin almacenar la clave ni datos sensibles en los logs.
8. Aplicar un límite de peticiones razonable por origen para proteger el endpoint, teniendo presente los reintentos legítimos.

La IP del JSON es el identificador de búsqueda acordado. **No sirve como autenticación**: la autorización depende de la clave compartida. La IP de origen de la conexión puede guardarse como dato de diagnóstico, pero podría diferir por NAT o proxies.

### 5.3 Configuración de la clave

Usar una variable como `CALL_ACTIVITY_REPORT_KEY` en `.env`, consumida mediante la configuración de la aplicación (por ejemplo, `config/services.php` en Laravel). La clave no se guarda en el repositorio, no aparece en respuestas de la API ni en el panel, y se entrega a los scripts mediante una configuración local protegida. En esta versión todos los servidores comparten la misma clave; rotarla exige actualizar tanto PersonalSyS como cada servidor que reporta.

## 6. Datos y relación con PersonalSyS

Antes de la migración, revisar el modelo actual de clientes, servicios y servidores. **Reutilizar el inventario existente** en vez de duplicar clientes o crear una segunda ficha de servidor. La IP utilizada por el API debe referirse a la IP que el futuro script reportará, normalmente la IP de servicio registrada, aunque el POST salga a Internet por otra dirección.

### 6.1 Identificación del servidor

Cada servidor monitoreado debe tener:

| Campo lógico | Descripción |
| --- | --- |
| `server_id` | Identificador interno estable del servidor o servicio existente. |
| `client_id` | Cliente propietario del servicio, resuelto por la relación existente. |
| `server_ip` | IP declarada para el reporte; debe resolver a un solo servidor activo. |
| `platform` | `vicidial` o `issabel`, según el inventario. |
| `monitoring_enabled` | Activa o desactiva el monitoreo para ese servidor. |
| `inactivity_threshold_hours` | Horas sin marcado antes de advertir; valor por defecto configurable. |
| `report_delay_threshold_hours` | Horas sin POST antes de alertar por falta de reporte; valor por defecto configurable. |

Si el inventario admite una misma IP para varios servicios, la activación de este monitoreo exigirá resolver la ambigüedad en la interfaz administrativa. No se asignará un reporte a un cliente por suposición. Un cambio de IP en el inventario debe aplicarse a la identificación del monitoreo sin perder el historial asociado al `server_id`.

### 6.2 Tabla de estado por servidor

Crear una tabla relacionada uno a uno con el servidor existente, por ejemplo `server_call_activity`:

| Campo | Tipo conceptual | Regla |
| --- | --- | --- |
| `server_id` | FK única | Referencia al servidor del inventario. |
| `last_outbound_at` | datetime nullable | Fecha más reciente aceptada, en UTC. |
| `last_reported_at` | datetime nullable | Última recepción válida, en UTC. |
| `created_at`, `updated_at` | datetime | Auditoría normal de PersonalSyS. |

Los umbrales y la activación pueden ubicarse en la tabla existente de servidores o en una tabla de configuración asociada, según la estructura actual. Una fila ausente o fechas `null` representan un servidor aún no inicializado, no una llamada inexistente.

### 6.3 Historial de recepciones

Crear un historial acotado, por ejemplo `server_call_activity_reports`, con `server_id`, `received_at`, `reported_last_outbound_at`, `updated_last_outbound` y, opcionalmente, IP de origen y resultado. Conservar inicialmente **90 días**, ajustables por configuración. Este historial es de **reportes**, no un CDR ni un registro de cada llamada. Los POST rechazados pueden quedar solo en logs operativos protegidos.

## 7. Panel de monitoreo

Agregar una sección en PersonalSyS, por ejemplo **Servidores → Actividad de marcado**, respetando la autenticación y los permisos administrativos ya existentes.

### 7.1 Tabla principal

| Columna | Contenido |
| --- | --- |
| Cliente | Nombre obtenido de la relación actual entre servidor y cliente; enlace a la ficha si existe. |
| Servidor | Nombre o referencia, IP y acceso a su ficha. |
| Plataforma | VICIdial o Issabel. |
| Último marcado saliente | Fecha/hora local del intento; “Sin llamadas registradas” si corresponde. |
| Tiempo sin marcar | Contador destacado e independiente de la fecha: tiempo exacto transcurrido desde el último intento saliente. |
| Último reporte | Fecha/hora local y tiempo transcurrido; “Nunca” si no existe. |
| Estado | Etiqueta visible y explicación breve. |
| Umbral | Plazo de inactividad configurado para ese servidor. |

La tabla incluirá búsqueda por cliente, servidor o IP; filtros por plataforma y estado; orden por mayor tiempo sin llamadas, con opción de ordenar por último reporte; y acceso al historial reciente de reportes de cada servidor. Mostrar un resumen superior con cantidades por estado.

**Presentación del tiempo transcurrido:** “Tiempo sin marcar” debe ser una columna visible, no solo una nota pequeña debajo de la fecha. Mostrar, por ejemplo, `3 min 17 s`, `5 h 24 min` o `2 días 4 h`. Si no hay llamada registrada, mostrar `Sin llamadas` en lugar de calcular una duración. El valor se calcula en el servidor desde `last_outbound_at` (UTC) hasta la hora actual al cargar el panel o pulsar “Actualizar datos”; no avanza en vivo ni provoca consultas automáticas mientras la página permanece abierta. La fecha exacta seguirá visible para auditoría.

### 7.2 Estados y prioridad

| Condición | Estado visible | Interpretación |
| --- | --- | --- |
| Nunca se recibió un reporte | Sin datos | Monitoreo aún sin inicializar. |
| El último reporte excede su umbral | Sin reporte | Se desconoce si el servidor está activo; requiere revisión técnica. |
| Reporta dentro del plazo, pero nunca hubo marcado | Sin llamadas registradas | El script funciona; no se ha detectado uso. |
| Reporta dentro del plazo y el último marcado excede su umbral | Inactivo | Posible abandono o pausa del servicio. |
| Reporta dentro del plazo y el marcado está dentro del umbral | Activo | Uso reciente confirmado por un intento saliente. |

**Prioridad:** “Sin reporte” prevalece sobre “Inactivo”; en ambos casos se sigue mostrando la fecha histórica del último marcado. De este modo el panel no afirma que el cliente dejó de llamar cuando el sistema ya no tiene datos recientes. La alerta se calcula con el reloj de PersonalSyS y las fechas UTC almacenadas.

Además de los estados configurables por servidor, el panel muestra una alerta operacional destacada cuando han transcurrido **más de 5 días (120 horas)** desde `last_outbound_at`. El límite es estricto: exactamente 120 horas todavía no activa la alerta. Esta advertencia no cambia el estado contractual, no suspende el servicio y no reemplaza la alerta por falta de reportes.

Para la primera puesta en marcha se proponen **48 horas sin marcado** y **36 horas sin reporte** como valores predeterminados editables. Estos números son parámetros iniciales, no una regla fija para todos los clientes. Si se requiere una vigilancia más rápida que un día, los scripts tendrán que reportar con mayor frecuencia.

### 7.3 Configuración y permisos

- Permitir al administrador activar o desactivar el monitoreo por servidor y ajustar ambos umbrales.
- Mostrar el nombre del cliente desde los datos existentes; no ofrecer una asociación manual alternativa dentro de este módulo si ya existe una relación confiable.
- Respetar los permisos de acceso a clientes y servidores del sistema. El endpoint de recepción usa la clave compartida y no la sesión del panel.
- No mostrar la clave compartida en la interfaz.

## 8. Casos especiales

- **Reporte diario sin actividad:** la API actualiza `last_reported_at`, conserva `last_outbound_at` y el panel puede pasar a “Inactivo”.
- **Reporte atrasado:** se acepta para constancia de comunicación, pero no hace retroceder `last_outbound_at`.
- **Nueva llamada tras inactividad:** una fecha posterior cambia el estado a “Activo” cuando el siguiente POST válido llega a la API.
- **Fallo de red del servidor:** el futuro script deberá reintentar el POST; el panel pasará a “Sin reporte” si se supera el umbral.
- **IP desconocida o duplicada:** la API rechaza el POST y deja los estados existentes intactos; el administrador corrige el inventario.
- **Cambio de IP:** el historial permanece vinculado al `server_id`; se actualiza la IP de referencia y el script local.
- **Falta de zona horaria o relojes desajustados:** se rechaza el dato; se sincronizan los relojes y se corrige la configuración del script.
- **Servidor desactivado para monitoreo:** no figura en las alertas activas y sus POST se rechazan de forma controlada.

## 9. Orden de implementación

1. Revisar el esquema de PersonalSyS: entidad de servidores, relación con clientes, unicidad real de IP, permisos y estilo actual de API/panel.
2. Añadir configuración de la clave, migraciones y relación uno a uno para el estado de actividad.
3. Implementar autenticación, validación, recepción idempotente, actualización de fechas e historial acotado.
4. Construir el panel, filtros, estados, umbrales y acceso al historial.
5. Probar con POST manuales y varios servidores ficticios: uno activo, uno inactivo, uno sin reportes y uno sin llamadas.
6. En otra etapa, implementar y desplegar los scripts de extracción para VICIdial e Issabel según las versiones y la configuración real de cada cliente.

## 10. Criterios de aceptación

1. Un POST válido con IP registrada y clave correcta crea o actualiza la actividad del servidor y el panel muestra el cliente correcto.
2. Un intento saliente cuenta aunque la llamada no haya sido contestada; el endpoint no exige estado `ANSWERED` ni duración mínima.
3. Un POST válido con la misma fecha de marcado actualiza el último reporte sin crear falsamente una llamada nueva.
4. Un POST con `last_outbound_at: null` permite inicializar un servidor sin llamadas y nunca borra una fecha ya guardada.
5. Una fecha de marcado anterior no reemplaza una más reciente.
6. Una petición sin autorización, una IP desconocida o ambigua y una fecha inválida se rechazan sin modificar la actividad.
7. Los estados “Inactivo” y “Sin reporte” se calculan y muestran por separado; “Sin reporte” tiene prioridad cuando coinciden ambos umbrales.
8. El administrador puede ajustar los plazos por servidor y consultar los reportes recientes.
9. La clave permanece fuera del código fuente, la base de datos de actividad, las respuestas públicas y los logs.
10. La solución se integra con los clientes y servidores existentes sin duplicar sus registros.
11. Cada fila muestra de forma destacada “Tiempo sin marcar”, calculado desde `last_outbound_at` hasta la hora actual del servidor al cargar o actualizar manualmente el panel; el valor no avanza en vivo.
12. El panel muestra una alerta visual cuando el tiempo sin marcado supera 5 días (120 horas), sin activarla exactamente en el límite.

## 11. Decisiones para la siguiente etapa

Al desarrollar los scripts se validará en cada instalación la fuente exacta de los intentos salientes, el modo de excluir llamadas internas y entrantes, la zona horaria del PBX y la identificación del servidor en instalaciones VICIdial con varios nodos. Esas decisiones no cambian el contrato de recepción definido aquí, salvo que se descubra una IP compartida que impida identificar de forma unívoca el servidor; en tal caso se versionará el contrato y se añadirá un identificador estable.
