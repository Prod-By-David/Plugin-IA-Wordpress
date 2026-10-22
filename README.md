# Asistente de IA para WordPress (AICA)

**Versión:** 1.0.0  
**Autor:** David Bravo Bravo  
**Licencia:** GPLv2 o posterior  
**Compatibilidad:** WordPress 5.8+, PHP 7.4+  

---

## 📌 Descripción General

**Asistente de IA para WordPress** es una solución SaaS/Plugin diseñada para extender las funcionalidades nativas del panel de administración y editor de WordPress (Gutenberg y Editor Clásico). Mediante la conexión directa con modelos de lenguaje de última generación (OpenAI API - `gpt-4o-mini`), el plugin permite a redactores, gestores de contenidos y agencias automatizar el proceso de creación, optimización SEO y traducción de entradas.

El plugin integra el SDK oficial de **Freemius** para la gestión automatizada de licencias, pruebas gratuitas de 7 días, pasarela de cobro recurrente anual (99 €/año) y control de acceso a funciones premium.

---

## ✨ Características Principales

- **Generación de Contenido Avanzado:** Creación automática de borradores estructurados con encabezados (H2, H3) e hipervínculos según la temática introducida.
- **Optimización SEO Asistida:** Análisis de textos para la generación de meta-títulos y meta-descripciones enfocados en posicionamiento web.
- **Traducción Multilingüe Integradora:** Traducción fluida al inglés manteniendo el tono comercial y el formato HTML original.
- **Integración con Gutenberg y Editor Clásico:** Inserción nativa de bloques mediante la API de bloques de WordPress (`wp.blocks.rawHandler`).
- **Seguridad y Rendimiento:**
  - Comunicación asíncrona a través de endpoints seguros en la API REST (`/wp-json/aica/v1/generate`).
  - Protección de peticiones con ganchos de verificación de permisos (`edit_posts`) y firmas *Nonce* (`X-WP-Nonce`).
  - Sanitización estricta de entradas/salidas de datos (`sanitize_textarea_field`, `esc_attr`).
- **Monetización con Freemius SDK:**
  - Gestión de trials y planes Pro de suscripción recurrente.
  - Bloqueo de llamadas API REST para instancias sin licencia activa.

---

## 🏗️ Arquitectura del Proyecto

El plugin sigue una arquitectura modular en PHP basada en Clases y Programación Orientada a Objetos (POO):

```text
ai-content-assistant/
│
├── ai-content-assistant.php   # Archivo principal, cabeceras, constantes e inicialización del SDK
├── README.md                  # Documentación técnica del repositorio
├── freemius/                  # SDK Oficial de Freemius para gestión de licencias
│   └── start.php
└── includes/
    ├── class-settings.php     # Panel de opciones en el Administrador de WordPress
    ├── class-api-handler.php  # Controlador de peticiones HTTP a la API de OpenAI (wp_remote_post)
    ├── class-rest-api.php     # Registro del endpoint REST seguro en WordPress
    └── class-meta-box.php     # Interfaz del usuario en el editor y script de inserción (JS/Fetch)
```

---

## 🚀 Guía de Instalación y Configuración

### 1. Requisitos Previos
- Servidor web con WordPress 5.8 o superior.
- PHP versión 7.4 o superior.
- Clave API de OpenAI ([Obtener clave API](https://platform.openai.com/)).
- Cuenta activa en Freemius (para desarrolladores) con la aplicación configurada (Product ID y Public Key).

### 2. Instalación desde el archivo `.zip`
1. Descarga o genera el archivo `ai-content-assistant.zip` desde el directorio raíz.
2. Inicia sesión en el panel de administración de WordPress.
3. Ve a **Plugins > Añadir nuevo > Subir plugin**.
4. Selecciona el archivo `.zip` y haz clic en **Instalar ahora**.
5. Haz clic en **Activar plugin**.

### 3. Activación de Licencia (Freemius)
1. Tras la activación del plugin, serás redirigido automáticamente al asistente de activación de **Freemius**.
2. Introduce tu clave de licencia comprada o inicia el periodo de **Prueba Gratuita de 7 días** para desbloquear el acceso a los componentes de Inteligencia Artificial.

### 4. Configuración de la Clave API de OpenAI
1. En el panel de control de WordPress, dirígete a **Ajustes > Asistente IA**.
2. Introduce tu clave secreta de OpenAI en el campo **Clave API** (ejemplo: `sk-proj-...`).
3. Haz clic en **Guardar Cambios**.

---

## 🛠️ Modo de Uso

1. Crea o edita una entrada en **Entradas > Añadir nueva**.
2. En la barra lateral derecha del editor, ubica la caja titulada **Asistente de IA (Generador de Contenido)**.
3. Selecciona el tipo de acción deseada:
   - **Generar Borrador / Artículo**
   - **Optimizar SEO y Títulos**
   - **Traducir al Inglés**
4. Escribe la temática, instrucciones o el texto base en el campo **Petición o Tema**.
5. Haz clic en **Generar con IA**.
6. El contenido se procesará de forma asíncrona y se insertará automáticamente dentro del editor como bloques nativos de Gutenberg o en el área de texto del Editor Clásico.

---

## 🔐 Seguridad y Sanitización

- **Protección contra accesos directos:** Todos los archivos ejecutan la validación `defined('ABSPATH') || exit;`.
- **Comprobación de Capacidad:** Solo los usuarios con permisos de edición de entradas (`edit_posts`) pueden realizar peticiones al endpoint REST.
- **Sanitización:** Los prompts de texto multilínea son procesados mediante `sanitize_textarea_field()` y las opciones guardadas en la base de datos se desinfectan de forma estricta.

---

## 📄 Licencia

Este proyecto se distribuye bajo la licencia **GPLv2 o posterior**.
```
eof

Here is the complete `README.md` file ready for your plugin repository. It includes all necessary details regarding installation, configuration, features, and architecture.