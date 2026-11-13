<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AICA_Rest_API {

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( 'aica/v1', '/generate', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_generation' ),
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
        ) );
    }

    public function handle_generation( $request ) {
        // 1. Validar la licencia o periodo de prueba con Freemius
        if ( function_exists( 'aica_fs' ) ) {
            if ( ! aica_fs()->can_use_premium_code() ) {
                return new WP_REST_Response( array( 
                    'success' => false, 
                    'message' => 'Se requiere una licencia activa o periodo de prueba de Freemius para utilizar las funciones de IA.' 
                ), 403 );
            }
        }

        $params = $request->get_json_params();
        
        // Usamos sanitize_textarea_field para preservar saltos de línea en prompts largos
        $prompt = sanitize_textarea_field( $params['prompt'] ?? '' );
        $action = sanitize_text_field( $params['action'] ?? 'generate' );

        if ( empty( $prompt ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'El prompt no puede estar vacío.' ), 400 );
        }

        // Llamamos al manejador de la API
        $result = AICA_API_Handler::generate_content( $prompt, $action );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => $result->get_error_message() ), 500 );
        }

        return new WP_REST_Response( array( 'success' => true, 'content' => $result ), 200 );
    }
}