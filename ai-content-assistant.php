<?php
/**
 * Plugin Name:       Asistente de IA para WordPress
 * Description:       Genera, optimiza y traduce contenidos desde el editor usando modelos de lenguaje (OpenAI y Claude).
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            David Bravo Bravo
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-content-assistant
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Evita acceso directo.
}

/*
 * IMPORTANTE: todo el código está dentro de if/else a propósito.
 * En PHP, las funciones declaradas al nivel superior de un archivo se definen al compilarlo,
 * antes de ejecutar nada; si hubiera dos copias del plugin (p. ej. gratuita y premium),
 * la segunda provocaría "Cannot redeclare". Declaradas dentro del bloque, solo existen si se ejecuta.
 * Es el mismo patrón que usa el snippet oficial de Freemius.
 */
if ( function_exists( 'aica_fs' ) ) {

    // Ya hay otra copia cargada: se deja que Freemius resuelva el conflicto y no se carga nada más.
    aica_fs()->set_basename( true, __FILE__ );

} else {

    // ==========================================
    // CONSTANTES DEL PLUGIN
    // ==========================================
    define( 'AICA_VERSION', '1.0.0' );
    define( 'AICA_PLUGIN_FILE', __FILE__ );
    define( 'AICA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
    define( 'AICA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

    /**
     * MODO PRUEBA DE FREEMIUS (sandbox).
     *
     * false = modo prueba: pagos y licencias de prueba, nada es real.
     * true  = modo real.
     *
     * Instrucción de dirección: dejar en modo prueba hasta que se decida vender el producto.
     * NO cambiar a true sin esa decisión.
     */
    define( 'AICA_FS_IS_LIVE', false );

    /**
     * Usos gratuitos al mes (para sitios sin licencia ni prueba activa).
     * Se puede cambiar aquí o con el filtro `aica_free_monthly_limit`.
     */
    define( 'AICA_FREE_MONTHLY_LIMIT', 5 );

    // ==========================================
    // INTEGRACIÓN DE FREEMIUS SDK
    // ==========================================
    function aica_fs() {
        global $aica_fs;

        if ( ! isset( $aica_fs ) ) {
            // Incluir el SDK de Freemius utilizando la constante del plugin.
            require_once AICA_PLUGIN_DIR . 'freemius/start.php';

            $aica_fs = fs_dynamic_init( array(
                'id'             => 42286,
                'slug'           => 'ai-content-assistant',
                'type'           => 'plugin',
                'public_key'     => 'pk_c64dfc9e3946c77e81d169f90b619',
                'is_premium'     => true,
                'has_addons'     => false,
                'has_paid_plans' => true,
                'is_live'        => AICA_FS_IS_LIVE,
                'trial'          => array(
                    'days'               => 7,
                    'is_require_payment' => false,
                ),
                'menu'           => array(
                    'slug'    => 'ai-content-assistant',
                    'support' => false,
                    'parent'  => array(
                        'slug' => 'options-general.php',
                    ),
                ),
            ) );
        }

        return $aica_fs;
    }

    // Inicializar Freemius.
    aica_fs();
    do_action( 'aica_fs_loaded' );

    /**
     * Limpieza de datos al desinstalar el plugin.
     *
     * No se usa uninstall.php a propósito: si existe, WordPress ignora el hook de desinstalación
     * que registra el SDK de Freemius. Este hook oficial del SDK hace ambas cosas.
     */
    function aica_fs_uninstall_cleanup() {
        delete_option( 'aica_api_key' );
        delete_option( 'aica_provider' );
        delete_option( 'aica_model' );
        delete_option( 'aica_usage' );
    }
    aica_fs()->add_action( 'after_uninstall', 'aica_fs_uninstall_cleanup' );

    // ==========================================
    // CARGA DE CLASES (todas las del directorio includes/)
    // ==========================================
    require_once AICA_PLUGIN_DIR . 'includes/class-usage.php';
    require_once AICA_PLUGIN_DIR . 'includes/class-api-handler.php';
    require_once AICA_PLUGIN_DIR . 'includes/class-settings.php';
    require_once AICA_PLUGIN_DIR . 'includes/class-rest-api.php';
    require_once AICA_PLUGIN_DIR . 'includes/class-meta-box.php';
    require_once AICA_PLUGIN_DIR . 'includes/class-editor.php';

    // Inicializar el plugin y sus componentes.
    function aica_init() {
        load_plugin_textdomain( 'ai-content-assistant', false, dirname( plugin_basename( AICA_PLUGIN_FILE ) ) . '/languages' );

        new AICA_Settings();
        new AICA_Rest_API();
        new AICA_Meta_Box();
        new AICA_Editor();
    }
    add_action( 'plugins_loaded', 'aica_init' );

}
