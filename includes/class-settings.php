<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AICA_Settings {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_admin_menu() {
        add_options_page(
            'Asistente IA - Ajustes',
            'Asistente IA',
            'manage_options',
            'ai-content-assistant',
            array( $this, 'settings_page_html' )
        );
    }

    public function register_settings() {
        // Registramos la opción limpiando espacios en blanco automáticamente con sanitize_text_field
        register_setting( 
            'aica_options_group', 
            'aica_api_key', 
            array( 'sanitize_callback' => 'sanitize_text_field' ) 
        );

        add_settings_section(
            'aica_main_section',
            'Configuración de la Inteligencia Artificial',
            null,
            'ai-content-assistant'
        );

        add_settings_field(
            'aica_api_key_field',
            'Clave API (OpenAI / Claude)',
            array( $this, 'api_key_field_html' ),
            'ai-content-assistant',
            'aica_main_section'
        );
    }

    public function api_key_field_html() {
        $api_key = get_option( 'aica_api_key', '' );
        echo '<input type="password" name="aica_api_key" value="' . esc_attr( $api_key ) . '" size="50" placeholder="sk-..." autocomplete="off" />';
    }

    public function settings_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'aica_options_group' );
                do_settings_sections( 'ai-content-assistant' );
                submit_button( 'Guardar Cambios' );
                ?>
            </form>
        </div>
        <?php
    }
}