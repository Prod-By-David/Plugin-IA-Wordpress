<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Caja lateral «Asistente de IA» (botón) para el Editor Clásico y Gutenberg.
 * El JavaScript vive en assets/js/aica-metabox.js (se carga con wp_enqueue_script).
 */
class AICA_Meta_Box {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_ai_meta_box' ) );
    }

    /**
     * Tipos de contenido donde aparece la caja (filtrable con `aica_post_types`).
     */
    public static function post_types() {
        $types = apply_filters( 'aica_post_types', array( 'post', 'page' ) );
        return is_array( $types ) ? array_values( $types ) : array( 'post' );
    }

    public function add_ai_meta_box() {
        add_meta_box(
            'aica_ai_assistant_box',
            __( 'Asistente de IA (Generador de Contenido)', 'ai-content-assistant' ),
            array( $this, 'meta_box_html' ),
            self::post_types(),
            'side',
            'high'
        );
    }

    public function meta_box_html( $post ) {
        ?>
        <div id="aica-assistant-wrapper" class="aica-box">
            <p><label for="aica-action-type"><strong><?php esc_html_e( 'Acción de IA:', 'ai-content-assistant' ); ?></strong></label></p>
            <select id="aica-action-type" class="aica-field">
                <?php foreach ( AICA_API_Handler::actions() as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>

            <div id="aica-language-row" hidden>
                <p><label for="aica-language"><strong><?php esc_html_e( 'Traducir a:', 'ai-content-assistant' ); ?></strong></label></p>
                <select id="aica-language" class="aica-field">
                    <?php foreach ( AICA_API_Handler::languages() as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <p><label for="aica-prompt-input"><strong><?php esc_html_e( 'Petición o tema:', 'ai-content-assistant' ); ?></strong></label></p>
            <textarea id="aica-prompt-input" rows="3" class="aica-field" placeholder="<?php esc_attr_e( 'Ej: Escribe sobre los beneficios del café orgánico...', 'ai-content-assistant' ); ?>"></textarea>

            <p><label class="aica-check"><input type="checkbox" id="aica-use-content" /> <?php esc_html_e( 'Usar el contenido actual de la entrada', 'ai-content-assistant' ); ?></label></p>

            <p><button type="button" id="aica-generate-btn" class="button button-primary aica-full"><?php esc_html_e( 'Generar con IA', 'ai-content-assistant' ); ?></button></p>

            <div id="aica-loading" class="aica-loading" role="status" hidden><?php esc_html_e( 'Generando contenido...', 'ai-content-assistant' ); ?></div>
            <div id="aica-message" class="aica-message" role="alert" hidden></div>

            <div id="aica-result-wrap" hidden>
                <p><strong><?php esc_html_e( 'Resultado:', 'ai-content-assistant' ); ?></strong></p>
                <div id="aica-result" class="aica-preview"></div>
                <p>
                    <button type="button" id="aica-insert-btn" class="button button-secondary"><?php esc_html_e( 'Insertar en el editor', 'ai-content-assistant' ); ?></button>
                    <button type="button" id="aica-copy-btn" class="button"><?php esc_html_e( 'Copiar HTML', 'ai-content-assistant' ); ?></button>
                </p>
            </div>

            <p id="aica-usage" class="description"></p>
        </div>
        <?php
    }
}
