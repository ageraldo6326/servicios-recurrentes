# SDD — Comparación Temporal de IP de Proveedores vs. Servicios Contratados

## 1. Objetivo

Crear un módulo de consulta temporal que permita pegar información copiada desde un proveedor, extraer las direcciones IPv4 que contiene y compararlas contra las IP registradas en los **servicios contratados** de ese proveedor.

Su propósito es detectar rápidamente servicios o recursos de proveedor que podrían no estar registrados para su gestión comercial y financiera, evitando que un servicio recurrente quede sin cobrar o sin seguimiento.

La primera aplicación práctica será con proveedores como **Vultr** y **AMDY**, pero la extracción será genérica: funcionará con cualquier texto que contenga direcciones IPv4 válidas.

## 2. Alcance definitivo

### Incluido

- Área de texto para pegar información desde cualquier portal de proveedor.
- Selección del proveedor contra el cual se realizará la comparación.
- Extracción y validación de IPv4 del texto pegado.
- Eliminación de IP repetidas antes de comparar.
- Consulta de los servicios contratados asociados al proveedor seleccionado.
- Comparación bidireccional entre IP pegadas e IP de los servicios contratados.
- Visualización clara de coincidencias y diferencias.
- Ejecución temporal, sin persistir el texto ni el resultado en tablas permanentes.

### Excluido

- API de Vultr, AMDY u otros proveedores.
- Parsers específicos por proveedor, extracción de UUID, nombre de servidor, costo, fecha o estado.
- Guardar el paste, las IP extraídas, resultados, historial o auditoría de esta comparación.
- Crear, editar, desactivar, borrar o cobrar servicios contratados desde este módulo.
- Crear alertas permanentes, compromisos, pagos o movimientos financieros.
- Comparación de IPv6 en la primera versión.

## 3. Principios obligatorios

1. El módulo es exclusivamente de **lectura y comparación** respecto a los servicios contratados.
2. Los servicios contratados **nunca** se eliminan, modifican, desactivan ni se afectan por usar este módulo.
3. El texto pegado y las IP obtenidas no se guardarán en base de datos, caché, sesión persistente, archivos, bitácora ni historial.
4. La información solo existirá durante la ejecución actual de la pantalla. Al salir, recargar, cancelar o completar una nueva comparación, el texto anterior se descarta.
5. El módulo no enviará el paste a IA, APIs externas ni servicios de terceros.
6. El sistema deberá validar las IP; no bastará con una expresión regular que acepte direcciones inválidas.

## 4. Flujo de usuario

1. El usuario abre la pantalla **Comparar IP de proveedor**.
2. Selecciona un proveedor de la lista de proveedores/beneficiarios activos, por ejemplo `Vultr` o `AMDY`.
3. Pega el listado copiado desde el portal del proveedor en el área de texto.
4. Pulsa **Extraer y comparar**.
5. El sistema identifica, valida, normaliza y elimina duplicados de las IPv4 del texto.
6. El sistema consulta, en modo lectura, los servicios contratados correspondientes al proveedor seleccionado y obtiene sus IP registradas.
7. El sistema presenta el resumen y las tres listas de resultados:

| Resultado | Definición | Interpretación operativa |
| --- | --- | --- |
| Coincidentes | IP presente en el paste y en un servicio contratado del proveedor. | El recurso externo tiene un servicio interno asociado. |
| IP del proveedor sin servicio contratado | IP detectada en el paste, pero ausente de los servicios contratados del proveedor. | Requiere revisar si existe un servicio de proveedor que aún no se ha registrado/gestionado. |
| Servicio contratado sin IP en el paste | IP registrada en un servicio contratado del proveedor, pero no detectada en el paste. | Requiere revisar si el inventario pegado fue completo, la IP cambió o el servicio ya no existe en el proveedor. |

8. El usuario puede copiar, exportar visualmente o revisar las diferencias mientras permanezca en la pantalla.
9. Al navegar fuera, cerrar, recargar o iniciar otra comparación, se descartan el texto y resultados anteriores.

## 5. Interfaz propuesta

### 5.1. Formulario superior

| Campo | Tipo | Regla |
| --- | --- | --- |
| Proveedor | Selector obligatorio | Muestra proveedores/beneficiarios activos que tengan servicios contratados o estén autorizados para comparación. |
| Inventario completo | Casilla opcional, activada por defecto | Indica que el paste representa todas las IP actuales del proveedor. Solo afecta la advertencia de “servicio contratado sin IP en el paste”. |
| Información copiada del proveedor | Área de texto obligatoria | Permite pegar texto, HTML visible, Markdown o tablas copiadas. No se guarda. |
| Botón `Extraer y comparar` | Acción | Valida los campos, extrae IP y muestra resultados. |
| Botón `Limpiar` | Acción | Borra inmediatamente el contenido y todos los resultados actuales. |

Texto de ayuda visible:

> Pegue el listado copiado desde el proveedor. El sistema solo extraerá IPv4 válidas para compararlas; este contenido no se guardará.

### 5.2. Resumen de resultado

Después de ejecutar la comparación, mostrar cuatro tarjetas:

- **IP válidas detectadas en el paste**.
- **IP coincidentes**.
- **IP del proveedor sin servicio contratado**.
- **Servicios contratados sin IP detectada**.

Si el usuario desmarca `Inventario completo`, la última tarjeta se mostrará como dato informativo y deberá indicar: “El paste se marcó como parcial; esta diferencia no confirma que el recurso haya desaparecido.”

### 5.3. Tablas de detalle

#### A. IP coincidentes

| Columna | Contenido |
| --- | --- |
| IP | Dirección IPv4 normalizada. |
| Servicio contratado | Nombre/identificador del servicio interno. |
| Cliente | Cliente asociado, si el modelo actual lo dispone. |
| Estado del servicio | Estado mostrado en modo lectura. |

#### B. IP del proveedor sin servicio contratado

| Columna | Contenido |
| --- | --- |
| IP detectada | IP tomada del paste. |
| Resultado | `No registrada en servicios contratados`. |
| Recomendación | Revisar si debe crearse o asociarse un servicio contratado fuera de este módulo. |

#### C. Servicios contratados sin IP en el paste

| Columna | Contenido |
| --- | --- |
| IP registrada | IP que figura en el servicio contratado. |
| Servicio contratado | Nombre/identificador del servicio interno. |
| Cliente | Cliente asociado, si existe. |
| Resultado | `No detectada en el inventario pegado`. |

#### D. IP inválidas o descartadas

Mostrar solamente cuando existan. Debe incluir el valor candidato y el motivo: formato inválido, repetida, o no IPv4.

## 6. Extracción y normalización de IP

### 6.1. Método

El módulo utilizará dos pasos:

1. **Detección de candidatos:** buscar valores con apariencia de IPv4 dentro de cualquier texto, sin depender de columnas, tablas ni formato del proveedor.
2. **Validación real:** validar cada candidato con el validador estándar de PHP/Laravel para IPv4 (`FILTER_VALIDATE_IP` con `FILTER_FLAG_IPV4`, o equivalente). Solo las IP válidas pasarán a la comparación.

Una expresión regular de detección aceptable como punto de partida es:

```text
(?<![\d.])(?:\d{1,3}\.){3}\d{1,3}(?![\d.])
```

La expresión regular no sustituye la validación. Por ejemplo, `999.10.20.30` puede coincidir como candidato, pero debe descartarse por no ser una IPv4 válida.

### 6.2. Normalización

- Eliminar espacios antes y después de cada candidato.
- Conservar la notación IPv4 estándar devuelta por el validador.
- Comparar mediante igualdad exacta tras normalización.
- Eliminar IP repetidas usando el valor normalizado.
- Mantener un contador de ocurrencias para informar repeticiones, sin mostrar la misma IP varias veces en los resultados.
- No convertir nombres de dominio a IP ni hacer DNS lookup en esta versión.
- No interpretar ni comparar IPv6 en esta versión; si se detecta una, mostrarla como descartada con el motivo `IPv6 fuera de alcance inicial`.

## 7. Fuente interna de comparación

El proveedor seleccionado se utilizará para filtrar los servicios contratados existentes. El módulo debe consultar el modelo real que ya utiliza el sistema para los servicios contratados y conservar su estructura actual.

Actualmente el sistema relaciona compromisos con proveedores/beneficiarios mediante `beneficiaries` y `financial_commitments`. Durante la implementación se debe verificar cuál es el modelo real que representa el servicio contratado y cuál es su campo/relación de IP.

### Requisito de datos mínimo

Para que un servicio participe en la comparación debe tener:

| Dato | Uso |
| --- | --- |
| Proveedor/beneficiario | Permite filtrar solo los servicios del proveedor seleccionado. |
| IP del servicio | Permite comparar. Debe almacenarse como IPv4 normalizada. |
| Identificador y nombre del servicio | Permite explicar la coincidencia o diferencia. |
| Estado | Se muestra como referencia; no se modifica. |

Si el modelo actual de servicios contratados no posee un campo de IP, se debe agregar un campo nullable específico, por ejemplo `ip_address`, al modelo existente. No se debe crear una tabla paralela de servicios ni migrar datos financieros innecesariamente.

### Servicios incluidos en la consulta

Por defecto se compararán los servicios contratados **activos** del proveedor seleccionado. La interfaz puede ofrecer un filtro opcional para incluir inactivos, pero este filtro no debe alterar ningún registro.

Los servicios activos sin IP registrada se deben informar aparte como **Servicios sin IP interna**, porque no pueden compararse de forma automática.

## 8. Lógica de comparación

Definiciones en memoria:

```text
P = conjunto de IPv4 válidas extraídas del paste
S = conjunto de IPv4 válidas registradas en servicios contratados activos del proveedor seleccionado

Coincidentes = P ∩ S
Proveedor sin servicio = P − S
Servicio sin IP en paste = S − P
```

La consulta no debe asumir que el texto del proveedor está completo. Por eso:

- `Proveedor sin servicio` siempre es un hallazgo útil: una IP externa no aparece registrada internamente.
- `Servicio sin IP en paste` se muestra siempre, pero solo se marca como advertencia de revisión cuando `Inventario completo` está activado.
- Ningún hallazgo cambia por sí mismo la información contractual, financiera o de infraestructura.

### Pseudocódigo

```php
$pastedIps = $ipExtractor->extractValidUniqueIpv4($pastedText);

$contractedServices = $contractedServiceRepository
    ->activeForProvider($providerId)
    ->withValidIpAddress()
    ->get();

$serviceByIp = $contractedServices->keyBy('ip_address');
$serviceIps = $serviceByIp->keys();

$matchingIps = $pastedIps->intersect($serviceIps);
$providerOnlyIps = $pastedIps->diff($serviceIps);
$serviceOnlyIps = $serviceIps->diff($pastedIps);
```

El código final debe respetar las convenciones del proyecto y usar el nombre real de las relaciones/campos existentes.

## 9. Persistencia, ciclo de vida y privacidad

### 9.1. Política de no persistencia

No crear tablas como `imports`, `runs`, `results`, `resources`, `logs` o similares para esta funcionalidad.

El contenido pegado, las IP extraídas y los resultados deben procesarse en memoria dentro de la solicitud/componente actual. La base de datos solo se consulta para leer los servicios contratados y el proveedor seleccionado.

No se permite guardar estos datos en:

- Base de datos.
- Cache (Redis, archivo, base de datos u otro driver).
- Sesión persistente.
- Archivos temporales o permanentes.
- Auditoría, activity log, eventos que serialicen el payload o colas.

### 9.2. Limpieza de la interfaz

- Tras ejecutar la comparación, el área de texto se limpia del estado del componente tan pronto como se hayan calculado los resultados; la vista puede indicar “Comparación procesada; contenido original descartado”.
- El usuario podrá pulsar `Limpiar` para borrar inmediatamente los resultados visibles.
- Al recargar, cancelar, navegar a otra ruta o iniciar una nueva comparación, se eliminan resultados y datos temporales anteriores.
- Las excepciones no deben incluir el texto pegado completo ni fragmentos que contengan IP si ocurre un error de validación.

### 9.3. Seguridad

- Escapar el texto al mostrarlo; nunca renderizar HTML pegado.
- Limitar el tamaño de entrada, por ejemplo 1 MB, con mensaje claro si se supera.
- Restringir el acceso a usuarios autorizados para ver proveedores y servicios contratados.
- Evitar registrar cuerpos de solicitudes de esta ruta en logs de depuración o monitoreo.

## 10. Arquitectura Laravel/Livewire

### Componentes

- `ProviderIpComparison\CompareForm`: formulario, selector de proveedor, área de texto y botones.
- `ProviderIpComparison\ComparisonResults`: tarjetas y tablas del resultado temporal.

Puede implementarse como un solo componente Livewire si respeta la separación de responsabilidades y no introduce complejidad innecesaria.

### Servicios de dominio

- `Ipv4Extractor`: detecta candidatos, valida, normaliza y elimina duplicados.
- `ProviderContractedIpComparisonService`: consulta los servicios del proveedor y arma los tres conjuntos de resultado.

La lógica de extracción y comparación no debe colocarse directamente en la vista Blade, el componente Livewire o el controlador.

### Contrato de resultado en memoria

```php
[
    'provider' => [
        'id' => 1,
        'name' => 'Vultr',
    ],
    'summary' => [
        'valid_pasted_ips' => 31,
        'matching_ips' => 29,
        'provider_only_ips' => 1,
        'service_only_ips' => 1,
        'services_without_internal_ip' => 2,
    ],
    'matches' => [],
    'provider_only' => [],
    'service_only' => [],
    'invalid_or_discarded' => [],
]
```

Este arreglo solo vive durante la visualización actual; no debe serializarse hacia mecanismos persistentes.

## 11. Validaciones funcionales

| Caso | Resultado esperado |
| --- | --- |
| No se selecciona proveedor | Se bloquea la comparación y se muestra un mensaje de campo obligatorio. |
| Área de texto vacía | Se bloquea la comparación. |
| Paste sin IPv4 válida | Se informa que no se encontraron IP válidas; no se ejecuta una comparación vacía. |
| Texto Vultr con enlaces, `svg`, columnas y botones | Se extraen solo las IPv4 válidas. |
| Texto AMDY con filas simples | Se extraen solo las IPv4 válidas. |
| IP repetida cinco veces | Se muestra una sola vez en las listas; el resumen informa que tuvo repeticiones. |
| `999.10.20.30` | Se descarta como IPv4 inválida. |
| IP en paste e IP del servicio son iguales | Aparece en `Coincidentes`. |
| IP presente solo en el paste | Aparece en `IP del proveedor sin servicio contratado`. |
| IP presente solo en servicios internos | Aparece en `Servicios contratados sin IP en el paste`. |
| Servicio activo sin IP interna | Aparece en el aviso independiente de servicios sin IP. |
| Ejecutar, recargar y volver a la pantalla | No existe texto ni resultado anterior recuperable. |
| Ejecutar comparación | No se crea, actualiza, desactiva ni elimina ningún servicio contratado. |

## 12. Criterios de aceptación

La funcionalidad estará terminada cuando un usuario autorizado pueda seleccionar `Vultr`, pegar su inventario copiado y obtener, en una sola pantalla, las IP detectadas, las IP que coinciden con los servicios contratados y las diferencias en ambos sentidos.

Después de salir o recargar, no deberá quedar almacenado el contenido pegado ni el resultado de la comparación. En todos los casos, los servicios contratados deberán permanecer intactos.

## 13. Plan de implementación

1. Inspeccionar el modelo existente de servicios contratados para identificar su relación con proveedor/beneficiario y el campo de IP.
2. Agregar el campo nullable de IP al modelo existente únicamente si todavía no existe.
3. Implementar y probar `Ipv4Extractor` con textos copiados de Vultr y AMDY, IP válidas, inválidas, duplicadas y contenido con HTML/Markdown.
4. Implementar el servicio de consulta/comparación, con acceso solo de lectura a los servicios contratados.
5. Crear el componente Livewire, validaciones, resumen y tablas de diferencias.
6. Verificar explícitamente que no se creen tablas, registros de historial, archivos, caché ni logs con el paste o resultados.
7. Probar con un listado real de Vultr y un listado real de AMDY antes de habilitar la pantalla en producción.
