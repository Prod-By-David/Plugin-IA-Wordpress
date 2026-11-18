<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Control de acceso y límites.
 *
 * - Con licencia o prueba gratuita activa de Freemius: uso ilimitado.
 * - Sin licencia: versión gratuita con un máximo de usos al mes (AICA_FREE_MONTHLY_LIMIT).
 */
class AICA_Access {

    const OPTION = 'aica_usage';

    /**
     * ¿El sitio tiene licencia o prueba activa en Freemius?
     */
    public static function has_license() {
        return function_exists( 'aica_fs' ) && (bool) aica_fs()->can_use_premium_code();
    }

    /**
     * Usos gratuitos permitidos al mes.
     */
    public static function free_limit() {
        $limit = (int) apply_filters( 'aica_free_monthly_limit', AICA_FREE_MONTHLY_LIMIT );
        return max( 0, $limit );
    }

    private static function current_month() {
        return gmdate( 'Y-m' );
    }

    /**
     * Usos gratuitos consumidos en el mes actual (el contador se reinicia solo cada mes).
     */
    public static function used() {
        $usage = get_option( self::OPTION, array() );

        if ( ! is_array( $usage ) || ( isset( $usage['month'] ) ? $usage['month'] : '' ) !== self::current_month() ) {
            return 0;
        }

        return max( 0, (int) ( isset( $usage['count'] ) ? $usage['count'] : 0 ) );
    }

    public static function remaining() {
        return max( 0, self::free_limit() - self::used() );
    }

    public static function can_generate() {
        return self::has_license() || self::remaining() > 0;
    }

    /**
     * Registra un uso. Solo cuenta en versión gratuita.
     */
    public static function consume() {
        if ( self::has_license() ) {
            return;
        }

        update_option(
            self::OPTION,
            array(
                'month' => self::current_month(),
                'count' => self::used() + 1,
            ),
            false
        );
    }

    /**
     * URL de la página de precios/compra de Freemius.
     */
    public static function upgrade_url() {
        return function_exists( 'aica_fs' ) ? (string) aica_fs()->get_upgrade_url() : '';
    }

    /**
     * Estado para la interfaz.
     */
    public static function status() {
        return array(
            'licensed'    => self::has_license(),
            'limit'       => self::free_limit(),
            'used'        => self::used(),
            'remaining'   => self::remaining(),
            'upgrade_url' => self::upgrade_url(),
        );
    }
}
