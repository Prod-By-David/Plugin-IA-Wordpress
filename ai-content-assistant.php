<?php
/**
 * Plugin Name: Asistente de IA para WordPress
 * Plugin URI:  https://tuweb.com/
 * Description: Genera, optimiza y traduce contenidos usando modelos de lenguaje avanzados.
 * Version:     1.0.0
 * Author:      David Bravo Bravo
 * License:     GPLv2 or later
 * Text Domain: ai-content-assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Evita acceso directo.
}

// Definir constantes del plugin
define( 'AICA_VERSION', '1.0.0' );
define( 'AICA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// ==========================================
// INTEGRACIÓN DE FREEMIUS SDK
// ==========================================
if ( ! function_exists( 'aica_fs' ) ) {
    // Función contenedora para acceder a Freemius de forma segura
    function aica_fs() {
        global $aica_fs;

        if ( ! isset( $aica_fs ) ) {
            // Incluir el SDK de Freemius utilizando la constante del plugin
            require_once AICA_PLUGIN_DIR . 'freemius/start.php';

            $aica_fs = fs_dynamic_init( array(
                'id'                  => 42286,
                'slug'                => 'ai-content-assistant',
                'type'                => 'plugin',
                'public_key'          => 'pk_c64dfc9e3946c77e81d169f90b619',
                'is_premium'          => true,
                'has_addons'          => false,
                'has_paid_plans'      => true,
                'trial'               => array(
                    'days'            => 7,
                    'is_premium'      => true,
                ),
                'menu'                => array(
                    'slug'            => 'ai-content-assistant',
                    'support'         => false,
                    'parent'          => array(
                        'slug'        => 'options-general.php',
                    ),
                ),
            ) );
        }

        return $aica_fs;
    }

    // Inicializar Freemius
    aica_fs();
    do_action( 'aica_fs_loaded' );
}

// Cargar todas las clases necesarias del plugin
require_once AICA_PLUGIN_DIR . 'includes/class-settings.php';
require_once AICA_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once AICA_PLUGIN_DIR . 'includes/class-meta-box.php';

// Inicializar el plugin y sus componentes
function aica_init() {
    new AICA_Settings();
    new AICA_Rest_API();
    new AICA_Meta_Box();
}
add_action( 'plugins_loaded', 'aica_init' );