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
            var prompt = document.getElementById('aica-prompt-input').value;
            var action = document.getElementById('aica-action-type').value;
            var loading = document.getElementById('aica-loading');

            if (!prompt) {
                alert('Por favor, escribe una petición.');
                return;
            }

            loading.style.display = 'block';

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
                if (data.success) {
                    // Si Gutenberg está activo, convertimos el contenido estructurado en bloques nativos
                    if (wp.data && wp.data.dispatch('core/editor')) {
                        if (wp.blocks && wp.blocks.rawHandler) {
                            var blocks = wp.blocks.rawHandler({ HTML: data.content });
                            wp.data.dispatch('core/editor').insertBlocks(blocks);
                        } else {
                            var block = wp.blocks.createBlock('core/paragraph', { content: data.content });
                            wp.data.dispatch('core/editor').insertBlocks(block);
                        }
                        alert('¡Contenido generado e insertado con éxito en el editor!');
                    } else {
                        // Fallback para el Editor Clásico
                        var editorContent = document.getElementById('content');
                        if (editorContent) {
                            editorContent.value += "\n\n" + data.content;
                            alert('¡Contenido generado y añadido al final!');
                        }
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                alert('Error de conexión con el servidor.');
                console.error(error);
            });
        });
        </script>
        <?php
    }
}