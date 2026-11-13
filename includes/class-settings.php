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
        // Registramos la opción con una función de saneamiento personalizada
        register_setting( 
            'aica_options_group', 
            'aica_api_key', 
            array( 
                'sanitize_callback' => array( $this, 'sanitize_api_key' ),
                'default'           => ''
            ) 
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

    /**
     * Saneamiento específico para la API Key en producción.
     */
    public function sanitize_api_key( $input ) {
        $input = sanitize_text_field( $input );
        
        // Opcional: Si el campo se envía vacío pero ya había una clave guardada, 
        // puedes decidir mantener la anterior o dejarla vacía. Aquí la limpiamos si está vacía.
        if ( empty( $input ) ) {
            return '';
        }

        return $input;
    }

    public function api_key_field_html() {
        $api_key = get_option( 'aica_api_key', '' );
        // Enmascaramos parcialmente o dejamos el campo de tipo password seguro
        // Para producción, dejar el valor en un input type="password" evita que se lea en pantalla fácilmente.
        ?>
        <input type="password" name="aica_api_key" value="<?php echo esc_attr( $api_key ); ?>" size="50" placeholder="sk-..." autocomplete="new-password" />
        <p class="description">Introduce tu clave secreta de OpenAI o Anthropic. Se almacena de forma segura en la base de datos.</p>
        <?php
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