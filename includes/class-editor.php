<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registra los scripts del editor (Clásico y Gutenberg) y el bloque «Asistente de IA».
 *
 * Los scripts son JavaScript sin paso de compilación (no requiere npm), cargados con wp_enqueue_script.
 */
class AICA_Editor {

    private static $localized = false;

    public function __construct() {
        add_action( 'init', array( $this, 'register_assets_and_block' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_metabox_assets' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
    }

    public function register_assets_and_block() {
        wp_register_script( 'aica-common', AICA_PLUGIN_URL . 'assets/js/aica-common.js', array(), AICA_VERSION, true );
        wp_register_script(
            'aica-block-editor',
            AICA_PLUGIN_URL . 'assets/js/aica-block.js',
            array( 'aica-common', 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-data' ),
            AICA_VERSION,
            true
        );
        wp_register_style( 'aica-editor', AICA_PLUGIN_URL . 'assets/css/editor.css', array(), AICA_VERSION );

        if ( function_exists( 'register_block_type' ) ) {
            register_block_type( AICA_PLUGIN_DIR . 'blocks/ai-assistant', array(
                // Bloque solo de editor: en la web pública no pinta nada.
                'render_callback' => '__return_empty_string',
            ) );
        }
    }

    /**
     * Meta box (botón en la barra lateral): Editor Clásico y también Gutenberg.
     */
    public function enqueue_metabox_assets( $hook_suffix ) {
        if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || ! in_array( $screen->post_type, AICA_Meta_Box::post_types(), true ) ) {
            return;
        }

        $deps = array( 'aica-common' );
        if ( method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) {
            // En Gutenberg el botón inserta bloques y lee el contenido mediante wp.data.
            $deps[] = 'wp-data';
            $deps[] = 'wp-blocks';
        }

        wp_enqueue_script( 'aica-metabox', AICA_PLUGIN_URL . 'assets/js/aica-metabox.js', $deps, AICA_VERSION, true );
        wp_enqueue_style( 'aica-editor' );
        $this->localize();
    }

    /**
     * Bloque de Gutenberg (WordPress carga su script de editor al estar registrado).
     */
    public function enqueue_block_editor_assets() {
        wp_enqueue_style( 'aica-editor' );
        $this->localize();
    }

    private function localize() {
        if ( self::$localized ) {
            return;
        }
        self::$localized = true;
        wp_localize_script( 'aica-common', 'aicaData', self::script_data() );
    }

    /**
     * Datos y textos que usa el JavaScript (los textos pasan por __() para poder traducirlos).
     */
    public static function script_data() {
        $actions = array();
        foreach ( AICA_API_Handler::actions() as $value => $label ) {
            $actions[] = array( 'value' => $value, 'label' => $label );
        }

        $languages = array();
        foreach ( AICA_API_Handler::languages() as $value => $label ) {
            $languages[] = array( 'value' => $value, 'label' => $label );
        }

        return array(
            'restUrl'     => esc_url_raw( rest_url( 'aica/v1/generate' ) ),
            'nonce'       => wp_create_nonce( 'wp_rest' ),
            'settingsUrl' => admin_url( 'options-general.php?page=ai-content-assistant' ),
            'canManage'   => current_user_can( 'manage_options' ),
            'hasKey'      => '' !== AICA_API_Handler::get_api_key(),
            'actions'     => $actions,
            'languages'   => $languages,
            'usage'       => AICA_Access::status(),
            'i18n'        => array(
                'emptyPrompt'         => __( 'Por favor, escribe una petición.', 'ai-content-assistant' ),
                'emptyText'           => __( 'Escribe un texto o marca «Usar el contenido actual de la entrada».', 'ai-content-assistant' ),
                'errorGeneric'        => __( 'No se pudo generar el contenido.', 'ai-content-assistant' ),
                'errorNetwork'        => __( 'Error de conexión con el servidor.', 'ai-content-assistant' ),
                'noKey'               => __( 'Falta la clave API del proveedor de IA.', 'ai-content-assistant' ),
                'configure'           => __( 'Configurar', 'ai-content-assistant' ),
                'upgrade'             => __( 'Iniciar prueba o comprar licencia', 'ai-content-assistant' ),
                'inserted'            => __( '¡Contenido insertado en el editor!', 'ai-content-assistant' ),
                'insertFailed'        => __( 'No se pudo insertar el contenido en el editor.', 'ai-content-assistant' ),
                'copied'              => __( 'Copiado al portapapeles.', 'ai-content-assistant' ),
                'copyFailed'          => __( 'No se pudo copiar.', 'ai-content-assistant' ),
                'usageUnlimited'      => __( 'Licencia o prueba activa: uso ilimitado.', 'ai-content-assistant' ),
                'usageFree'           => __( 'Versión gratuita: {used} de {limit} usos este mes.', 'ai-content-assistant' ),
                'placeholderGenerate' => __( 'Ej: Escribe sobre los beneficios del café orgánico...', 'ai-content-assistant' ),
                'placeholderOther'    => __( 'Opcional: instrucciones o texto a procesar', 'ai-content-assistant' ),
                'blockTitle'          => __( 'Asistente de IA', 'ai-content-assistant' ),
                'blockDescription'    => __( 'Genera, optimiza el SEO o traduce contenido con IA sin salir del editor.', 'ai-content-assistant' ),
                'actionLabel'         => __( 'Acción de IA', 'ai-content-assistant' ),
                'languageLabel'       => __( 'Traducir a', 'ai-content-assistant' ),
                'promptLabel'         => __( 'Petición o tema', 'ai-content-assistant' ),
                'useContentLabel'     => __( 'Usar el contenido actual de la entrada', 'ai-content-assistant' ),
                'generateBtn'         => __( 'Generar con IA', 'ai-content-assistant' ),
                'replaceBtn'          => __( 'Insertar y reemplazar este bloque', 'ai-content-assistant' ),
                'copyBtn'             => __( 'Copiar HTML', 'ai-content-assistant' ),
            ),
        );
    }
}
