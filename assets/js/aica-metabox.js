/**
 * Asistente de IA - botón de la caja lateral.
 * Funciona en el Editor Clásico (pestañas Visual y Texto) y en Gutenberg.
 */
( function ( window, document ) {
	'use strict';

	var AICA = window.AICA;
	if ( ! AICA ) {
		return;
	}

	var lastResult = '';

	function $( id ) {
		return document.getElementById( id );
	}

	// ---- Adaptador de editor ------------------------------------------------

	/** Devuelve el store core/editor solo si estamos de verdad en Gutenberg. */
	function gutenberg() {
		var wp = window.wp;
		if ( ! document.body.classList.contains( 'block-editor-page' ) || ! wp || ! wp.data || ! wp.data.select ) {
			return null;
		}
		var store = wp.data.select( 'core/editor' );
		return store && typeof store.getEditedPostContent === 'function' ? store : null;
	}

	/** Instancia de TinyMCE solo si la pestaña Visual está activa. */
	function tiny() {
		var ed = window.tinymce && window.tinymce.get ? window.tinymce.get( 'content' ) : null;
		return ed && ! ed.isHidden() ? ed : null;
	}

	function readEditor() {
		var g = gutenberg();
		if ( g ) {
			return {
				content: g.getEditedPostContent() || '',
				title: g.getEditedPostAttribute( 'title' ) || ''
			};
		}
		var ed = tiny();
		var area = $( 'content' );
		var title = $( 'title' );
		return {
			content: ed ? ed.getContent() : area ? area.value : '',
			title: title ? title.value : ''
		};
	}

	function insertIntoEditor( html ) {
		var wp = window.wp;
		var g = gutenberg();

		if ( g ) {
			var dispatcher = wp.data.dispatch( 'core/block-editor' );
			if ( ! wp.blocks || ! dispatcher ) {
				throw new Error( 'block editor' );
			}
			var blocks = wp.blocks.rawHandler( { HTML: html } );
			if ( ! blocks || ! blocks.length ) {
				blocks = [ wp.blocks.createBlock( 'core/paragraph', { content: html } ) ];
			}
			dispatcher.insertBlocks( blocks );
			return;
		}

		var ed = tiny();
		if ( ed ) {
			ed.execCommand( 'mceInsertContent', false, html );
			return;
		}

		var area = $( 'content' );
		if ( ! area ) {
			throw new Error( 'editor' );
		}
		area.value += ( area.value ? '\n\n' : '' ) + html;
	}

	// ---- Interfaz --------------------------------------------------------------

	function showMessage( text, kind, link ) {
		var box = $( 'aica-message' );
		box.textContent = '';
		box.className = 'aica-message aica-' + kind;
		box.appendChild( document.createTextNode( text ) );
		if ( link && link.href ) {
			box.appendChild( document.createTextNode( ' ' ) );
			var a = document.createElement( 'a' );
			a.href = link.href;
			a.target = '_blank';
			a.rel = 'noopener noreferrer';
			a.textContent = link.label;
			box.appendChild( a );
		}
		box.hidden = false;
	}

	function hideMessage() {
		$( 'aica-message' ).hidden = true;
	}

	function setBusy( busy ) {
		$( 'aica-generate-btn' ).disabled = busy;
		$( 'aica-loading' ).hidden = ! busy;
	}

	function refreshUsage( usage ) {
		var line = $( 'aica-usage' );
		if ( line ) {
			line.textContent = AICA.usageText( usage );
		}
	}

	function syncAction() {
		var action = $( 'aica-action-type' ).value;
		$( 'aica-language-row' ).hidden = 'translate' !== action;
		$( 'aica-use-content' ).checked = 'generate' !== action;
		$( 'aica-prompt-input' ).placeholder = 'generate' === action ? AICA.t( 'placeholderGenerate' ) : AICA.t( 'placeholderOther' );
	}

	function onGenerate() {
		var action = $( 'aica-action-type' ).value;
		var prompt = $( 'aica-prompt-input' ).value.trim();
		var source = $( 'aica-use-content' ).checked ? readEditor() : { content: '', title: '' };

		if ( 'generate' === action && ! prompt ) {
			showMessage( AICA.t( 'emptyPrompt' ), 'error' );
			return;
		}
		if ( 'generate' !== action && ! prompt && ! AICA.textOf( source.content ).trim() ) {
			showMessage( AICA.t( 'emptyText' ), 'error' );
			return;
		}

		hideMessage();
		setBusy( true );

		AICA.generate( {
			action: action,
			prompt: prompt,
			content: source.content,
			title: source.title,
			language: $( 'aica-language' ).value
		} ).then(
			function ( res ) {
				lastResult = res.content;
				$( 'aica-result' ).innerHTML = res.content; // Ya saneado en el servidor con wp_kses_post.
				$( 'aica-result-wrap' ).hidden = false;
				refreshUsage( res.usage );
				setBusy( false );
			},
			function ( err ) {
				var link = null;
				if ( err.upgradeUrl ) {
					link = { href: err.upgradeUrl, label: AICA.t( 'upgrade' ) };
				} else if ( err.settingsUrl ) {
					link = { href: err.settingsUrl, label: AICA.t( 'configure' ) };
				}
				showMessage( err.message, 'error', link );
				if ( err.usage ) {
					refreshUsage( err.usage );
				}
				setBusy( false );
			}
		);
	}

	function onInsert() {
		try {
			insertIntoEditor( lastResult );
			showMessage( AICA.t( 'inserted' ), 'ok' );
		} catch ( e ) {
			showMessage( AICA.t( 'insertFailed' ), 'error' );
		}
	}

	function onCopy() {
		AICA.copyText( lastResult ).then(
			function () {
				showMessage( AICA.t( 'copied' ), 'ok' );
			},
			function () {
				showMessage( AICA.t( 'copyFailed' ), 'error' );
			}
		);
	}

	// Delegación de eventos: sigue funcionando aunque Gutenberg mueva la caja de sitio.
	document.addEventListener( 'click', function ( event ) {
		var target = event.target;
		if ( ! target || ! target.closest ) {
			return;
		}
		if ( target.closest( '#aica-generate-btn' ) ) {
			onGenerate();
		} else if ( target.closest( '#aica-insert-btn' ) ) {
			onInsert();
		} else if ( target.closest( '#aica-copy-btn' ) ) {
			onCopy();
		}
	} );

	document.addEventListener( 'change', function ( event ) {
		if ( event.target && 'aica-action-type' === event.target.id ) {
			syncAction();
		}
	} );

	function init() {
		if ( ! $( 'aica-assistant-wrapper' ) ) {
			return;
		}
		syncAction();
		refreshUsage( AICA.data.usage );
		if ( AICA.data.hasKey === false ) {
			showMessage(
				AICA.t( 'noKey' ),
				'error',
				AICA.data.canManage ? { href: AICA.data.settingsUrl, label: AICA.t( 'configure' ) } : null
			);
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}( window, document ) );
