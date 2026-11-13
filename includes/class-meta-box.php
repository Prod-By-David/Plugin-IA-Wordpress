<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AICA_Meta_Box {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_ai_meta_box' ) );
    }

    public function add_ai_meta_box() {
        add_meta_box(
            'aica_ai_assistant_box',
            'Asistente de IA (Generador de Contenido)',
            array( $this, 'meta_box_html' ),
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_html( $post ) {
        $api_key      = get_option( 'aica_api_key', '' );
        $settings_url = admin_url( 'options-general.php?page=ai-content-assistant' );

        if ( empty( $api_key ) ) {
            ?>
            <div class="notice notice-warning inline" style="margin: 0 0 10px 0; padding: 10px;">
                <p><strong>Clave API no configurada:</strong> Para utilizar el asistente de IA, debes introducir tu clave en los ajustes del plugin.</p>
                <p><a href="<?php echo esc_url( $settings_url ); ?>" class="button button-secondary" target="_blank">Configurar Clave API</a></p>
            </div>
            <?php
            return;
        }
        ?>
        <div id="aica-assistant-wrapper">
            <p><strong>Acción de IA:</strong></p>
            <select id="aica-action-type" style="width:100%; margin-bottom:10px;">
                <option value="generate">Generar Borrador / Artículo</option>
                <option value="seo">Optimizar SEO y Títulos</option>
                <option value="translate">Traducir al Inglés</option>
            </select>

            <p><strong>Petición o Tema:</strong></p>
            <textarea id="aica-prompt-input" rows="3" style="width:100%; margin-bottom:10px;" placeholder="Ej: Escribe sobre los beneficios del café orgánico..."></textarea>

            <button type="button" id="aica-generate-btn" class="button button-primary" style="width:100%;">Generar con IA</button>
            
            <div id="aica-loading" style="display:none; margin-top:10px; text-align:center; color:#007cba;">Generando contenido...</div>
        </div>

        <script>
        document.getElementById('aica-generate-btn').addEventListener('click', function() {
            var promptInput = document.getElementById('aica-prompt-input');
            var actionType = document.getElementById('aica-action-type');
            var loading = document.getElementById('aica-loading');
            var generateBtn = document.getElementById('aica-generate-btn');

            var prompt = promptInput.value.trim();
            var action = actionType.value;

            if (!prompt) {
                alert('Por favor, escribe una petición para el asistente.');
                promptInput.focus();
                return;
            }

            loading.style.display = 'block';
            generateBtn.disabled = true;

            fetch('<?php echo esc_url( rest_url( 'aica/v1/generate' ) ); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
                },
                body: JSON.stringify({ prompt: prompt, action: action })
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                generateBtn.disabled = false;

                if (data.success) {
                    if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
                        if (wp.blocks && typeof wp.blocks.rawHandler === 'function') {
                            var blocks = wp.blocks.rawHandler({ HTML: data.content });
                            wp.data.dispatch('core/editor').insertBlocks(blocks);
                        } else if (wp.blocks && typeof wp.blocks.createBlock === 'function') {
                            var block = wp.blocks.createBlock('core/paragraph', { content: data.content });
                            wp.data.dispatch('core/editor').insertBlocks(block);
                        }
                        alert('¡Contenido generado e insertado con éxito en el editor!');
                    } else {
                        var editorContent = document.getElementById('content');
                        if (editorContent) {
                            editorContent.value += "\n\n" + data.content;
                            if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                                tinymce.get('content').setContent(editorContent.value);
                            }
                            alert('¡Contenido generado y añadido al final del editor clásico!');
                        } else {
                            alert('No se pudo detectar el editor activo para insertar el contenido.');
                        }
                    }
                } else {
                    alert('Error: ' + (data.message || 'Ocurrió un error inesperado.'));
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                generateBtn.disabled = false;
                alert('Error de conexión con el servidor.');
                console.error('AICA Error:', error);
            });
        });
        </script>
        <?php
    }
}