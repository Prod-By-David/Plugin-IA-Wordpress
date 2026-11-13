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
                // Asegurar que el usuario no solo puede editar posts, sino que está autenticado
                return is_user_logged_in() && current_user_can( 'edit_posts' );
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
        
        // Validar que el parámetro llegue correctamente
        if ( empty( $params ) || ! isset( $params['prompt'] ) ) {
            return new WP_REST_Response( array( 
                'success' => false, 
                'message' => 'Faltan parámetros requeridos en la solicitud.' 
            ), 400 );
        }

        // Usamos sanitize_textarea_field para preservar saltos de línea en prompts largos
        $prompt = sanitize_textarea_field( $params['prompt'] );
        $action = sanitize_text_field( $params['action'] ?? 'generate' );

        if ( empty( trim( $prompt ) ) ) {
            return new WP_REST_Response( array( 
                'success' => false, 
                'message' => 'El prompt no puede estar vacío.' 
            ), 400 );
        }

        // Llamamos al manejador de la API (Modelo BYOK)
        $result = AICA_API_Handler::generate_content( $prompt, $action );

        if ( is_wp_error( $result ) ) {
            $error_code = $result->get_error_code();
            
            // Si el error es por falta de API key o credenciales inválidas, devolvemos un 400 en vez de 500
            $http_status = ( in_array( $error_code, array( 'no_api_key', 'api_error' ), true ) ) ? 400 : 500;

            return new WP_REST_Response( array( 
                'success' => false, 
                'message' => $result->get_error_message() 
            ), $http_status );
        }

        return new WP_REST_Response( array( 
            'success' => true, 
            'content' => $result 
        ), 200 );
    }
}