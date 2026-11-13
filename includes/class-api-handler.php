<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AICA_API_Handler {

    public static function generate_content( $prompt, $action_type = 'generate' ) {
        $api_key = get_option( 'aica_api_key', '' );
        
        // Validación BYOK: Asegurar que el usuario configuró su clave
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', 'Por favor, configura tu propia clave API en los ajustes del plugin para poder utilizar el asistente.' );
        }

        // Limpiar espacios accidentales en la clave
        $api_key = trim( $api_key );

        // Sistema de Caché (Transients) para evitar llamadas duplicadas y ahorrar tokens
        $cache_key   = 'aica_cache_' . md5( $action_type . '_' . $prompt );
        $cached_resp = get_transient( $cache_key );
        if ( false !== $cached_resp ) {
            return $cached_resp;
        }

        $url = 'https://api.openai.com/v1/chat/completions';
        
        // Definir instrucciones de sistema según la acción elegida
        $system_prompts = array(
            'generate'  => 'Eres un asistente experto en redacción de contenidos SEO para WordPress. Genera un borrador atractivo, bien estructurado con subtítulos (H2, H3) basado en la petición del usuario.',
            'seo'       => 'Eres un experto en SEO para WordPress. Analiza el tema dado y proporciona sugerencias de meta-títulos optimizados y una meta-descripción atractiva.',
            'translate' => 'Eres un traductor profesional. Traduce el texto proporcionado al inglés manteniendo un tono natural, comercial y profesional.'
        );

        $system_message = isset( $system_prompts[ $action_type ] ) ? $system_prompts[ $action_type ] : $system_prompts['generate'];

        $body = array(
            'model'       => 'gpt-4o-mini',
            'messages'    => array(
                array( 'role' => 'system', 'content' => $system_message ),
                array( 'role' => 'user', 'content' => $prompt )
            ),
            'temperature' => 0.7,
            'max_tokens'  => 2048,
        );

        $args = array(
            'body'        => json_encode( $body ),
            'headers'     => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'timeout'     => 45,
            'data_format' => 'body',
        );

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
            error_log( 'AICA Error de Conexión HTTP (BYOK): ' . $response->get_error_message() );
            return new WP_Error( 'connection_error', 'Error de conexión con los servidores de OpenAI. Inténtalo de nuevo más tarde.' );
        }

        $status_code   = wp_remote_retrieve_response_code( $response );
        $body_raw      = wp_remote_retrieve_body( $response );
        $body_response = json_decode( $body_raw, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            error_log( 'AICA Error: JSON inválido recibido de OpenAI. Respuesta cruda: ' . $body_raw );
            return new WP_Error( 'invalid_json', 'OpenAI devolvió una respuesta no válida o el servidor no responde correctamente.' );
        }

        if ( $status_code !== 200 ) {
            $error_message = isset( $body_response['error']['message'] ) ? $body_response['error']['message'] : 'Error desconocido en la API de OpenAI.';
            
            if ( $status_code === 401 ) {
                $error_message = 'La clave API introducida es incorrecta o ha caducado. Por favor, verifica tus credenciales en los ajustes.';
            }

            error_log( 'AICA Error HTTP ' . $status_code . ' (BYOK): ' . $error_message );
            return new WP_Error( 'api_error', $error_message );
        }

        $content = isset( $body_response['choices'][0]['message']['content'] ) ? trim( $body_response['choices'][0]['message']['content'] ) : '';

        if ( empty( $content ) ) {
            return new WP_Error( 'empty_response', 'La IA devolvió una respuesta vacía. Es posible que el contenido haya sido filtrado o la solicitud sea demasiado ambigua.' );
        }

        // Guardar en caché durante 12 horas para optimizar peticiones idénticas
        set_transient( $cache_key, $content, 12 * HOUR_IN_SECONDS );

        return $content;
    }
}