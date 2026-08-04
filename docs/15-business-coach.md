# 15. Business Coach

## Objetivo

Business Coach es un asistente inteligente basado en OpenAI.

Su propósito no es responder preguntas generales.

Su propósito es analizar el estado del negocio utilizando el contexto de la pantalla actual y generar recomendaciones prácticas para el usuario.

Debe comportarse como un consultor especializado en empresas de servicios recurrentes.

No es un chatbot.

Es un asesor operativo.

---

# Objetivo principal

Cada pantalla del sistema deberá contar con un botón llamado:

🧠 Analizar

Al presionar este botón, el sistema generará automáticamente un análisis utilizando la API de OpenAI.

---

# Filosofía

La IA nunca accederá directamente a la base de datos.

La IA únicamente recibirá un resumen estructurado generado por Laravel.

Esto desacopla completamente el modelo de datos del modelo de IA.

---

# Arquitectura

Base de datos

↓

Laravel

↓

Context Builder

↓

JSON

↓

OpenAI

↓

Markdown

↓

Business Coach Panel

---

# Context Builder

Cada módulo deberá construir su propio contexto.

Ejemplos:

ClientesContextBuilder

ServiciosContextBuilder

CobrosContextBuilder

CompromisosContextBuilder

DashboardContextBuilder

ProveedoresContextBuilder

Cada Builder tendrá la responsabilidad de resumir la información relevante de su pantalla.

Nunca deberá enviar tablas completas.

Nunca deberá enviar HTML.

Nunca deberá enviar información innecesaria.

---

# Prompt

Cada módulo tendrá un prompt independiente.

Ejemplos:

prompts/

clientes.md

servicios.md

cobros.md

compromisos.md

dashboard.md

proveedores.md

Esto permitirá especializar el comportamiento del Business Coach.

---

# Información enviada

La información enviada a OpenAI deberá estar estructurada.

Ejemplo

{
"page":"clientes",

    "total_clientes":125,

    "clientes_con_deuda":12,

    "clientes_con_un_servicio":84,

    "clientes_cancelados_mes":3,

    "clientes_nuevos_mes":5

}

Nunca enviar HTML.

Nunca enviar consultas SQL.

Nunca enviar modelos completos.

---

# OpenAI

El sistema utilizará una API Key configurada mediante variables de entorno.

OPENAI_API_KEY

La integración deberá encapsularse en un servicio.

Ejemplo:

BusinessCoachService

Toda llamada a OpenAI deberá pasar por este servicio.

Nunca realizar llamadas directamente desde Livewire o Controladores.

---

# Interfaz

Cada pantalla tendrá un botón.

🧠 Analizar

Este botón abrirá un panel lateral.

No utilizar modales.

El panel lateral permitirá seguir visualizando la pantalla principal.

---

# Panel lateral

El panel mostrará:

Resumen

Riesgos

Oportunidades

Recomendaciones

Acciones sugeridas

Observaciones

---

# Estilo del análisis

El análisis debe ser breve.

Máximo:

600 palabras.

Debe ser fácilmente escaneable.

Utilizar títulos.

Listas.

Prioridades.

Nunca responder con párrafos excesivamente largos.

---

# Tipo de respuestas

La IA deberá actuar como un consultor.

No como un chatbot.

Ejemplo

✔ Detecté concentración de ingresos.

✔ Conviene contactar primero estos clientes.

✔ Tu proveedor principal representa demasiado riesgo.

✔ El servicio X tiene margen inferior al promedio.

✔ La liquidez proyectada para la próxima semana es ajustada.

---

# Personalidad

La IA debe responder como:

Director Financiero

Consultor de Negocios

Director de Operaciones

Nunca responder como un asistente genérico.

Nunca responder de forma excesivamente conversacional.

Las recomendaciones deben ser concretas.

---

# Botones

Actualizar análisis

Copiar

Guardar como nota

Cerrar

---

# Historial

No guardar automáticamente todos los análisis.

El usuario decidirá si desea guardar uno.

Esto evitará llenar la base de datos con información irrelevante.

---

# Rendimiento

El análisis se genera únicamente cuando el usuario presiona el botón.

Nunca generar automáticamente.

Nunca consumir tokens sin interacción del usuario.

---

# Caché

Si la información de la pantalla no cambia, reutilizar el análisis durante un tiempo configurable.

Esto reducirá el consumo de la API.

---

# Seguridad

Nunca enviar:

API Keys

Contraseñas

Tokens

Información sensible

Credenciales

Información privada innecesaria

Enviar únicamente la información mínima necesaria para el análisis.

---

# Compatibilidad

La implementación no debe modificar módulos existentes.

Debe agregarse como una funcionalidad adicional.

No deberá alterar el comportamiento actual del sistema.

---

# Definición de terminado

El módulo estará terminado cuando:

Todas las pantallas principales tengan el botón Analizar.

Cada pantalla construya su propio contexto.

La IA genere recomendaciones útiles y específicas para esa pantalla.

El análisis se muestre en un panel lateral.

El usuario pueda copiar o guardar el análisis.

No se rompa ninguna funcionalidad existente.
