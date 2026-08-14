# Módulo: Cuadernos y Notas

## Propósito del documento

Este documento contiene las instrucciones funcionales y técnicas para que Codex implemente, dentro del sistema existente, un módulo de notas inspirado en la experiencia de Microsoft OneNote.

No se pretende copiar la marca, los recursos gráficos ni la interfaz exacta de Microsoft. El objetivo es construir una solución propia con capacidades equivalentes de organización y toma de notas.

El nombre provisional del módulo será **Cuadernos**. Antes de implementarlo, verificar si el proyecto ya utiliza otro nombre para una funcionalidad similar.

---

# Objetivo

Permitir que cada usuario capture, organice, consulte y recupere información mediante una jerarquía sencilla:

```text
Cuaderno
└── Sección
    └── Página
        ├── Contenido
        ├── Archivos adjuntos
        ├── Imágenes
        └── Historial de versiones
```

La experiencia debe responder inmediatamente a estas preguntas:

> ¿En qué cuaderno está mi información?

> ¿Qué páginas modifiqué recientemente?

> ¿Cómo encuentro una nota aunque no recuerde dónde la guardé?

El módulo deberá priorizar:

**captura rápida → organización → guardado confiable → búsqueda → recuperación**.

---

# Instrucciones de ejecución para Codex

Antes de modificar código:

1. Leer completamente `AGENTS.md` y obedecer sus reglas.
2. Inspeccionar la arquitectura, versiones y convenciones reales del proyecto.
3. Revisar modelos, migraciones, rutas, componentes, sistema de autenticación, almacenamiento de archivos, traducciones y pruebas existentes.
4. Buscar implementaciones reutilizables antes de crear componentes o servicios nuevos.
5. Presentar un diagnóstico breve y un plan de implementación por fases.
6. Implementar el MVP completo, no solamente una maqueta visual.
7. Ejecutar pruebas y verificaciones relevantes al finalizar.
8. Informar archivos modificados, pruebas ejecutadas, decisiones tomadas y cualquier trabajo pendiente.

No asumir que un paquete o una tabla existen. Verificar primero.

Si una decisión importante no puede inferirse del código existente y cambia materialmente el comportamiento, detenerse y solicitar aclaración.

---

# Stack y convenciones esperadas

El proyecto utiliza como referencia:

- Laravel 12.
- PHP 8.2 o superior, según la versión instalada en el proyecto.
- Livewire 4.
- Filament 4 donde ya forme parte de la arquitectura.
- Blade y Tailwind CSS.
- MySQL o MariaDB.

Las versiones reales del repositorio son la fuente de verdad. No actualizar dependencias principales ni cambiar el stack sin autorización.

No instalar Spatie Permission ni otro paquete de permisos. Utilizar la autenticación, autorización, Policies y Gates existentes.

No introducir un framework JavaScript completo si Livewire y Alpine.js resuelven la necesidad. Un editor especializado podrá incorporarse únicamente si existe en el proyecto o si su adopción está debidamente justificada y autorizada.

---

# Principios de arquitectura

Aplicar de manera pragmática:

- SOLID.
- DRY.
- KISS.
- PSR-12.
- Separación de responsabilidades.
- Reutilización antes de creación.

La lógica de negocio deberá residir, cuando corresponda, en:

- Actions para operaciones concretas.
- Services para procesos reutilizables.
- DTOs cuando aporten claridad real.
- Enums para estados y tipos cerrados.
- Policies para autorización.
- Jobs para trabajo pesado o asíncrono.
- Events y Listeners para efectos secundarios desacoplados.

Los componentes Livewire administrarán estado de interfaz, validación de entrada, interacción y coordinación. No colocar reglas de negocio complejas en Blade, componentes visuales o Controllers.

Evitar abstracciones innecesarias. La arquitectura debe ser extensible, pero el MVP no debe quedar bloqueado por diseñar funcionalidades futuras que todavía no existen.

---

# Alcance del MVP

La primera versión deberá incluir:

- Cuadernos personales.
- Creación, edición, cambio de nombre, archivado y eliminación recuperable de cuadernos.
- Secciones dentro de cada cuaderno.
- Creación, edición, ordenamiento, archivado y eliminación recuperable de secciones.
- Páginas y subpáginas.
- Creación rápida de páginas.
- Título de página.
- Editor de contenido enriquecido.
- Texto, encabezados, listas, listas de verificación, citas, enlaces y bloques de código.
- Inserción de imágenes.
- Archivos adjuntos.
- Guardado automático.
- Indicador visible del estado del guardado.
- Historial básico de versiones.
- Restauración de una versión anterior.
- Búsqueda por título y contenido textual.
- Páginas recientes.
- Páginas favoritas.
- Papelera y restauración.
- Reordenamiento de cuadernos, secciones y páginas.
- Interfaz responsive.
- Aislamiento de información por usuario.
- Pruebas funcionales y de autorización.

---

# Fuera del MVP

No implementar inicialmente, salvo que el usuario lo solicite de forma expresa:

- Colaboración simultánea en tiempo real.
- Cursores de múltiples usuarios.
- Resolución avanzada de conflictos de edición mediante CRDT u OT.
- Compartir cuadernos con otros usuarios.
- Permisos por cuaderno, sección o página.
- Aplicaciones móviles nativas.
- Trabajo completo sin conexión.
- Sincronización con OneDrive, Google Drive o Dropbox.
- Importación directa desde Microsoft OneNote.
- Exportación al formato propietario de OneNote.
- OCR de imágenes o PDF.
- Transcripción de audio.
- Grabación de audio o video.
- Escritura manual avanzada.
- Reconocimiento de escritura manuscrita.
- Lienzo verdaderamente infinito con posicionamiento libre de cada elemento.
- Inteligencia artificial generativa.
- Integraciones con calendarios o correo.

La arquitectura no deberá impedir estas evoluciones, pero tampoco debe añadir complejidad prematura para soportarlas.

---

# Decisión central del editor

El MVP utilizará un **editor de bloques en flujo vertical**, no un lienzo infinito de posicionamiento libre.

Esta decisión permite entregar una experiencia confiable, responsive y fácil de mantener. El editor deberá admitir como mínimo:

- Párrafo.
- Encabezados H1, H2 y H3.
- Negrita, cursiva, subrayado y tachado.
- Listas ordenadas y no ordenadas.
- Lista de verificación interactiva.
- Citas.
- Enlaces.
- Bloques de código.
- Separadores.
- Imágenes.
- Archivos adjuntos.

La capa de persistencia deberá permitir evolucionar posteriormente hacia bloques enriquecidos o un lienzo libre sin perder el contenido existente.

No almacenar como fuente principal HTML arbitrario sin sanear. Preferir una representación estructurada y versionable, por ejemplo JSON del editor, acompañada de texto normalizado para búsqueda. Si el proyecto ya tiene un editor y un formato establecido, reutilizarlos.

---

# Experiencia principal

En escritorio, la pantalla deberá organizarse conceptualmente en columnas:

```text
┌──────────────┬──────────────┬──────────────────────┬──────────────────────────┐
│ Cuadernos    │ Secciones    │ Páginas              │ Editor                   │
│              │              │                       │                          │
│ Personal     │ General      │ Reunión del lunes    │ Título                   │
│ Trabajo      │ Clientes     │ Ideas de campaña     │ Fecha de modificación    │
│ Tecnología   │ Proyectos    │ Configuración PBX    │ Contenido de la página   │
└──────────────┴──────────────┴───────────────────────┴──────────────────────────┘
```

Esta distribución es conceptual. Debe adaptarse al sistema visual existente y a la resolución disponible.

En móvil, las columnas no deberán comprimirse hasta quedar inutilizables. La navegación será progresiva:

```text
Cuadernos → Secciones → Páginas → Editor
```

Se deberán mostrar controles claros para regresar al nivel anterior.

---

# Navegación y rutas

La navegación debe permitir:

- Abrir un cuaderno.
- Seleccionar una sección.
- Seleccionar una página.
- Crear elementos sin abandonar el contexto actual.
- Conservar, cuando sea razonable, el cuaderno, sección y página seleccionados al recargar.
- Utilizar URLs navegables para abrir directamente una página.
- Usar el historial del navegador sin perder el estado de forma inesperada.

Las rutas deberán utilizar identificadores no predecibles si el patrón existente del proyecto lo recomienda, pero la seguridad nunca dependerá únicamente del identificador. Toda consulta deberá validar propiedad o autorización.

---

# Cuadernos

Un cuaderno agrupa secciones relacionadas.

Datos iniciales:

- Nombre.
- Descripción opcional.
- Color o apariencia opcional.
- Posición de ordenamiento.
- Estado activo o archivado.
- Usuario propietario.
- Fechas de creación y modificación.
- Eliminación lógica.

Acciones:

- Crear.
- Editar nombre y descripción.
- Reordenar.
- Archivar y desarchivar.
- Enviar a papelera.
- Restaurar.

No depender exclusivamente del color para identificar un cuaderno.

---

# Secciones

Una sección pertenece a un cuaderno y agrupa páginas.

Datos iniciales:

- Nombre.
- Color opcional.
- Posición.
- Cuaderno.
- Estado activo o archivado.
- Fechas de creación y modificación.
- Eliminación lógica.

El usuario deberá poder crear y cambiar de sección rápidamente. Al eliminar una sección, el sistema deberá explicar que sus páginas también dejarán de verse y deberán poder restaurarse de forma coherente.

---

# Páginas y subpáginas

Una página pertenece a una sección.

Datos iniciales:

- Título.
- Contenido estructurado.
- Texto normalizado para búsqueda.
- Sección.
- Página padre opcional.
- Posición.
- Indicador de favorita.
- Fecha de última edición.
- Usuario creador.
- Usuario que realizó la última modificación.
- Fechas de creación y modificación.
- Eliminación lógica.

Reglas:

- Permitir páginas sin título y mostrar temporalmente `Página sin título`.
- Una subpágina deberá pertenecer a la misma sección que su página padre.
- Limitar el MVP a un solo nivel de subpáginas, salvo que la arquitectura existente resuelva más niveles sin complejidad adicional.
- Cambiar el orden no debe alterar las fechas de contenido ni crear versiones innecesarias.
- Mover una página entre secciones deberá conservar su contenido, adjuntos e historial.
- Si se mueve una página padre, definir un comportamiento coherente para sus subpáginas y probarlo.

---

# Creación rápida

Crear una página debe requerir una sola acción.

Comportamiento esperado:

1. Se crea en la sección activa.
2. Se abre inmediatamente en el editor.
3. El cursor se posiciona en el título o en el contenido.
4. El guardado ocurre automáticamente.

Si no existe ningún cuaderno, el sistema deberá mostrar un estado vacío con una acción clara para crear el primero. No crear datos de ejemplo silenciosamente en producción.

---

# Guardado automático

El editor deberá guardar automáticamente los cambios sin obligar al usuario a presionar un botón.

Requisitos:

- Aplicar `debounce` para evitar una solicitud por cada pulsación.
- Guardar el título y contenido de forma segura.
- Mostrar estados: `Guardando…`, `Guardado` y `Error al guardar`.
- No mostrar `Guardado` antes de recibir confirmación del servidor.
- Conservar cambios pendientes si una solicitud anterior todavía está en curso.
- Evitar que una respuesta antigua sobrescriba contenido más reciente.
- Advertir antes de abandonar la página si existen cambios que no pudieron sincronizarse.
- Reintentar errores transitorios de forma limitada, sin crear un bucle infinito.
- Registrar errores técnicos sin exponer detalles sensibles al usuario.

El intervalo de `debounce` deberá equilibrar seguridad y carga. Como punto inicial puede usarse entre 800 y 1,500 ms, ajustándolo según el editor y la infraestructura existente.

---

# Control de concurrencia

Aun sin colaboración en tiempo real, una página puede abrirse en dos pestañas.

Implementar control de edición optimista mediante un número de versión, `updated_at` comprobado o mecanismo equivalente.

Si el servidor detecta que la página fue modificada después de que el cliente comenzó a editar:

- No sobrescribir silenciosamente la versión más reciente.
- Informar que existe un conflicto.
- Permitir recargar la versión actual.
- Conservar el contenido local para que el usuario pueda copiarlo o recuperarlo.

No implementar CRDT u OT en el MVP.

---

# Historial de versiones

El sistema deberá crear versiones recuperables del contenido.

Reglas mínimas:

- No crear una versión por cada tecla.
- Crear una versión cuando haya cambios materiales y haya transcurrido un intervalo razonable desde la versión anterior.
- Crear una versión antes de restaurar otra versión.
- Registrar autor y fecha.
- Permitir visualizar y restaurar una versión anterior.
- La restauración genera una nueva versión; no borra el historial posterior.

La retención deberá ser configurable. Si no existe una política definida, conservar todas las versiones durante el MVP y documentar la necesidad de una política futura.

---

# Archivos e imágenes

El usuario podrá adjuntar archivos a una página e insertar imágenes en el contenido.

Requisitos:

- Utilizar el sistema de almacenamiento configurado por Laravel.
- No asumir que el disco será siempre local.
- Guardar metadatos en base de datos y el binario en el disco configurado.
- Generar nombres internos seguros y no confiar en el nombre proporcionado por el cliente.
- Conservar el nombre original únicamente como metadato para mostrar o descargar.
- Validar tamaño, extensión y MIME real.
- Rechazar tipos peligrosos.
- Autorizar cada vista y descarga.
- No exponer rutas físicas del servidor.
- Evitar colisiones de nombres.
- Manejar archivos huérfanos cuando falle una operación.
- Definir límites configurables de tamaño y capacidad.

Tipos iniciales sugeridos, sujetos a la política existente:

- Imágenes: JPEG, PNG, WebP y GIF.
- Documentos: PDF, TXT, CSV, DOCX, XLSX y PPTX.

No ejecutar, interpretar ni renderizar directamente contenido activo proporcionado por el usuario.

---

# Búsqueda

La búsqueda del MVP deberá encontrar páginas por:

- Título.
- Contenido textual normalizado.
- Nombre del cuaderno.
- Nombre de la sección.

Comportamiento:

- Restringir siempre los resultados al usuario autorizado.
- Mostrar título, cuaderno, sección, fragmento coincidente y fecha de modificación.
- Permitir abrir directamente el resultado.
- Ordenar principalmente por relevancia y, en igualdad, por modificación reciente.
- Aplicar `debounce` en la interfaz.
- Mostrar un estado claro cuando no existan resultados.

Para el MVP puede utilizarse búsqueda compatible con MySQL/MariaDB según el volumen real. No incorporar Elasticsearch, Meilisearch u otro motor externo sin necesidad demostrada y autorización.

OCR, búsqueda dentro de imágenes, audio y documentos adjuntos quedan fuera del MVP.

---

# Recientes y favoritos

La vista de recientes deberá mostrar páginas modificadas recientemente por el usuario.

El usuario podrá marcar o desmarcar una página como favorita con una sola acción. Favoritos y recientes no deben duplicar páginas ni moverlas de su sección original; son vistas alternativas.

---

# Papelera

Las eliminaciones ordinarias deberán ser recuperables mediante Soft Deletes o el mecanismo existente.

La papelera deberá permitir:

- Ver cuadernos, secciones y páginas eliminados.
- Identificar su ubicación original.
- Restaurar elementos.
- Eliminar definitivamente únicamente mediante una acción explícita y confirmada.

Antes de implementar restauraciones en cascada, definir y probar qué ocurre cuando el elemento padre sigue eliminado. El sistema deberá restaurar la jerarquía necesaria o solicitar una ubicación válida; nunca dejar datos inaccesibles.

---

# Reordenamiento y movimiento

Se podrá cambiar el orden de cuadernos, secciones y páginas mediante controles accesibles. Arrastrar y soltar puede utilizarse, pero deberá existir una alternativa mediante teclado o botones.

Persistir posiciones de forma eficiente y validar en el servidor que todos los elementos reordenados pertenecen al mismo usuario y contenedor autorizado.

Mover páginas entre secciones y secciones entre cuadernos podrá incluirse en el MVP si no compromete la estabilidad. En caso contrario, implementar primero el reordenamiento dentro del mismo contenedor y documentar el movimiento como siguiente fase.

---

# Modelo de datos propuesto

Los nombres definitivos deberán respetar las convenciones del proyecto. Verificar primero si existen tablas reutilizables.

Entidades sugeridas:

```text
notebooks
notebook_sections
note_pages
note_page_versions
note_attachments
```

Campos conceptuales:

```text
notebooks
- id
- user_id
- name
- description nullable
- color nullable
- position
- archived_at nullable
- timestamps
- deleted_at

notebook_sections
- id
- notebook_id
- name
- color nullable
- position
- archived_at nullable
- timestamps
- deleted_at

note_pages
- id
- notebook_section_id
- parent_id nullable
- created_by
- updated_by
- title nullable
- content_json
- searchable_text nullable
- position
- is_favorite
- content_version
- last_edited_at nullable
- timestamps
- deleted_at

note_page_versions
- id
- note_page_id
- user_id
- title nullable
- content_json
- content_version
- created_at

note_attachments
- id
- note_page_id
- user_id
- disk
- path
- original_name
- stored_name
- mime_type
- extension nullable
- size_bytes
- checksum nullable
- timestamps
- deleted_at nullable
```

Crear índices para claves foráneas, ordenamiento, favoritos, fechas recientes y búsqueda según las capacidades de la base de datos real.

No guardar archivos binarios grandes dentro de MySQL salvo que la arquitectura existente lo exija explícitamente.

---

# Integridad y transacciones

Usar claves foráneas y restricciones compatibles con el proyecto.

Las operaciones que modifican varias entidades deberán ser transaccionales cuando corresponda, por ejemplo:

- Mover una página y reordenar las posiciones afectadas.
- Restaurar una jerarquía eliminada.
- Crear metadatos de adjunto y confirmar su asociación.
- Restaurar una versión y registrar la versión anterior.

No envolver cargas de archivos completas en transacciones largas de base de datos.

---

# Seguridad y autorización

Cada usuario solo podrá consultar y modificar sus propios cuadernos, secciones, páginas, versiones y adjuntos.

Aplicar autorización en el servidor para todas las operaciones, incluidas:

- Lectura.
- Búsqueda.
- Creación.
- Edición.
- Reordenamiento.
- Movimiento.
- Eliminación.
- Restauración.
- Descarga de adjuntos.
- Consulta y restauración de versiones.

No confiar en IDs recibidos desde Livewire o el navegador.

Prevenir:

- IDOR.
- XSS almacenado.
- Carga de archivos maliciosos.
- Acceso directo no autorizado a archivos.
- Asignación masiva de campos sensibles.
- Eliminación o restauración de datos ajenos.

Sanear el contenido enriquecido en el servidor con una lista permitida de elementos y atributos. Las vistas previas y resultados de búsqueda también deberán escapar contenido correctamente.

---

# Rendimiento

Evitar cargar en una sola respuesta todo el contenido de todos los cuadernos.

Lineamientos:

- Cargar la jerarquía necesaria para navegar.
- Cargar el contenido completo únicamente de la página seleccionada.
- Paginar o aplicar carga progresiva en recientes, favoritos, papelera y búsqueda.
- Evitar consultas N+1.
- Seleccionar únicamente las columnas necesarias en listados.
- No incluir `content_json` completo al construir listas de páginas.
- Indexar las consultas frecuentes después de verificar su uso real.
- Mantener el guardado automático ligero.

---

# Estados vacíos y manejo de errores

Crear estados vacíos útiles para:

- Usuario sin cuadernos.
- Cuaderno sin secciones.
- Sección sin páginas.
- Búsqueda sin resultados.
- Favoritos vacíos.
- Papelera vacía.

Los errores de guardado, carga y subida deberán explicar qué ocurrió en lenguaje sencillo y ofrecer una acción de recuperación cuando sea posible.

Nunca descartar silenciosamente contenido escrito por el usuario.

---

# Diseño visual

La interfaz deberá sentirse integrada al sistema existente.

Prioridades:

- Lectura cómoda.
- Jerarquía visual clara.
- Mínima fricción para crear una nota.
- Navegación rápida.
- Editor amplio.
- Indicadores discretos pero claros.
- Compatibilidad con modo claro u oscuro si ya existe.

No copiar íconos, logotipos, nombres protegidos ni detalles visuales exactos de OneNote. Utilizar los componentes, iconografía y paleta del proyecto.

---

# Responsive

El módulo deberá funcionar correctamente en:

- Desktop.
- Laptop.
- Tablet.
- Móvil.

En escritorio se priorizará la navegación multicolumna y el espacio del editor.

En móvil se priorizarán:

- Navegación por niveles.
- Editor a pantalla completa.
- Barra de herramientas compacta y desplazable.
- Acciones principales accesibles con el pulgar.
- Conservación segura del contenido cuando cambie la orientación o se interrumpa la conexión.

---

# Accesibilidad

Cumplir, en lo razonable, WCAG 2.1 AA y las convenciones de accesibilidad existentes.

Incluir:

- Navegación mediante teclado.
- Foco visible.
- Etiquetas accesibles.
- Contraste suficiente.
- Texto alternativo editable para imágenes cuando corresponda.
- Botones con nombre comprensible.
- Alternativas a arrastrar y soltar.
- Mensajes de guardado anunciables sin interrumpir la escritura.
- Jerarquía semántica correcta.

No depender exclusivamente del color o de un ícono para comunicar estado.

---

# Internacionalización y zona horaria

Idioma inicial: **español**.

Si el proyecto dispone de archivos de traducción, utilizarlos. No dispersar textos críticos directamente en componentes cuando existe infraestructura de internacionalización.

Guardar fechas siguiendo las convenciones del proyecto y presentarlas en la zona horaria configurada para el usuario o la aplicación. No fijar una zona horaria dentro de la lógica de negocio.

---

# Notificaciones

El MVP no necesita notificaciones invasivas. Podrá mostrar confirmaciones discretas para:

- Guardado exitoso.
- Error de guardado.
- Archivo cargado.
- Elemento restaurado.
- Conflicto de edición.

No emitir una notificación por cada guardado automático correcto si esto distrae al usuario. El indicador de estado del editor es suficiente.

---

# Migraciones y protección de datos

Todas las migraciones deberán ser aditivas y compatibles con una base existente.

Está prohibido:

- Eliminar tablas existentes.
- Eliminar columnas existentes.
- Reinicializar la base de datos.
- Usar `migrate:fresh`.
- Usar `db:wipe`.
- Usar `truncate` sobre datos existentes.
- Sobrescribir configuración de producción.
- Eliminar archivos reales para facilitar pruebas.

Antes de cada migración, revisar colisiones de nombres y relaciones con módulos existentes.

---

# Pruebas

Las pruebas deberán seguir el mecanismo seguro ya establecido por el proyecto.

No usar contra una base de datos con información real:

- `RefreshDatabase`.
- `DatabaseMigrations`.
- `DatabaseTruncation`.
- `migrate:fresh`.
- `db:wipe`.
- `truncate`.

Las pruebas deberán crear datos aislados y eliminar únicamente lo creado por ellas, o utilizar una base de datos de pruebas inequívocamente separada y configurada para ese propósito.

Nunca ejecutar una suite destructiva hasta comprobar el entorno y la conexión activa.

---

# Pruebas funcionales mínimas

## Cuadernos

- [ ] Crear un cuaderno.
- [ ] Editar un cuaderno.
- [ ] Reordenar cuadernos.
- [ ] Archivar y restaurar un cuaderno.
- [ ] Enviar un cuaderno a papelera y restaurarlo.

## Secciones

- [ ] Crear una sección dentro del cuaderno autorizado.
- [ ] Editar y reordenar secciones.
- [ ] Impedir crear o mover una sección dentro de un cuaderno ajeno.
- [ ] Eliminar y restaurar una sección con sus páginas.

## Páginas

- [ ] Crear una página en la sección activa.
- [ ] Crear una subpágina.
- [ ] Editar título y contenido.
- [ ] Guardar automáticamente.
- [ ] Mostrar un error de guardado sin perder el contenido local.
- [ ] Detectar un conflicto entre dos ediciones.
- [ ] Mover o reordenar una página.
- [ ] Marcar y desmarcar como favorita.
- [ ] Eliminar y restaurar una página.

## Editor

- [ ] Crear párrafos y encabezados.
- [ ] Aplicar formato básico.
- [ ] Crear listas y listas de verificación.
- [ ] Crear enlaces y bloques de código.
- [ ] Sanear contenido no permitido.
- [ ] Mantener el contenido después de recargar.

## Versiones

- [ ] Crear versiones sin generar una por cada tecla.
- [ ] Consultar versiones anteriores.
- [ ] Restaurar una versión.
- [ ] Confirmar que restaurar no elimina versiones posteriores.

## Archivos

- [ ] Cargar una imagen válida.
- [ ] Cargar un documento válido.
- [ ] Rechazar extensión, MIME o tamaño no permitido.
- [ ] Autorizar visualización y descarga.
- [ ] Impedir que un usuario descargue archivos ajenos.

## Búsqueda

- [ ] Buscar por título.
- [ ] Buscar por contenido textual.
- [ ] Mostrar cuaderno, sección y fragmento.
- [ ] No devolver resultados de otros usuarios.

## Seguridad

- [ ] Un usuario no puede ver cuadernos de otro usuario.
- [ ] Un usuario no puede abrir una página ajena mediante URL directa.
- [ ] Un usuario no puede modificar, mover, eliminar ni restaurar información ajena.
- [ ] Un usuario no puede acceder a versiones ni adjuntos ajenos.
- [ ] El contenido enriquecido no ejecuta scripts.

## Responsive y accesibilidad

- [ ] Navegar y editar en escritorio.
- [ ] Navegar y editar en móvil.
- [ ] Usar las acciones principales con teclado.
- [ ] Reordenar mediante una alternativa accesible.

---

# Criterios de aceptación del MVP

El módulo se considerará funcional cuando:

- [ ] El usuario pueda crear cuadernos, secciones, páginas y subpáginas.
- [ ] La jerarquía sea clara y navegable.
- [ ] La URL permita regresar directamente a una página autorizada.
- [ ] El editor permita contenido enriquecido básico.
- [ ] El contenido se guarde automáticamente con estado visible.
- [ ] Un error de red o de servidor no descarte silenciosamente cambios.
- [ ] Se detecten conflictos de edición básicos.
- [ ] Exista historial de versiones y restauración segura.
- [ ] Se puedan insertar imágenes y adjuntar archivos autorizados.
- [ ] La búsqueda encuentre títulos y contenido textual.
- [ ] Existan vistas de recientes y favoritos.
- [ ] La papelera permita restaurar información.
- [ ] Ningún usuario pueda acceder a información de otro usuario.
- [ ] La interfaz funcione en desktop y móvil.
- [ ] Las acciones principales sean accesibles por teclado.
- [ ] Las pruebas relevantes estén aprobadas.
- [ ] No se rompan módulos existentes.
- [ ] Se respeten `AGENTS.md` y las convenciones del proyecto.

---

# Fases de implementación recomendadas

## Fase 1: diagnóstico y diseño

- Inspeccionar el proyecto.
- Confirmar stack, autenticación, almacenamiento y pruebas.
- Identificar componentes reutilizables.
- Definir formato del contenido del editor.
- Definir esquema final y estrategia de autorización.

## Fase 2: dominio y persistencia

- Crear migraciones aditivas.
- Crear modelos, relaciones, Enums y Policies.
- Implementar Actions y Services principales.
- Implementar papelera y ordenamiento básico.

## Fase 3: navegación y CRUD funcional

- Construir navegación de cuadernos, secciones y páginas.
- Implementar creación rápida.
- Añadir responsive y estados vacíos.

## Fase 4: editor y guardado

- Integrar el editor.
- Sanear contenido.
- Implementar guardado automático.
- Implementar control de concurrencia.

## Fase 5: archivos, búsqueda y versiones

- Implementar imágenes y adjuntos.
- Añadir búsqueda.
- Añadir recientes y favoritos.
- Implementar historial y restauración.

## Fase 6: pruebas y cierre

- Ejecutar pruebas funcionales, de autorización y seguridad.
- Revisar rendimiento y consultas.
- Verificar responsive y accesibilidad.
- Documentar decisiones, configuración y limitaciones.

Cada fase deberá dejar el proyecto ejecutable. No acumular todos los cambios sin validarlos progresivamente.

---

# Evolución futura

La arquitectura podrá evolucionar hacia:

- Cuadernos compartidos.
- Roles de lector y editor.
- Colaboración en tiempo real.
- Comentarios y menciones.
- Lienzo infinito.
- Dibujo y tinta digital.
- Audio, video y transcripción.
- OCR de imágenes y PDF.
- Etiquetas.
- Enlaces bidireccionales entre páginas.
- Plantillas.
- Exportación a PDF, HTML o Markdown.
- Importación de documentos.
- Sincronización externa.
- Aplicación web progresiva con soporte sin conexión.
- IA para resumir, clasificar y relacionar notas.

La IA futura podrá:

- Resumir una página o sección.
- Generar una lista de acciones a partir de una nota.
- Encontrar páginas relacionadas.
- Proponer títulos y etiquetas.
- Responder preguntas sobre notas autorizadas.

La IA nunca deberá modificar o compartir contenido sin autorización explícita del usuario.

---

# Restricciones finales

Codex no deberá:

- Construir únicamente una maqueta sin persistencia real.
- Copiar la interfaz o identidad visual exacta de OneNote.
- Instalar dependencias sin revisar primero las existentes.
- Cambiar autenticación, permisos o configuración global sin necesidad y autorización.
- Introducir colaboración en tiempo real dentro del MVP.
- Exponer adjuntos mediante URLs públicas permanentes si contienen información privada.
- Confiar en IDs del cliente para autorización.
- Guardar HTML inseguro.
- Ejecutar operaciones destructivas sobre la base de datos.
- Dar por finalizada la tarea sin ejecutar verificaciones.

---

# Resultado esperado

El producto final deberá sentirse como un cuaderno digital propio, rápido y confiable:

```text
CUADERNOS
    ↓
SECCIONES
    ↓
PÁGINAS Y SUBPÁGINAS
    ↓
EDICIÓN Y GUARDADO AUTOMÁTICO
    ↓
BÚSQUEDA, VERSIONES Y RECUPERACIÓN
```

El usuario deberá poder entrar al módulo, encontrar el contexto correcto, escribir de inmediato y confiar en que su información quedó guardada y podrá recuperarse.

> **Una nota útil no solo se escribe: se organiza, se encuentra y se conserva.**
