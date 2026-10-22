<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AICA_API_Handler {

    public static function generate_content( $prompt, $action_type = 'generate' ) {
        $api_key = get_option( 'aica_api_key', '' );
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', 'Por favor, configura tu clave API en los ajustes del plugin.' );
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
            'model' => 'gpt-4o-mini', // Modelo rápido y económico ideal para plugins
            'messages' => array(
                array( 'role' => 'system', 'content' => $system_message ),
                array( 'role' => 'user', 'content' => $prompt )
            ),
            'temperature' => 0.7,
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
            return $response;
        }

        $status_code   = wp_remote_retrieve_response_code( $response );
        $body_raw      = wp_remote_retrieve_body( $response );
        $body_response = json_decode( $body_raw, true );

        // Verificar si la respuesta no es un JSON válido
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'invalid_json', 'La API de OpenAI devolvió una respuesta no válida o el servidor no responde correctamente.' );
        }

        if ( $status_code !== 200 ) {
            $error_message = isset( $body_response['error']['message'] ) ? $body_response['error']['message'] : 'Error desconocido en la API de OpenAI.';
            return new WP_Error( 'api_error', $error_message );
        }

        return isset( $body_response['choices'][0]['message']['content'] ) ? trim( $body_response['choices'][0]['message']['content'] ) : '';
    }
}