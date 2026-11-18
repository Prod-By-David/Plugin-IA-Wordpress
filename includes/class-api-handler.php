<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Conexión con las APIs de modelos de lenguaje (OpenAI y Anthropic/Claude).
 */
class AICA_API_Handler {

    const OPENAI_URL       = 'https://api.openai.com/v1/chat/completions';
    const ANTHROPIC_URL    = 'https://api.anthropic.com/v1/messages';
    const ANTHROPIC_VERSION = '2023-06-01';

    /**
     * Proveedores disponibles.
     */
    public static function providers() {
        return array(
            'openai'    => __( 'OpenAI (ChatGPT)', 'ai-content-assistant' ),
            'anthropic' => __( 'Anthropic (Claude)', 'ai-content-assistant' ),
        );
    }

    /**
     * Acciones disponibles en el editor.
     */
    public static function actions() {
        return array(
            'generate'  => __( 'Generar borrador / artículo', 'ai-content-assistant' ),
            'seo'       => __( 'Optimizar SEO y títulos', 'ai-content-assistant' ),
            'translate' => __( 'Traducir entrada', 'ai-content-assistant' ),
        );
    }

    /**
     * Idiomas de destino para la traducción (código => nombre).
     */
    public static function languages() {
        return array(
            'en' => 'English',
            'es' => 'Español',
            'fr' => 'Français',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português',
            'ca' => 'Català',
            'nl' => 'Nederlands',
        );
    }

    public static function get_provider() {
        $provider  = (string) get_option( 'aica_provider', 'openai' );
        $providers = self::providers();
        return isset( $providers[ $provider ] ) ? $provider : 'openai';
    }

    public static function default_model( $provider ) {
        return 'anthropic' === $provider ? 'claude-haiku-5-5' : 'gpt-4o-mini';
    }

    public static function get_model( $provider = null ) {
        $provider = $provider ? $provider : self::get_provider();
        $model    = trim( (string) get_option( 'aica_model', '' ) );

        if ( '' === $model ) {
            $model = self::default_model( $provider );
        }

        return (string) apply_filters( 'aica_model', $model, $provider );
    }

    public static function get_api_key() {
        return trim( (string) get_option( 'aica_api_key', '' ) );
    }

    /**
     * Instrucciones de sistema según la acción. Siempre se pide HTML limpio para poder
     * convertirlo en bloques de Gutenberg o insertarlo en el Editor Clásico.
     */
    public static function system_prompt( $action, $language_name = 'English' ) {
        $format = ' Devuelve únicamente HTML limpio y válido (solo etiquetas p, h2, h3, ul, ol, li, strong, em, a, blockquote), sin bloques de código markdown, sin etiquetas html/body y sin comentarios antes ni después del contenido.';

        switch ( $action ) {
            case 'seo':
                return 'Eres un experto en SEO para WordPress. Analiza el contenido o el tema dado y proporciona: 3 meta-títulos optimizados (máximo 60 caracteres cada uno), 1 meta-descripción atractiva (máximo 155 caracteres) y 5 palabras clave sugeridas. Responde en el mismo idioma del contenido, con un h3 por sección y listas ul.' . $format;

            case 'translate':
                return 'Eres un traductor profesional. Traduce el texto proporcionado al idioma: ' . $language_name . '. Mantén un tono natural, comercial y profesional. Conserva exactamente la estructura HTML, las etiquetas, los enlaces (href) y las imágenes; traduce solo el texto visible y los atributos alt y title. Si el texto no contiene HTML, devuélvelo en párrafos p.' . $format;

            case 'generate':
            default:
                return 'Eres un asistente experto en redacción de contenidos SEO para WordPress. Genera un borrador atractivo y bien estructurado, con subtítulos (h2, h3) y párrafos cortos, basado en la petición del usuario. No inventes enlaces ni datos concretos; incluye enlaces solo si el usuario los proporciona.' . $format;
        }
    }

    /**
     * Construye el mensaje del usuario a partir de lo que llega del editor.
     */
    public static function user_message( $action, $prompt, $content = '', $title = '' ) {
        // Los comentarios de bloque de Gutenberg no aportan nada al modelo.
        $content = trim( preg_replace( '/<!--.*?-->/s', '', (string) $content ) );
        $prompt  = trim( (string) $prompt );
        $title   = trim( (string) $title );
        $has_content = '' !== trim( wp_strip_all_tags( $content ) );

        if ( 'translate' === $action ) {
            return $has_content ? $content : $prompt;
        }

        $parts = array();

        if ( '' !== $title && 'generate' !== $action ) {
            $parts[] = 'Título actual: ' . $title;
        }
        if ( '' !== $prompt ) {
            $parts[] = ( 'generate' === $action ? 'Petición: ' : 'Tema o instrucciones: ' ) . $prompt;
        }
        if ( $has_content ) {
            $parts[] = ( 'generate' === $action ? 'Contexto (contenido actual de la entrada):' : 'Contenido:' ) . "\n" . $content;
        }

        return implode( "\n\n", $parts );
    }

    /**
     * Genera contenido con el proveedor y el modelo configurados.
     *
     * @return string|WP_Error HTML saneado, o WP_Error.
     */
    public static function generate_content( $prompt, $action_type = 'generate', $content = '', $language = 'en', $title = '' ) {
        $api_key = self::get_api_key();
        if ( '' === $api_key ) {
            return new WP_Error( 'no_api_key', __( 'Por favor, configura tu clave API en los ajustes del plugin.', 'ai-content-assistant' ) );
        }

        $actions   = self::actions();
        $action    = isset( $actions[ $action_type ] ) ? $action_type : 'generate';
        $languages = self::languages();
        $lang_name = isset( $languages[ $language ] ) ? $languages[ $language ] : 'English';

        $system   = self::system_prompt( $action, $lang_name );
        $user_msg = self::user_message( $action, $prompt, $content, $title );
        $provider = self::get_provider();
        $model    = self::get_model( $provider );

        if ( 'anthropic' === $provider ) {
            $text = self::call_anthropic( $api_key, $model, $system, $user_msg );
        } else {
            $text = self::call_openai( $api_key, $model, $system, $user_msg );
        }

        if ( is_wp_error( $text ) ) {
            return $text;
        }

        $text = self::clean_output( $text );
        if ( '' === $text ) {
            return new WP_Error( 'empty_response', __( 'El modelo no devolvió contenido. Inténtalo de nuevo.', 'ai-content-assistant' ) );
        }

        return $text;
    }

    private static function call_openai( $api_key, $model, $system, $user_msg ) {
        $body = array(
            'model'    => $model,
            'messages' => array(
                array( 'role' => 'system', 'content' => $system ),
                array( 'role' => 'user', 'content' => $user_msg ),
            ),
        );

        $response = wp_remote_post( self::OPENAI_URL, array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( $body ),
        ) );

        $data = self::parse_response( $response );
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        return isset( $data['choices'][0]['message']['content'] ) ? (string) $data['choices'][0]['message']['content'] : '';
    }

    private static function call_anthropic( $api_key, $model, $system, $user_msg ) {
        $body = array(
            'model'      => $model,
            'max_tokens' => (int) apply_filters( 'aica_max_tokens', 4096 ),
            'system'     => $system,
            'messages'   => array(
                array( 'role' => 'user', 'content' => $user_msg ),
            ),
        );

        $response = wp_remote_post( self::ANTHROPIC_URL, array(
            'timeout' => 60,
            'headers' => array(
                'x-api-key'         => $api_key,
                'anthropic-version' => self::ANTHROPIC_VERSION,
                'content-type'      => 'application/json',
            ),
            'body'    => wp_json_encode( $body ),
        ) );

        $data = self::parse_response( $response );
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        // La respuesta de Claude es una lista de bloques; se concatenan los de tipo texto.
        $text = '';
        if ( isset( $data['content'] ) && is_array( $data['content'] ) ) {
            foreach ( $data['content'] as $block ) {
                if ( isset( $block['type'], $block['text'] ) && 'text' === $block['type'] ) {
                    $text .= $block['text'];
                }
            }
        }

        return $text;
    }

    /**
     * Valida la respuesta HTTP y devuelve el JSON decodificado, o un WP_Error.
     */
    private static function parse_response( $response ) {
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status = (int) wp_remote_retrieve_response_code( $response );
        $data   = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! is_array( $data ) ) {
            return new WP_Error(
                'invalid_json',
                __( 'El proveedor de IA devolvió una respuesta no válida o el servidor no responde correctamente.', 'ai-content-assistant' ),
                array( 'http_status' => $status )
            );
        }

        if ( $status < 200 || $status >= 300 ) {
            $message = isset( $data['error']['message'] ) ? (string) $data['error']['message'] : __( 'Error desconocido en la API del proveedor de IA.', 'ai-content-assistant' );
            return new WP_Error( 'api_error', $message, array( 'http_status' => $status ) );
        }

        return $data;
    }

    /**
     * Quita vallas de código markdown y deja solo HTML permitido en entradas.
     */
    public static function clean_output( $text ) {
        $text = trim( (string) $text );
        $text = preg_replace( '/^```[a-zA-Z]*\s*/', '', $text );
        $text = preg_replace( '/\s*```$/', '', $text );

        return trim( wp_kses_post( $text ) );
    }
}
