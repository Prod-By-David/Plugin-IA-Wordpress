<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AICA_Rest_API {

    const MAX_PROMPT  = 4000;
    const MAX_CONTENT = 30000;
    const RATE_WINDOW = 60; // segundos.

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( 'aica/v1', '/generate', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_generation' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'action'   => array(
                    'type'    => 'string',
                    'default' => 'generate',
                    'enum'    => array_keys( AICA_API_Handler::actions() ),
                ),
                'prompt'   => array(
                    'type'              => 'string',
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
                // El contenido conserva su HTML (se limpia con wp_kses_post), p. ej. para traducir una entrada completa.
                'content'  => array(
                    'type'              => 'string',
                    'default'           => '',
                    'sanitize_callback' => 'wp_kses_post',
                ),
                'title'    => array(
                    'type'              => 'string',
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'language' => array(
                    'type'    => 'string',
                    'default' => 'en',
                    'enum'    => array_keys( AICA_API_Handler::languages() ),
                ),
            ),
        ) );
    }

    public function check_permission() {
        return current_user_can( 'edit_posts' );
    }

    public function handle_generation( $request ) {
        // 0. Límite de peticiones por usuario (protege la clave API y el coste).
        if ( $this->is_rate_limited( get_current_user_id() ) ) {
            return $this->error( 'aica_rate_limited', __( 'Demasiadas peticiones seguidas. Espera un minuto e inténtalo de nuevo.', 'ai-content-assistant' ), 429 );
        }

        // 1. Licencia / prueba de Freemius, o cupo gratuito mensual.
        if ( ! AICA_Access::can_generate() ) {
            return $this->error(
                'aica_limit_reached',
                sprintf(
                    /* translators: %d: usos gratuitos al mes */
                    __( 'Has agotado los %d usos gratuitos de este mes. Inicia la prueba gratuita o activa una licencia para usar la IA sin límites.', 'ai-content-assistant' ),
                    AICA_Access::free_limit()
                ),
                403,
                array( 'upgrade_url' => AICA_Access::upgrade_url() )
            );
        }

        // 2. Entradas (ya validadas y saneadas por el esquema de la ruta).
        $action   = (string) $request->get_param( 'action' );
        $prompt   = trim( (string) $request->get_param( 'prompt' ) );
        $content  = (string) $request->get_param( 'content' );
        $title    = (string) $request->get_param( 'title' );
        $language = (string) $request->get_param( 'language' );

        if ( $this->strlen( $prompt ) > self::MAX_PROMPT || $this->strlen( $content ) > self::MAX_CONTENT ) {
            return $this->error( 'aica_too_long', __( 'El texto es demasiado largo. Reduce la petición o el contenido.', 'ai-content-assistant' ), 400 );
        }

        $has_content = '' !== trim( wp_strip_all_tags( preg_replace( '/<!--.*?-->/s', '', $content ) ) );

        if ( 'generate' === $action && '' === $prompt ) {
            return $this->error( 'aica_empty_prompt', __( 'El prompt no puede estar vacío.', 'ai-content-assistant' ), 400 );
        }
        if ( 'generate' !== $action && '' === $prompt && ! $has_content ) {
            return $this->error( 'aica_empty_text', __( 'Escribe un texto o usa el contenido de la entrada.', 'ai-content-assistant' ), 400 );
        }

        // 3. Llamada al proveedor de IA.
        $result = AICA_API_Handler::generate_content( $prompt, $action, $content, $language, $title );

        if ( is_wp_error( $result ) ) {
            $is_admin = current_user_can( 'manage_options' );

            if ( 'no_api_key' === $result->get_error_code() ) {
                return $this->error(
                    'aica_no_api_key',
                    $is_admin ? $result->get_error_message() : __( 'El administrador del sitio debe configurar la clave API del plugin.', 'ai-content-assistant' ),
                    400,
                    array( 'settings_url' => $is_admin ? admin_url( 'options-general.php?page=ai-content-assistant' ) : '' )
                );
            }

            // El detalle del proveedor (que puede incluir datos de la cuenta) solo se muestra a administradores.
            return $this->error(
                'aica_provider_error',
                $is_admin ? $result->get_error_message() : __( 'No se pudo completar la petición con el proveedor de IA. Inténtalo de nuevo o avisa al administrador.', 'ai-content-assistant' ),
                502
            );
        }

        AICA_Access::consume();

        return new WP_REST_Response( array(
            'success' => true,
            'content' => $result,
            'usage'   => AICA_Access::status(),
        ), 200 );
    }

    private function error( $code, $message, $status, $extra = array() ) {
        return new WP_REST_Response( array_merge(
            array(
                'success' => false,
                'code'    => $code,
                'message' => $message,
                'usage'   => AICA_Access::status(),
            ),
            $extra
        ), $status );
    }

    private function strlen( $text ) {
        return function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text );
    }

    /**
     * Máximo de peticiones por usuario y minuto (ajustable con el filtro `aica_rate_limit`).
     */
    private function is_rate_limited( $user_id ) {
        $max   = max( 1, (int) apply_filters( 'aica_rate_limit', 10 ) );
        $key   = 'aica_rl_' . (int) $user_id;
        $now   = time();
        $state = get_transient( $key );

        if ( ! is_array( $state ) || ( $now - (int) $state['start'] ) >= self::RATE_WINDOW ) {
            $state = array( 'start' => $now, 'count' => 0 );
        }

        if ( $state['count'] >= $max ) {
            return true;
        }

        $state['count']++;
        set_transient( $key, $state, self::RATE_WINDOW );

        return false;
    }
}
