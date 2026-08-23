# SDD — Sistema de seguridad, acceso y monitoreo

## 1. Propósito

Construir una capa de seguridad para la aplicación de **Servicios Recurrentes**, basada en Laravel 12, Livewire 4 y Fail2ban. El sistema debe prevenir ataques de fuerza bruta, dejar trazabilidad de los eventos sensibles, alertar al administrador y proteger especialmente las cuentas administrativas.

La seguridad se divide en dos niveles:

1. **Aplicación Laravel:** detecta intentos, limita solicitudes, bloquea temporalmente, registra eventos y notifica.
2. **Servidor:** Fail2ban analiza un log exclusivo y bloquea la IP en el firewall cuando la actividad supera el umbral definido.

No se debe sustituir un nivel por el otro. Laravel conoce el contexto funcional (usuario, ruta y acción); Fail2ban corta el acceso desde la red.

## 2. Alcance inicial

El desarrollo inicial incluye estos módulos y componentes:

1. Registro centralizado de eventos de seguridad.
2. Protección configurable del inicio de sesión.
3. Bloqueo temporal por IP dentro de Laravel.
4. Integración de eventos con Fail2ban y firewall.
5. Módulo administrador para consultar y desbloquear bloqueos creados por Laravel.
6. Notificaciones de seguridad.
7. MFA/2FA TOTP obligatorio para administradores.
8. Gestión de sesiones y cierre remoto.
9. Auditoría de acciones sensibles.
10. Configuración de políticas de seguridad.

Quedan fuera de esta primera fase: CAPTCHA, reconocimiento de dispositivos, bloqueo geográfico y análisis de reputación de IP. Se podrán agregar más adelante sin cambiar la base del módulo.

## 3. Principios y reglas generales

- La API key de OpenAI y cualquier secreto se guardan únicamente en el servidor, en variables de entorno. Nunca se exponen en Blade, Livewire, JavaScript, logs ni repositorio.
- Las contraseñas nunca se guardan ni se escriben en logs.
- Los secretos TOTP se almacenan cifrados con `Crypt` de Laravel. Los códigos de recuperación se almacenan como hashes.
- Los eventos de seguridad no deben incluir datos financieros, tokens, contraseñas, contenido de formularios ni correos completos. Para el login se almacenará `user_id`, cuando exista, o un hash irreversible del correo digitado.
- Las IP se almacenan para poder bloquear, investigar y liberar. El acceso a estas IP se limita a administradores.
- Toda acción de bloqueo, desbloqueo, activación o desactivación de MFA debe quedar auditada.
- El panel web **solo** administra bloqueos creados por Laravel. No ejecuta `unban`, no administra jails y no envía comandos a Fail2ban.
- El sistema debe funcionar con IPv4 e IPv6.
- Si el servidor usa Cloudflare, Nginx como proxy o balanceador, se debe configurar correctamente `TrustProxies`. `request()->ip()` debe devolver la IP real del visitante antes de activar bloqueos.
- Las respuestas de login no deben revelar si un correo existe. Ante una credencial inválida, usar el mismo mensaje genérico.

## 4. Arquitectura funcional

```text
Usuario o bot
    ↓
Ruta de login Laravel
    ↓
Middleware de IP bloqueada + RateLimiter
    ↓
Autenticación
    ├── Correcta → evento, sesión segura y validación MFA
    └── Fallida → evento, contador, aviso y posible bloqueo
                                      ↓
                         storage/logs/security.log
                                      ↓
                                Fail2ban
                                      ↓
                              FirewallD / firewall
```

### 4.1 Estados de la protección

| Estado | Descripción |
|---|---|
| Normal | La IP puede usar la ruta de inicio de sesión. |
| Limitada | Ha acumulado intentos, pero aún puede reintentar según el límite corto. |
| Bloqueada por Laravel | La IP no puede entrar a `login`, recuperación de contraseña ni rutas configuradas hasta que termine el bloqueo. |
| Bloqueada por Fail2ban | La IP está bloqueada a nivel de firewall y no llega a Laravel. Se libera exclusivamente desde consola. |
| En lista permitida | IP excepcional autorizada por un administrador. Debe usarse con mucha cautela. |

## 5. Módulo: eventos de seguridad

### 5.1 Objetivo

Tener un historial legible de todo evento importante de autenticación, acceso, cambios administrativos y bloqueos. Debe servir para revisar incidentes y alimentar notificaciones.

### 5.2 Tabla `security_events`

| Campo | Tipo / regla |
|---|---|
| `id` | Identificador. |
| `event_type` | Código del evento. Indexado. |
| `severity` | `info`, `warning`, `critical`. |
| `user_id` | Nullable, relación a usuario. |
| `ip_address` | IPv4/IPv6. Indexado. |
| `route` | Ruta afectada, nullable. |
| `method` | Método HTTP, nullable. |
| `user_agent` | Texto limitado. |
| `metadata` | JSON con datos no sensibles. |
| `occurred_at` | Fecha/hora del evento. Indexado. |
| `created_at`, `updated_at` | Laravel. |

### 5.3 Eventos mínimos

| Código | Severidad | Cuándo ocurre |
|---|---|---|
| `LOGIN_FAILED` | warning | Credenciales incorrectas. |
| `LOGIN_RATE_LIMITED` | warning | Se excede el límite corto de intentos. |
| `LOGIN_IP_BLOCKED` | critical | Laravel bloquea una IP. |
| `LOGIN_IP_UNBLOCKED` | warning | Un administrador libera un bloqueo creado por Laravel. |
| `LOGIN_SUCCESS` | info | Inicio de sesión correcto. |
| `MFA_CHALLENGE_FAILED` | warning | Código TOTP incorrecto. |
| `MFA_ENABLED` / `MFA_DISABLED` | warning | Se activa o desactiva MFA. |
| `SESSION_REVOKED` | warning | Se cierra una sesión de forma remota. |
| `PASSWORD_CHANGED` | warning | Cambio o recuperación de contraseña. |
| `USER_ROLE_CHANGED` | critical | Cambio de rol o permiso. |
| `SECURITY_SETTING_CHANGED` | critical | Cambio de política de seguridad. |
| `FAIL2BAN_BAN_DETECTED` | critical | Se registra una IP bloqueada por Fail2ban, si la sincronización está habilitada. |

### 5.4 Log de Fail2ban

Laravel debe crear el canal `security` en `config/logging.php`, con salida separada:

```text
storage/logs/security.log
```

Formato obligatorio, una sola línea por evento:

```text
SECURITY_LOGIN_FAILED ip=190.80.10.25 route=login user_hash=sha256:... 
SECURITY_LOGIN_IP_BLOCKED ip=190.80.10.25 source=laravel duration=900
```

El log para Fail2ban debe conservar el nombre de evento y `ip=<IP>` de forma estable. No modificar ese formato sin actualizar y probar el filtro de Fail2ban.

## 6. Módulo: protección de inicio de sesión

### 6.1 Reglas de bloqueo

Implementar dos contadores independientes:

1. **IP + usuario/correo hasheado:** reduce ataques dirigidos a una cuenta.
2. **IP global:** detecta bots que prueban muchos usuarios desde la misma IP.

No bloquear permanentemente una cuenta por intentos fallidos. Eso permitiría que un tercero impida el acceso al usuario. El bloqueo principal será temporal por IP y ruta.

### 6.2 Configuración inicial sugerida

| Parámetro | Valor inicial | Configurable |
|---|---:|:---:|
| Intentos por IP + cuenta | 5 | Sí |
| Ventana de evaluación | 10 minutos | Sí |
| Bloqueo Laravel | 15 minutos | Sí |
| Intentos globales por IP | 10 | Sí |
| Ventana para Fail2ban | 15 minutos | Sí |
| Bloqueo inicial Fail2ban | 6 horas | Sí, en servidor |
| Bloqueo por reincidencia | 24 horas | Sí, en servidor |
| Código MFA incorrecto | 5 en 10 minutos | Sí |

Laravel utilizará `RateLimiter` con cache persistente (Redis recomendado; base de datos como alternativa). La clave debe separar claramente IP, cuenta y tipo de acción.

### 6.3 Middleware `EnsureIpIsNotSecurityBlocked`

Crear middleware para revisar si existe un bloqueo activo de la IP. Debe aplicarse como mínimo a:

- `GET/POST /login`
- recuperación y restablecimiento de contraseña
- verificación de MFA
- API de autenticación, si existe

Si la IP está bloqueada por Laravel:

- No ejecutar autenticación.
- Responder `429 Too Many Requests` con `Retry-After`.
- Para páginas web, mostrar una pantalla o alerta clara con el tiempo restante.
- Registrar únicamente un evento adicional si no genera ruido excesivo; no crear miles de registros por una IP ya bloqueada.

### 6.4 Flujo de login

1. Validar la IP y el límite antes de consultar la autenticación.
2. Intentar login con mensaje genérico ante fallo.
3. Si falla, incrementar ambos contadores y crear `LOGIN_FAILED`.
4. Si alcanza el límite Laravel, crear un registro en `security_ip_blocks`, emitir `LOGIN_IP_BLOCKED`, notificar y responder con `429`.
5. Si es correcto, limpiar los contadores de la combinación correspondiente, regenerar la sesión y crear `LOGIN_SUCCESS`.
6. Si el usuario requiere MFA, no completar acceso al panel hasta validar el código TOTP.

## 7. Módulo: bloqueos de IP

### 7.1 Tabla `security_ip_blocks`

| Campo | Descripción |
|---|---|
| `id` | Identificador. |
| `ip_address` | IP bloqueada. Indexada. |
| `scope` | `login`, `password_reset`, `mfa`, `all_auth`. |
| `source` | `laravel` o `manual`. No representa ni controla bans de Fail2ban en esta fase. |
| `reason` | Motivo visible al administrador. |
| `blocked_at` | Inicio. |
| `expires_at` | Fin; nullable solo para bloqueo manual explícito. |
| `released_at` | Fecha de liberación manual, nullable. |
| `released_by` | Usuario administrador que liberó, nullable. |
| `metadata` | Datos complementarios sin secretos. |

### 7.2 Reglas

- Laravel bloqueará solamente las rutas de autenticación; no debe bloquear todo el sitio por defecto.
- Un administrador podrá liberar desde el Centro de seguridad únicamente una IP cuyo bloqueo haya sido creado por Laravel y exista en `security_ip_blocks`.
- El botón **Desbloquear** elimina o marca como liberado solamente el registro y la restricción interna de Laravel. No puede afectar el firewall.
- Esta primera fase no consulta ni sincroniza las IPs bloqueadas por Fail2ban. Por tanto, una IP baneada por Fail2ban no debe aparecer como desbloqueable desde la aplicación.
- No ejecutar comandos `sudo` ni `fail2ban-client` directamente desde una solicitud web.
- Un ban de Fail2ban se libera por consola del servidor, por ejemplo: `fail2ban-client set laravel-login unbanip <IP>`. El nombre del jail debe coincidir con la configuración real del servidor.
- Una integración futura con Fail2ban requerirá una especificación y revisión de seguridad separadas. Nunca otorgar permisos root a PHP/Apache.

## 8. Integración Fail2ban

### 8.1 Responsabilidad

Fail2ban leerá `storage/logs/security.log` y bloqueará IPs que excedan el umbral alto. Laravel no debe intentar administrar el firewall directamente.

El alcance de esta fase termina en el registro que Laravel escribe para Fail2ban. La aplicación no consulta el estado de sus jails, no crea bans y no libera IPs baneadas por Fail2ban.

### 8.2 Filtro esperado

Archivo sugerido:

```text
/etc/fail2ban/filter.d/laravel-login.conf
```

```ini
[Definition]
failregex = ^.*SECURITY_LOGIN_FAILED ip=<HOST>.*$
            ^.*SECURITY_MFA_CHALLENGE_FAILED ip=<HOST>.*$
ignoreregex =
```

### 8.3 Jail sugerido

Archivo sugerido:

```text
/etc/fail2ban/jail.d/laravel-login.local
```

```ini
[laravel-login]
enabled  = true
filter   = laravel-login
logpath  = /ruta/absoluta/proyecto/storage/logs/security.log
maxretry = 10
findtime = 15m
bantime  = 6h
action   = firewallcmd-rich-rules
```

Antes de activar el jail en producción se debe probar el patrón contra eventos reales con `fail2ban-regex`. También se debe confirmar que el usuario de Fail2ban puede leer el archivo y que `logrotate` no rompe el seguimiento.

## 9. Módulo: Centro de seguridad

### 9.1 Acceso

- Ruta sugerida: `/security`.
- Solo rol administrador.
- Cada consulta, liberación o cambio relevante debe quedar auditado.

### 9.2 Pantalla principal

Mostrar tarjetas:

- Intentos fallidos en las últimas 24 horas.
- IPs bloqueadas actualmente por Laravel.
- Alertas críticas sin revisar.
- Último login administrativo.
- Usuarios administradores sin MFA: indicador crítico.

Mostrar una tabla de eventos con filtros por fecha, tipo, severidad, usuario e IP. Debe permitir abrir el detalle, sin mostrar secretos.

### 9.3 Administración de bloqueos Laravel

Tabla con IP, motivo, alcance, fecha de bloqueo, expiración, estado y acciones. Esta tabla se alimenta exclusivamente de `security_ip_blocks`.

Acciones:

- Ver eventos relacionados.
- Liberar bloqueo Laravel, solicitando confirmación y motivo. La acción actualiza `released_at` y `released_by`, elimina la restricción interna y genera `LOGIN_IP_UNBLOCKED`.
- Crear o eliminar lista permitida, solo si se habilita esta función.

La pantalla debe mostrar esta aclaración permanente:

> Este módulo desbloquea únicamente IPs bloqueadas por la aplicación. Las IPs bloqueadas por Fail2ban deben liberarse desde la consola del servidor.

No debe existir botón, endpoint, comando Artisan expuesto por web ni integración `sudoers` para liberar bans de Fail2ban en esta fase.

### 9.4 Panel de auditoría

Mostrar cambios sobre:

- Usuarios, roles y permisos.
- Configuración de seguridad.
- Contraseñas y MFA.
- Clientes, servicios, cobros, compromisos y ajustes financieros.

No duplicar innecesariamente el historial existente de la aplicación; integrar el sistema de actividad actual, si ya existe, y mostrarlo desde el Centro de seguridad.

## 10. Módulo: notificaciones de seguridad

### 10.1 Destinatarios

- Administradores designados en configuración.
- El usuario afectado, solo si el evento se relaciona con una cuenta existente y la notificación no facilita enumerar cuentas.

### 10.2 Eventos que notifican

| Evento | Notificación |
|---|---|
| IP bloqueada por Laravel | Interna inmediata a administradores. |
| Ban de Fail2ban detectado | Interna + correo a administradores. |
| Login administrativo desde IP nueva | Interna y correo al administrador afectado. |
| MFA activado, desactivado o fallido repetidamente | Interna; correo si fue desactivado. |
| Cambio de rol administrador | Interna y correo a administradores. |
| Muchos errores en un período corto | Resumen, no una alerta por cada evento. |

Las alertas internas deben utilizar el sistema de notificaciones existente y permitir marcar como leídas. Para correo, usar cola; un fallo de correo nunca puede impedir el login ni el bloqueo.

## 11. Módulo: MFA con Google Authenticator u otra aplicación TOTP

### 11.1 Alcance

MFA será obligatorio para administradores y opcional inicialmente para otros usuarios. Debe ser compatible con Google Authenticator, Authy y Microsoft Authenticator; no requiere API de Google ni cuenta Google.

### 11.2 Datos de usuario

Agregar a `users`:

- `two_factor_secret` (texto cifrado, nullable).
- `two_factor_confirmed_at` (fecha, nullable).
- `two_factor_recovery_codes` (JSON de hashes, nullable).
- `two_factor_last_used_at` (fecha, nullable).

### 11.3 Flujo de activación

1. Usuario autenticado abre **Mi perfil → Seguridad**.
2. Laravel genera un secreto TOTP temporal y un QR.
3. El usuario escanea el QR con su autenticador.
4. Ingresa el primer código de seis dígitos.
5. Solo si el código es válido, se persiste el secreto cifrado, se marca la confirmación y se generan 8–10 códigos de recuperación.
6. Los códigos se muestran una única vez; el usuario debe guardarlos.

### 11.4 Flujo de login con MFA

1. Correo y contraseña correctos.
2. Se guarda una sesión parcial, sin acceso a rutas protegidas.
3. Se muestra la pantalla de código TOTP o código de recuperación.
4. Al validar, se regenera la sesión y se concede acceso.
5. Tras varios códigos incorrectos, aplicar el límite de MFA y registrar el evento.

### 11.5 Recuperación segura

- Usar un código de recuperación invalida únicamente ese código.
- La pérdida del autenticador debe resolverse con un proceso administrativo: validar identidad, registrar motivo, regenerar MFA y cerrar sesiones activas.
- No permitir desactivar MFA con solo estar autenticado si la cuenta es administradora: requerir contraseña actual y un código TOTP válido.

## 12. Módulo: sesiones seguras

### 12.1 Reglas

- Regenerar el identificador de sesión después de login, cambio de contraseña, verificación MFA y elevación de privilegios.
- Cerrar sesiones al cambiar contraseña o restablecerla.
- Configurar expiración por inactividad, inicialmente 45 minutos y configurable.
- Usar cookies `Secure`, `HttpOnly` y `SameSite=Lax` como mínimo, siempre bajo HTTPS.

### 12.2 Gestión de sesiones

En **Mi perfil → Sesiones**, cada usuario podrá ver sesiones activas con fecha, IP aproximada, navegador y última actividad.

Acciones:

- Cerrar una sesión específica.
- Cerrar todas las demás sesiones.

Para administradores, permitir al Centro de seguridad cerrar las sesiones de un usuario ante incidente. Registrar `SESSION_REVOKED`.

## 13. Módulo: configuración de seguridad

Crear sección `/security/settings`, exclusivamente para administradores.

| Grupo | Ajustes |
|---|---|
| Login | Intentos, ventana y duración de bloqueo Laravel. |
| MFA | Obligatorio para administradores, obligatorio para todos, plazo de adopción. |
| Sesiones | Tiempo por inactividad y número máximo de sesiones. |
| Notificaciones | Administradores destinatarios y eventos notificados. |
| Retención | Días de conservación de eventos y auditoría. |
| Excepciones | Lista permitida de IP, con razón, responsable y fecha de expiración obligatoria. |

Los cambios deben validar rangos seguros, requerir confirmación y generar `SECURITY_SETTING_CHANGED`.

## 14. Seguridad complementaria obligatoria

Estas medidas deben verificarse al implementar el módulo:

- HTTPS obligatorio y redirección HTTP → HTTPS.
- Protección CSRF en formularios web.
- Autorización en backend mediante Policies/Gates; nunca depender únicamente de ocultar botones.
- Validación estricta de archivos: extensión, MIME real, tamaño, nombre aleatorio y ubicación no ejecutable.
- Encabezados de seguridad: CSP gradual, `X-Frame-Options` o `frame-ancestors`, `X-Content-Type-Options`, `Referrer-Policy` y HSTS después de confirmar HTTPS estable.
- Backups probados, cifrados y con acceso restringido.
- Dependencias PHP y JavaScript revisadas y actualizadas periódicamente.
- Logs con permisos restrictivos y rotación configurada.

## 15. Modelo de permisos

| Acción | Usuario normal | Administrador |
|---|:---:|:---:|
| Activar su MFA | Sí | Sí |
| Ver sus sesiones | Sí | Sí |
| Cerrar sus sesiones | Sí | Sí |
| Ver eventos generales | No | Sí |
| Ver y liberar IP bloqueada | No | Sí |
| Cambiar políticas de seguridad | No | Sí |
| Restablecer MFA de otro usuario | No | Sí, con auditoría |
| Cerrar sesiones de otro usuario | No | Sí, con auditoría |

## 16. Estructura técnica sugerida

```text
app/
  Actions/Security/
  Events/Security/
  Listeners/Security/
  Models/SecurityEvent.php
  Models/SecurityIpBlock.php
  Models/SecuritySetting.php
  Middleware/EnsureIpIsNotSecurityBlocked.php
  Notifications/Security/
  Services/Security/LoginProtectionService.php
  Services/Security/SecurityEventLogger.php
  Services/Security/TwoFactorService.php
  Livewire/Security/

database/migrations/
  create_security_events_table.php
  create_security_ip_blocks_table.php
  create_security_settings_table.php
  add_two_factor_columns_to_users_table.php

resources/views/livewire/security/
  dashboard.blade.php
  events-index.blade.php
  ip-blocks-index.blade.php
  settings.blade.php
  two-factor-setup.blade.php
  sessions-index.blade.php
```

Usar migraciones aditivas. No modificar ni eliminar estructuras existentes sin necesidad.

## 17. Pruebas de aceptación

### Protección de login

- Cinco credenciales inválidas dentro de la ventana bloquean la IP en Laravel.
- Una IP bloqueada recibe `429` y no llega a autenticar.
- El contador se limpia tras login correcto.
- Un atacante no puede saber si un correo existe por el mensaje de error.
- El bloqueo aplica a IPv4 e IPv6.

### Fail2ban

- Un evento `SECURITY_LOGIN_FAILED` es detectado por `fail2ban-regex`.
- Diez eventos desde la misma IP activan el jail en ambiente de prueba.
- La IP bloqueada no llega a Laravel.
- La rotación del log no impide que Fail2ban siga analizando eventos.
- No existe botón ni endpoint web que ejecute `unban` en Fail2ban.

### MFA

- Un administrador sin MFA no puede completar login cuando MFA obligatorio esté activo.
- El QR se confirma con un código válido antes de persistir MFA.
- Código TOTP incorrecto no permite acceso y respeta el rate limit.
- Un código de recuperación sirve una sola vez.
- Desactivar MFA requiere contraseña actual y código TOTP.

### Centro de seguridad

- Un usuario normal no puede acceder a `/security`.
- Un administrador puede filtrar eventos y liberar un bloqueo Laravel.
- Una IP que no existe en `security_ip_blocks` no aparece como desbloqueable desde la aplicación.
- Las acciones administrativas generan evento y auditoría.

## 18. Orden recomendado de implementación

1. Crear tablas, modelos, canal `security` y servicio de registro.
2. Implementar RateLimiter, middleware y bloqueos Laravel.
3. Crear y probar filtro/jail de Fail2ban en servidor de prueba.
4. Agregar notificaciones internas y por correo para eventos críticos.
5. Construir Centro de seguridad: tablero, eventos y bloqueos.
6. Implementar MFA TOTP para administradores.
7. Implementar gestión de sesiones y auditoría ampliada.
8. Agregar encabezados, protección de archivos, retención y revisión final.

## 19. Criterio de terminado

El módulo estará listo cuando la aplicación pueda detectar y explicar intentos fallidos, bloquear temporalmente una IP desde Laravel, escalar ataques repetidos a Fail2ban, notificar al administrador, exigir MFA a administradores y ofrecer un historial auditable de los eventos sin exponer información sensible.
