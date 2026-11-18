/**
 * Asistente de IA - bloque de Gutenberg «aica/ai-assistant».
 *
 * Escrito con wp.element.createElement para no necesitar compilación (sin npm/JSX).
 * Al pulsar «Insertar», el bloque se reemplaza por los bloques generados.
 */
( function ( wp, AICA ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.element || ! wp.components || ! AICA ) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var c = wp.components;
	var t = AICA.t;
	var data = AICA.data;

	function Edit( props ) {
		var blockProps = wp.blockEditor && wp.blockEditor.useBlockProps ? wp.blockEditor.useBlockProps( { className: 'aica-block' } ) : { className: 'aica-block' };

		var _action = useState( 'generate' );
		var _language = useState( 'en' );
		var _prompt = useState( '' );
		var _useContent = useState( false );
		var _busy = useState( false );
		var _error = useState( null );
		var _result = useState( '' );
		var _usage = useState( data.usage );

		var action = _action[ 0 ];
		var language = _language[ 0 ];
		var prompt = _prompt[ 0 ];
		var useContent = _useContent[ 0 ];
		var busy = _busy[ 0 ];
		var error = _error[ 0 ];
		var result = _result[ 0 ];
		var usage = _usage[ 0 ];

		function changeAction( value ) {
			_action[ 1 ]( value );
			_useContent[ 1 ]( 'generate' !== value ); // Por defecto, SEO y traducir trabajan sobre la entrada.
		}

		function run() {
			var store = wp.data.select( 'core/editor' );
			var content = '';
			var title = '';

			// Se lee solo al pulsar (no en cada tecla) para no serializar la entrada continuamente.
			if ( useContent && store && store.getEditedPostContent ) {
				content = store.getEditedPostContent() || '';
				title = store.getEditedPostAttribute( 'title' ) || '';
			}

			if ( 'generate' === action && ! prompt.trim() ) {
				_error[ 1 ]( { message: t( 'emptyPrompt' ) } );
				return;
			}
			if ( 'generate' !== action && ! prompt.trim() && ! AICA.textOf( content ).trim() ) {
				_error[ 1 ]( { message: t( 'emptyText' ) } );
				return;
			}

			_busy[ 1 ]( true );
			_error[ 1 ]( null );

			AICA.generate( {
				action: action,
				prompt: prompt.trim(),
				content: content,
				title: title,
				language: language
			} ).then(
				function ( res ) {
					_result[ 1 ]( res.content );
					_usage[ 1 ]( res.usage );
					_busy[ 1 ]( false );
				},
				function ( err ) {
					_error[ 1 ]( err );
					if ( err.usage ) {
						_usage[ 1 ]( err.usage );
					}
					_busy[ 1 ]( false );
				}
			);
		}

		function insert() {
			var blocks = wp.blocks.rawHandler( { HTML: result } );
			if ( ! blocks || ! blocks.length ) {
				blocks = [ wp.blocks.createBlock( 'core/paragraph', { content: result } ) ];
			}
			wp.data.dispatch( 'core/block-editor' ).replaceBlocks( props.clientId, blocks );
		}

		function copy() {
			AICA.copyText( result );
		}

		var errorLink = null;
		if ( error && error.upgradeUrl ) {
			errorLink = el( 'a', { href: error.upgradeUrl, target: '_blank', rel: 'noopener noreferrer' }, t( 'upgrade' ) );
		} else if ( error && error.settingsUrl ) {
			errorLink = el( 'a', { href: error.settingsUrl, target: '_blank', rel: 'noopener noreferrer' }, t( 'configure' ) );
		}

		return el(
			'div',
			blockProps,
			el(
				c.Placeholder,
				{ icon: 'edit', label: t( 'blockTitle' ), className: 'aica-placeholder' },
				el(
					'div',
					{ className: 'aica-block-form' },
					el( c.SelectControl, { label: t( 'actionLabel' ), value: action, options: data.actions, onChange: changeAction } ),
					'translate' === action
						? el( c.SelectControl, { label: t( 'languageLabel' ), value: language, options: data.languages, onChange: _language[ 1 ] } )
						: null,
					el( c.TextareaControl, {
						label: t( 'promptLabel' ),
						value: prompt,
						rows: 3,
						placeholder: 'generate' === action ? t( 'placeholderGenerate' ) : t( 'placeholderOther' ),
						onChange: _prompt[ 1 ]
					} ),
					el( c.CheckboxControl, { label: t( 'useContentLabel' ), checked: useContent, onChange: _useContent[ 1 ] } ),
					el(
						'div',
						{ className: 'aica-actions' },
						el( c.Button, { variant: 'primary', className: 'is-primary', onClick: run, disabled: busy }, t( 'generateBtn' ) ),
						busy ? el( c.Spinner ) : null
					),
					data.hasKey === false && ! error
						? el( c.Notice, { status: 'warning', isDismissible: false }, t( 'noKey' ), ' ', data.canManage ? el( 'a', { href: data.settingsUrl, target: '_blank', rel: 'noopener noreferrer' }, t( 'configure' ) ) : null )
						: null,
					error ? el( c.Notice, { status: 'error', isDismissible: false }, error.message, ' ', errorLink ) : null,
					result
						? el(
							Fragment,
							null,
							el( 'div', { className: 'aica-preview', dangerouslySetInnerHTML: { __html: result } } ), // Saneado en el servidor con wp_kses_post.
							el(
								'div',
								{ className: 'aica-actions' },
								el( c.Button, { variant: 'primary', className: 'is-primary', onClick: insert }, t( 'replaceBtn' ) ),
								el( c.Button, { variant: 'secondary', onClick: copy }, t( 'copyBtn' ) )
							)
						)
						: null,
					el( 'p', { className: 'aica-usage' }, AICA.usageText( usage ) )
				)
			)
		);
	}

	wp.blocks.registerBlockType( 'aica/ai-assistant', {
		apiVersion: 2,
		title: t( 'blockTitle' ),
		description: t( 'blockDescription' ),
		category: 'widgets',
		icon: 'edit',
		keywords: [ 'ia', 'ai', 'seo', 'traducir', 'generar', 'claude', 'openai' ],
		supports: { html: false, reusable: false },
		attributes: {},
		edit: Edit,
		save: function () {
			return null;
		}
	} );
}( window.wp, window.AICA ) );
