<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AICA_Settings {

    const PAGE  = 'ai-content-assistant';
    const GROUP = 'aica_options_group';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_admin_menu() {
        add_options_page(
            __( 'Asistente IA - Ajustes', 'ai-content-assistant' ),
            __( 'Asistente IA', 'ai-content-assistant' ),
            'manage_options',
            self::PAGE,
            array( $this, 'settings_page_html' )
        );
    }

    public function register_settings() {
        register_setting( self::GROUP, 'aica_provider', array(
            'type'              => 'string',
            'default'           => 'openai',
            'sanitize_callback' => array( $this, 'sanitize_provider' ),
        ) );
        register_setting( self::GROUP, 'aica_api_key', array(
            'type'              => 'string',
            'default'           => '',
            'sanitize_callback' => array( $this, 'sanitize_api_key' ),
        ) );
        register_setting( self::GROUP, 'aica_model', array(
            'type'              => 'string',
            'default'           => '',
            'sanitize_callback' => array( $this, 'sanitize_model' ),
        ) );

        add_settings_section(
            'aica_main_section',
            __( 'Configuración de la Inteligencia Artificial', 'ai-content-assistant' ),
            null,
            self::PAGE
        );

        add_settings_field( 'aica_provider_field', __( 'Proveedor de IA', 'ai-content-assistant' ), array( $this, 'provider_field_html' ), self::PAGE, 'aica_main_section', array( 'label_for' => 'aica_provider' ) );
        add_settings_field( 'aica_api_key_field', __( 'Clave API', 'ai-content-assistant' ), array( $this, 'api_key_field_html' ), self::PAGE, 'aica_main_section', array( 'label_for' => 'aica_api_key' ) );
        add_settings_field( 'aica_model_field', __( 'Modelo (opcional)', 'ai-content-assistant' ), array( $this, 'model_field_html' ), self::PAGE, 'aica_main_section', array( 'label_for' => 'aica_model' ) );
    }

    // ------------------------------------------------------------------
    // Saneamiento
    // ------------------------------------------------------------------

    public function sanitize_provider( $value ) {
        $value = sanitize_key( (string) $value );
        return array_key_exists( $value, AICA_API_Handler::providers() ) ? $value : 'openai';
    }

    /**
     * La clave guardada no se vuelve a mostrar en el formulario: si el campo llega vacío se conserva la actual.
     */
    public function sanitize_api_key( $value ) {
        $old = (string) get_option( 'aica_api_key', '' );

        // options.php ya ha verificado el nonce de settings_fields() antes de llegar aquí.
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        if ( ! empty( $_POST['aica_remove_key'] ) ) {
            return '';
        }

        $value = trim( sanitize_text_field( (string) $value ) );
        if ( '' === $value ) {
            return $old;
        }

        $provider = isset( $_POST['aica_provider'] )
            ? sanitize_key( wp_unslash( $_POST['aica_provider'] ) )
            : (string) get_option( 'aica_provider', 'openai' );
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        $is_anthropic_key = 0 === strpos( $value, 'sk-ant-' );

        if ( 'anthropic' === $provider && ! $is_anthropic_key ) {
            add_settings_error( 'aica_api_key', 'aica_key_mismatch', __( 'Has elegido Claude, pero la clave no parece de Anthropic (suelen empezar por «sk-ant-»). Comprueba que es correcta.', 'ai-content-assistant' ), 'warning' );
        } elseif ( 'openai' === $provider && $is_anthropic_key ) {
            add_settings_error( 'aica_api_key', 'aica_key_mismatch', __( 'Has elegido OpenAI, pero la clave parece de Anthropic (Claude). Cambia el proveedor o revisa la clave.', 'ai-content-assistant' ), 'warning' );
        }

        return $value;
    }

    public function sanitize_model( $value ) {
        $value = trim( sanitize_text_field( (string) $value ) );

        if ( ! preg_match( '/^[A-Za-z0-9._:\/-]{0,100}$/', $value ) ) {
            add_settings_error( 'aica_model', 'aica_model_invalid', __( 'El nombre del modelo no es válido; se usará el modelo por defecto.', 'ai-content-assistant' ) );
            return '';
        }

        return $value;
    }

    // ------------------------------------------------------------------
    // Campos
    // ------------------------------------------------------------------

    public function provider_field_html() {
        $current = AICA_API_Handler::get_provider();

        echo '<select id="aica_provider" name="aica_provider">';
        foreach ( AICA_API_Handler::providers() as $value => $label ) {
            printf( '<option value="%s"%s>%s</option>', esc_attr( $value ), selected( $current, $value, false ), esc_html( $label ) );
        }
        echo '</select>';
    }

    public function api_key_field_html() {
        $key = AICA_API_Handler::get_api_key();

        echo '<input type="password" id="aica_api_key" name="aica_api_key" value="" size="50" placeholder="sk-..." autocomplete="new-password" />';

        if ( '' !== $key ) {
            echo '<p class="description">' . esc_html(
                sprintf(
                    /* translators: %s: últimos 4 caracteres de la clave */
                    __( 'Hay una clave guardada (termina en «%s»). Déjalo vacío para conservarla o escribe una nueva para sustituirla.', 'ai-content-assistant' ),
                    substr( $key, -4 )
                )
            ) . '</p>';
            echo '<p><label><input type="checkbox" name="aica_remove_key" value="1" /> ' . esc_html__( 'Eliminar la clave guardada', 'ai-content-assistant' ) . '</label></p>';
        } else {
            echo '<p class="description">' . esc_html__( 'Introduce la clave API del proveedor elegido. Se guarda en la base de datos de este sitio y no se vuelve a mostrar.', 'ai-content-assistant' ) . '</p>';
        }
    }

    public function model_field_html() {
        $model = (string) get_option( 'aica_model', '' );

        printf(
            '<input type="text" id="aica_model" name="aica_model" value="%s" size="30" placeholder="%s" />',
            esc_attr( $model ),
            esc_attr( AICA_API_Handler::default_model( AICA_API_Handler::get_provider() ) )
        );
        echo '<p class="description">' . esc_html(
            sprintf(
                /* translators: 1: modelo por defecto de OpenAI, 2: modelo por defecto de Claude */
                __( 'Déjalo vacío para usar el modelo por defecto (OpenAI: %1$s · Claude: %2$s).', 'ai-content-assistant' ),
                AICA_API_Handler::default_model( 'openai' ),
                AICA_API_Handler::default_model( 'anthropic' )
            )
        ) . '</p>';
    }

    // ------------------------------------------------------------------
    // Página
    // ------------------------------------------------------------------

    private function render_status_box() {
        $status  = AICA_Access::status();
        $is_test = function_exists( 'aica_fs' ) && ! aica_fs()->is_live();

        if ( $is_test ) {
            echo '<div class="notice notice-warning inline"><p><strong>' . esc_html__( 'Freemius en MODO PRUEBA (sandbox).', 'ai-content-assistant' ) . '</strong> '
                . esc_html__( 'Los pagos y las licencias son de prueba; nada es real.', 'ai-content-assistant' ) . '</p></div>';
        }

        echo '<div class="notice notice-info inline"><p>';
        if ( $status['licensed'] ) {
            echo '<strong>' . esc_html__( 'Licencia o prueba activa:', 'ai-content-assistant' ) . '</strong> ' . esc_html__( 'uso ilimitado de las funciones de IA.', 'ai-content-assistant' );
        } else {
            echo '<strong>' . esc_html__( 'Versión gratuita:', 'ai-content-assistant' ) . '</strong> ' . esc_html(
                sprintf(
                    /* translators: 1: usos consumidos, 2: usos gratuitos al mes */
                    __( 'has usado %1$d de %2$d generaciones gratuitas este mes.', 'ai-content-assistant' ),
                    $status['used'],
                    $status['limit']
                )
            );
            if ( '' !== $status['upgrade_url'] ) {
                echo ' <a href="' . esc_url( $status['upgrade_url'] ) . '">' . esc_html__( 'Iniciar prueba o comprar licencia', 'ai-content-assistant' ) . '</a>';
            }
        }
        echo '</p></div>';
    }

    public function settings_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <?php $this->render_status_box(); ?>
            <form action="options.php" method="post">
                <?php
                settings_fields( self::GROUP );
                do_settings_sections( self::PAGE );
                submit_button( __( 'Guardar Cambios', 'ai-content-assistant' ) );
                ?>
            </form>
        </div>
        <?php
    }
}
