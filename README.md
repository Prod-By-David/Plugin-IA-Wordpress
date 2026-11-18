# Asistente de IA para WordPress (AICA)

Plugin de WordPress que añade Inteligencia Artificial al editor de entradas y páginas para **generar borradores, optimizar el SEO y traducir contenido** sin salir del panel de control.

| | |
|---|---|
| **Versión** | 1.0.0 |
| **Autor** | David Bravo Bravo |
| **Licencia** | GPLv2 o posterior |
| **Requisitos declarados** | WordPress 5.8+ · PHP 7.4+ |
| **Proveedores de IA** | OpenAI (ChatGPT) y Anthropic (Claude) |
| **Licencias y pagos** | SDK de Freemius 2.13.4 |

> ## Estado actual: MODO PRUEBA
> Siguiendo la indicación de dirección —*«deja todos los proyectos en modo prueba; pasar a modo real lo decidiremos cuando vayamos a vender cada producto»*—
> Freemius está configurado en **modo prueba (sandbox)**: los pagos y las licencias son ficticios y no se cobra nada.
> Para pasar a modo real, ver [Pasar a modo real](#pasar-a-modo-real).

---

## Índice

1. [Dónde está desplegada](#1-dónde-está-desplegada)
2. [Qué hace la aplicación](#2-qué-hace-la-aplicación)
3. [Instalación en un WordPress de prueba (paso a paso)](#3-instalación-en-un-wordpress-de-prueba-paso-a-paso)
4. [Probar la prueba gratuita y el pago ficticio (Freemius en sandbox)](#4-probar-la-prueba-gratuita-y-el-pago-ficticio-freemius-en-sandbox)
5. [Lista de comprobación tras instalar](#5-lista-de-comprobación-tras-instalar)
6. [Problemas frecuentes](#6-problemas-frecuentes)
7. [Configuración avanzada](#7-configuración-avanzada)
8. [Arquitectura del proyecto](#8-arquitectura-del-proyecto)
9. [Seguridad](#9-seguridad)
10. [Estado de verificación](#10-estado-de-verificación)
11. [Pasar a modo real](#pasar-a-modo-real)

---

## 1. Dónde está desplegada

**No está desplegada en ningún sitio público ni en producción.** Es un plugin que se instala en un WordPress; el código solo existe como archivo ZIP (`ai-content-assistant.zip`) y, hasta ahora, solo se ha instalado en una **instalación local de prueba** (XAMPP en Windows, carpeta `C:\xampp\htdocs\prueba`, a la que se accede en `http://localhost/prueba/`).

| Elemento | Estado |
|---|---|
| Servidor de producción / web pública | **No hay** |
| WordPress de prueba | Local (XAMPP). Sirve cualquier otro WordPress de prueba (Local, LocalWP, Docker, hosting de staging) |
| Freemius (producto ID `42286`, slug `ai-content-assistant`) | **Modo prueba** (`AICA_FS_IS_LIVE = false`) |
| Claves de IA (OpenAI / Claude) | No están en el código. Cada instalación pega la suya en **Ajustes → Asistente IA** |
| Repositorio de código | *(indicar aquí la URL cuando se publique el repositorio)* |

Cuando haya una web de pruebas en un servidor, anótala en la tabla de arriba.

---

## 2. Qué hace la aplicación

### 2.1. Las tres acciones de IA

Todas se lanzan desde el mismo panel, eligiendo la **Acción de IA** en un desplegable.

| Acción (texto del desplegable) | Qué hace exactamente | Qué le envía a la IA |
|---|---|---|
| **Generar borrador / artículo** | Redacta un borrador estructurado con subtítulos (`h2`, `h3`) y párrafos cortos a partir de la petición («Escribe sobre los beneficios del café orgánico…»). No inventa enlaces ni datos concretos; solo incluye enlaces si tú los das. | Tu petición y, si marcas *Usar el contenido actual de la entrada*, el texto actual como contexto. |
| **Optimizar SEO y títulos** | Devuelve **3 meta-títulos** (máx. 60 caracteres), **1 meta-descripción** (máx. 155) y **5 palabras clave** sugeridas, cada bloque con su subtítulo y en listas. Responde en el idioma del contenido. | El título de la entrada, tus instrucciones (opcionales) y el contenido actual. |
| **Traducir entrada** | Traduce a uno de **8 idiomas**: inglés, español, francés, alemán, italiano, portugués, catalán y neerlandés. Mantiene exactamente la estructura HTML, los enlaces y las imágenes; traduce solo el texto visible y los atributos `alt` y `title`. Tono natural, comercial y profesional. | El contenido de la entrada (si marcas *Usar el contenido actual*) o, si no, el texto que escribas en la caja. |

Notas importantes sobre cómo funcionan:

- **Nunca modifica la entrada por su cuenta.** Primero muestra el resultado en una vista previa; solo se añade al editor cuando pulsas **Insertar**.
- **«Optimizar SEO» devuelve sugerencias como texto.** No rellena automáticamente los campos de Yoast, Rank Math u otros plugins de SEO: las copias o las insertas tú.
- **«Traducir» no sustituye el original:** inserta la traducción *además* del contenido existente (hay que borrar el original a mano si no se quiere). Tampoco traduce el título de la entrada.
- Para **SEO** y **Traducir**, la casilla *Usar el contenido actual de la entrada* se marca sola; para **Generar** viene desmarcada.
- Si el resultado ha de ser muy largo (entradas extensas), puede quedar cortado: con Claude el límite de salida por defecto es de 4.096 tokens (ajustable con el filtro `aica_max_tokens`).

### 2.2. Dónde aparece en el editor

| Editor | Qué se ve | Qué hace «Insertar» |
|---|---|---|
| **Gutenberg (editor de bloques)** | 1) El bloque **«Asistente de IA»** (categoría *Widgets*; búscalo escribiendo «IA»). 2) La caja **«Asistente de IA (Generador de Contenido)»** en la barra lateral, con el botón **Generar con IA**. | **Caja lateral:** convierte el HTML en bloques nativos y los añade a la entrada. **Bloque:** **«Insertar y reemplazar este bloque»** sustituye el propio bloque del asistente por el resultado. |
| **Editor Clásico** | La caja **«Asistente de IA (Generador de Contenido)»** en la barra lateral. | En la pestaña **Visual** inserta donde esté el cursor; en la pestaña **Texto** lo añade al final. |

Además, en ambos editores hay un botón **Copiar HTML** para copiar el resultado y pegarlo donde quieras.

El bloque «Asistente de IA» es **solo de editor**: en la web pública no pinta nada, no deja rastro en el contenido publicado.

Aparece por defecto en **Entradas** y **Páginas** (se puede ampliar a otros tipos de contenido, ver [filtros](#7-configuración-avanzada)).

### 2.3. Página de ajustes (Ajustes → Asistente IA)

Solo para administradores. Contiene:

- **Proveedor de IA:** OpenAI (ChatGPT) o Anthropic (Claude).
- **Clave API:** campo de contraseña. Una vez guardada **no se vuelve a mostrar**; solo se indican sus 4 últimos caracteres. Dejar el campo vacío conserva la clave guardada; hay una casilla **Eliminar la clave guardada**. Si la clave no parece del proveedor elegido (las de Claude empiezan por `sk-ant-`), avisa.
- **Modelo (opcional):** por defecto `gpt-4o-mini` (OpenAI) y `claude-haiku-5-5` (Claude). Se puede escribir otro nombre de modelo.
- **Cuadro de estado:** indica si el sitio está en versión gratuita (y cuántos usos lleva) o con licencia/prueba activa, y muestra un aviso naranja **«Freemius en MODO PRUEBA (sandbox)»** mientras esté en modo prueba.

### 2.4. Versión gratuita, prueba y licencia

| | Sin licencia (gratuita) | Prueba de 7 días o licencia |
|---|---|---|
| Generaciones | **5 al mes** para todo el sitio | **Ilimitadas** |
| Contador | Se reinicia solo cada mes natural (UTC) | — |
| Cuando se agota | El editor muestra el aviso con el enlace **«Iniciar prueba o comprar licencia»** | — |

- Un error del proveedor de IA (clave incorrecta, sin saldo…) **no consume** cupo.
- El límite de 5 usos se cambia en la constante `AICA_FREE_MONTHLY_LIMIT` o con el filtro `aica_free_monthly_limit`.
- La **suscripción de 99 €/año**, la moneda y la **prueba gratuita de 7 días (sin tarjeta)** se gestionan con **Freemius**: el código solo dice que existe una prueba de 7 días sin pago; el precio y los planes **se configuran en el panel de Freemius**, no en el plugin.

### 2.5. Quién puede usar qué

| Acción | Permiso de WordPress necesario |
|---|---|
| Usar el asistente en el editor | `edit_posts` (colaboradores, autores, editores y administradores) |
| Ver y cambiar Ajustes → Asistente IA | `manage_options` (administradores) |

La clave API y el cupo son **del sitio**, no de cada usuario: cualquier usuario que pueda editar entradas gasta del mismo cupo y de la misma cuenta de IA.

### 2.6. Límites de uso

- Petición: máximo **4.000** caracteres. Contenido de la entrada enviado: máximo **30.000**.
- Máximo **10 peticiones por usuario y minuto** (anti-abuso; filtro `aica_rate_limit`).
- Espera máxima a la IA: **60 segundos** por petición.

---

## 3. Instalación en un WordPress de prueba (paso a paso)

### Requisitos previos

- Un WordPress funcionando (5.8 o superior) con **PHP 7.4 o superior**. En XAMPP: Apache y MySQL en marcha y WordPress ya instalado (p. ej. en `C:\xampp\htdocs\prueba`).
- **Acceso a Internet desde el servidor** (el plugin llama a la API de IA y a Freemius).
- Una **clave API** de OpenAI (<https://platform.openai.com/api-keys>) o de Anthropic (<https://console.anthropic.com/>). Las llamadas se facturan a la cuenta dueña de la clave: usa una clave con límite de gasto para las pruebas.
- El archivo **`ai-content-assistant.zip`**.

### Opción A — Subir el ZIP desde el panel (recomendada)

1. Entra en el panel: `http://localhost/prueba/wp-admin/` (o la URL de tu sitio de pruebas).
2. **Plugins → Añadir nuevo → Subir plugin**.
3. Elige `ai-content-assistant.zip` y pulsa **Instalar ahora**.
4. Pulsa **Activar plugin**.
5. Si aparece la pantalla de bienvenida de **Freemius**, puedes continuar con ella o saltarla; sin licencia el plugin funciona en versión gratuita (5 usos al mes). La prueba de 7 días se inicia más tarde desde el enlace **«Iniciar prueba o comprar licencia»** (ver [sección 4](#4-probar-la-prueba-gratuita-y-el-pago-ficticio-freemius-en-sandbox)).

### Opción B — Copiar la carpeta a mano (XAMPP)

1. Descomprime el ZIP. Debe salir **una sola carpeta** llamada `ai-content-assistant` que contiene directamente `ai-content-assistant.php`.
2. Cópiala a `C:\xampp\htdocs\prueba\wp-content\plugins\`. La ruta final debe ser:
   `…\wp-content\plugins\ai-content-assistant\ai-content-assistant.php`
   (**no** `…\plugins\ai-content-assistant\ai-content-assistant\…`; si queda duplicada, mueve el contenido un nivel arriba).
3. En el panel, **Plugins → Plugins instalados** → **Activar** «Asistente de IA».

> **Si ya había una versión anterior instalada,** desactívala y bórrala desde **Plugins** antes de instalar esta. No debe haber dos copias del plugin a la vez.

### Configurar la IA

1. Ve a **Ajustes → Asistente IA**.
2. Elige el **Proveedor de IA** (OpenAI o Claude).
3. Pega la **Clave API** (si es de Claude empieza por `sk-ant-`).
4. (Opcional) escribe un **Modelo**; vacío = el modelo por defecto.
5. **Guardar cambios**. Debes ver el cuadro «Versión gratuita: has usado 0 de 5 generaciones gratuitas este mes» y el aviso naranja de **modo prueba**.

### Probarlo

1. **Entradas → Añadir nueva**.
2. En la barra lateral busca la caja **«Asistente de IA (Generador de Contenido)»** (en Gutenberg también puedes insertar el bloque **«Asistente de IA»**).
3. Acción **Generar borrador / artículo**, escribe «Escribe sobre los beneficios del café orgánico» y pulsa **Generar con IA**.
4. Revisa la vista previa y pulsa **Insertar en el editor**.
5. Repite con **Optimizar SEO y títulos** y con **Traducir entrada** (elige idioma en *Traducir a*).

Cada generación correcta suma 1 al contador de «usos este mes» que aparece al pie del panel.

### Ver los errores con detalle (opcional)

Para pruebas conviene activar el modo depuración en `wp-config.php` (antes de la línea *«That's all, stop editing!»*):

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );   // escribe en wp-content/debug.log
define( 'WP_DEBUG_DISPLAY', false );
```

Desactívalo al terminar.

---

## 4. Probar la prueba gratuita y el pago ficticio (Freemius en sandbox)

El plugin ya está en modo prueba, así que **ningún pago es real**. Pero para que la pantalla de **prueba/pago de Freemius** funcione en sandbox, el sitio de pruebas necesita además la **clave secreta (secret key) del producto**:

1. En el panel de Freemius (<https://dashboard.freemius.com>), abre el producto *AI Content Assistant* (ID `42286`) y localiza su **Secret Key** (empieza por `sk_`; suele estar en *Settings → Keys*; solo la ve quien tenga acceso a la cuenta de Freemius).
2. Añádela al `wp-config.php` **del sitio de pruebas** (antes de *«That's all, stop editing!»*):

   ```php
   define( 'WP_FS__ai-content-assistant_SECRET_KEY', 'sk_XXXXXXXXXXXXXXXX' );
   ```
3. Guarda, recarga el panel y ve a **Ajustes → Asistente IA → «Iniciar prueba o comprar licencia»**.

> **Seguridad:** la secret key es un secreto. **No** la pongas dentro del plugin, **no** la subas al repositorio ni la incluyas en el ZIP, y no la pegues en capturas o chats. Va solo en el `wp-config.php` de cada sitio de pruebas.

Qué probar:

| Prueba | Resultado esperado |
|---|---|
| Iniciar la prueba de 7 días (sin tarjeta) | El cuadro de estado pasa a «Licencia o prueba activa: uso ilimitado» y el contador deja de limitar |
| Compra ficticia del plan anual (99 €/año) con la tarjeta de pruebas que indica Freemius | Licencia activa y uso ilimitado |
| Agotar los 5 usos sin licencia | Aviso con enlace «Iniciar prueba o comprar licencia» |

> Que el plan de 99 €/año y la prueba de 7 días existan y estén bien configurados **depende del panel de Freemius**; no se puede comprobar desde el código del plugin.

**Volver a empezar (restablecer el cupo gratuito):** borra la opción `aica_usage`.
Con WP-CLI: `wp option delete aica_usage`. Con phpMyAdmin: elimina la fila `aica_usage` de la tabla `wp_options` (el prefijo `wp_` puede ser otro en tu instalación).

---

## 5. Lista de comprobación tras instalar

| # | Comprobación | Resultado esperado |
|---|---|---|
| 1 | Activar el plugin | Sin errores fatales; aparece **Ajustes → Asistente IA** |
| 2 | Abrir Ajustes → Asistente IA | Aviso naranja de modo prueba y cuadro de estado |
| 3 | Guardar proveedor y clave | Se guarda; la clave ya no se muestra (solo sus últimos 4 caracteres) |
| 4 | Entrada nueva en **Gutenberg** | Aparecen la caja lateral y el bloque «Asistente de IA» |
| 5 | Entrada nueva en **Editor Clásico** (plugin *Classic Editor*) | Aparece la caja lateral |
| 6 | Generar borrador | Vista previa → **Insertar** lo añade al editor |
| 7 | Optimizar SEO | 3 títulos, 1 meta-descripción, 5 palabras clave |
| 8 | Traducir | Traducción manteniendo la estructura del HTML |
| 9 | Contador | Sube 1 por cada generación correcta; a los 5 usos bloquea con el aviso de licencia |
| 10 | Usuario sin permisos de edición | No puede usar el endpoint (error de permisos) |
| 11 | Desactivar y borrar el plugin | Se eliminan la clave, el proveedor, el modelo y el contador |

---

## 6. Problemas frecuentes

| Síntoma | Causa probable y qué hacer |
|---|---|
| **«Falta la clave API del proveedor de IA»** | No hay clave guardada. Ajustes → Asistente IA → pega la clave y guarda. |
| Error con el detalle del proveedor (clave inválida, sin saldo, modelo inexistente…) | Los administradores ven el mensaje del proveedor; el resto, uno genérico. Revisa la clave, el saldo de la cuenta y el nombre del modelo. |
| **Aviso de cupo agotado** | Se han gastado los 5 usos del mes. Inicia la prueba/compra de prueba (sección 4) o restablece `aica_usage` para seguir probando. |
| **«Demasiadas peticiones seguidas. Espera un minuto…»** | Más de 10 peticiones en un minuto desde el mismo usuario. Espera un momento. |
| **«El texto es demasiado largo…»** | La petición supera 4.000 caracteres o el contenido enviado supera 30.000. Acórtalos. |
| **No aparece la caja o el bloque** | Comprueba que el plugin está activo, que editas una *Entrada* o *Página* y que la versión de WordPress es 5.8 o superior. Prueba con *Ctrl + F5* (vaciar caché del navegador) y desactiva plugins de caché. |
| **La pantalla de prueba/pago de Freemius tarda mucho o no carga** | Esa pantalla se monta en el navegador con datos de `wp.freemius.com` y el pago se abre en un *iframe* de `checkout.freemius.com`. Comprueba que el navegador alcanza esos dominios (F12 → pestaña *Red/Network*) y que está definida la secret key de la [sección 4](#4-probar-la-prueba-gratuita-y-el-pago-ficticio-freemius-en-sandbox). |
| **Error fatal de Freemius `count(): Argument #1 … bool given` al activar** | Ocurre cuando el servidor no consigue descargar de `api.freemius.com` los datos de los planes (la respuesta falla y el SDK no la controla en PHP 8). Revisa que el servidor tenga salida a Internet (antivirus, cortafuegos, proxy, IPv6 mal configurado) y vuelve a activar. Si persiste, guarda el mensaje completo. |
| **La generación tarda mucho** | Es normal que tarde unos segundos; el servidor espera a la IA hasta 60 s. Un modelo más ligero (el por defecto) acelera la respuesta. |

---

## 7. Configuración avanzada

### Constantes

| Constante | Dónde | Valor actual | Para qué |
|---|---|---|---|
| `AICA_FS_IS_LIVE` | `ai-content-assistant.php` | `false` | Modo Freemius (`false` = prueba/sandbox) |
| `AICA_FREE_MONTHLY_LIMIT` | `ai-content-assistant.php` | `5` | Usos gratuitos al mes |
| `WP_FS__ai-content-assistant_SECRET_KEY` | `wp-config.php` (opcional, por sitio) | — | Secret key de Freemius para pagos de prueba |

### Filtros

| Filtro | Para qué |
|---|---|
| `aica_free_monthly_limit` | Usos gratuitos al mes |
| `aica_rate_limit` | Peticiones por usuario y minuto |
| `aica_model` | Modelo a usar (`$modelo`, `$proveedor`) |
| `aica_max_tokens` | `max_tokens` de salida para Claude (por defecto 4096) |
| `aica_post_types` | Tipos de contenido que muestran la caja (por defecto `post` y `page`) |

Ejemplo: mostrar la caja también en un tipo de contenido propio (en el `functions.php` del tema hijo o en un plugin propio):

```php
add_filter( 'aica_post_types', function ( $types ) {
    $types[] = 'producto';
    return $types;
} );
```

### API REST

El editor se comunica con el servidor mediante `POST /wp-json/aica/v1/generate` (requiere usuario con `edit_posts` y nonce `X-WP-Nonce`). Parámetros: `action` (`generate` | `seo` | `translate`), `prompt`, `content`, `title`, `language`. Respuesta correcta: `{ success, content, usage }`.

---

## 8. Arquitectura del proyecto

```text
ai-content-assistant/
├── ai-content-assistant.php     # Cabecera, constantes, Freemius (is_live = false), carga de clases
├── README.md
├── includes/
│   ├── class-usage.php          # AICA_Access: licencia/prueba y cupo gratuito mensual
│   ├── class-api-handler.php    # Llamadas HTTP a OpenAI y Claude (wp_remote_post) y prompts
│   ├── class-rest-api.php       # Endpoint REST aica/v1/generate (permisos, validación, límites)
│   ├── class-settings.php       # Página Ajustes > Asistente IA
│   ├── class-meta-box.php       # Caja/botón de la barra lateral (HTML)
│   └── class-editor.php         # Registro de scripts, estilos, textos y bloque de Gutenberg
├── assets/
│   ├── js/aica-common.js        # Petición REST y utilidades compartidas
│   ├── js/aica-metabox.js       # Botón: Editor Clásico (TinyMCE/Texto) y Gutenberg
│   ├── js/aica-block.js         # Bloque de Gutenberg (sin compilación: sin npm ni JSX)
│   └── css/editor.css
├── blocks/ai-assistant/block.json
├── languages/                   # Carpeta para traducciones (textos fuente en español)
└── freemius/                    # SDK oficial de Freemius 2.13.4 (sin modificar)
```

El ZIP debe contener **una única carpeta raíz** `ai-content-assistant/` (su nombre debe coincidir con el *slug* de Freemius) y no incluye pruebas ni archivos de desarrollo.

---

## 9. Seguridad

- Acceso directo bloqueado en todos los archivos (`ABSPATH`).
- Endpoint REST con permiso `edit_posts` y nonce; parámetros validados con listas cerradas de acciones e idiomas y longitudes máximas.
- Límite de 10 peticiones por usuario y minuto.
- Entradas saneadas; la **salida de la IA también se sanea** con `wp_kses_post` antes de llegar al editor.
- Los mensajes de error detallados del proveedor solo los ven los administradores.
- La clave API no se vuelca en el HTML del formulario ni en los datos de JavaScript.
- Al desinstalar el plugin se borran la clave, el proveedor, el modelo y el contador (gancho `after_uninstall` de Freemius).

---

## 10. Estado de verificación

**Verificado con pruebas automáticas (simulación de WordPress en PHP, y el JavaScript en Chromium con un editor simulado):** carga del plugin, modo prueba, conexión con ambos proveedores con respuestas simuladas, cupo gratuito, permisos y errores del endpoint, ajustes, y comportamiento del botón y del bloque.

**Todavía NO verificado (requiere WordPress real, claves reales y acceso a Freemius):**

- Llamadas reales a OpenAI y a Claude con claves válidas.
- Aspecto y funcionamiento del bloque en Gutenberg real y de la caja en el Editor Clásico real.
- Prueba gratuita y compra ficticia en el sandbox de Freemius (necesita la secret key en el sitio de pruebas) y activación de licencia.
- Que el plan de 99 €/año y la prueba de 7 días estén configurados en el panel de Freemius.
- **Pruebas de compatibilidad (Fase 4):** distintas versiones de WordPress (5.8 → actual) y de PHP (7.4 → actual), y conflictos con otros plugins.
- El error fatal de Freemius `count()` visto una vez en XAMPP no se ha podido explicar del todo.

---

## Pasar a modo real

Ahora mismo: `define( 'AICA_FS_IS_LIVE', false );` en `ai-content-assistant.php`.
**No cambiar a `true` hasta que dirección decida vender el producto.** Cuando llegue ese momento:

1. Poner `AICA_FS_IS_LIVE` a `true`.
2. Revisar en el panel de Freemius que el producto, el plan (99 €/año) y la pasarela de cobro están en modo real.
3. Quitar del `wp-config.php` de los sitios de pruebas la secret key de sandbox si ya no se necesita.
4. Generar un ZIP nuevo y repetir las pruebas de compra y activación.

---

## Licencia

Este proyecto se distribuye bajo la licencia **GPLv2 o posterior**.
