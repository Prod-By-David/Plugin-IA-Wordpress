/**
 * Asistente de IA - núcleo compartido (meta box y bloque).
 * Expone window.AICA con la petición al endpoint REST y utilidades.
 */
( function ( window, document ) {
	'use strict';

	var data = window.aicaData || {};
	var i18n = data.i18n || {};

	function t( key, fallback ) {
		return i18n[ key ] || fallback || key;
	}

	/**
	 * POST al endpoint REST del plugin. Resuelve siempre con { ok, status, body }.
	 */
	function request( payload ) {
		return window.fetch( data.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': data.nonce
			},
			body: JSON.stringify( payload )
		} ).then( function ( response ) {
			return response.json().then(
				function ( body ) {
					return { ok: response.ok, status: response.status, body: body || {} };
				},
				function () {
					return { ok: response.ok, status: response.status, body: {} };
				}
			);
		} );
	}

	/**
	 * Genera contenido. Resuelve con el cuerpo de la respuesta ({ content, usage })
	 * o rechaza con un Error que puede llevar upgradeUrl / settingsUrl / usage.
	 */
	function generate( payload ) {
		return request( payload ).then(
			function ( res ) {
				if ( res.ok && res.body && res.body.success ) {
					return res.body;
				}
				var err = new Error( ( res.body && res.body.message ) || t( 'errorGeneric' ) );
				err.code = res.body && res.body.code;
				err.upgradeUrl = res.body && res.body.upgrade_url;
				err.settingsUrl = res.body && res.body.settings_url;
				err.usage = res.body && res.body.usage;
				throw err;
			},
			function () {
				throw new Error( t( 'errorNetwork' ) );
			}
		);
	}

	function usageText( usage ) {
		if ( ! usage ) {
			return '';
		}
		if ( usage.licensed ) {
			return t( 'usageUnlimited' );
		}
		return t( 'usageFree' ).replace( '{used}', usage.used ).replace( '{limit}', usage.limit );
	}

	/**
	 * Texto visible de un fragmento HTML (DOMParser es inerte: no ejecuta scripts ni carga imágenes).
	 */
	function textOf( html ) {
		var doc = new window.DOMParser().parseFromString( html || '', 'text/html' );
		return ( doc.body && doc.body.textContent ) || '';
	}

	function copyText( text ) {
		if ( window.navigator.clipboard && window.navigator.clipboard.writeText ) {
			return window.navigator.clipboard.writeText( text );
		}
		return new Promise( function ( resolve, reject ) {
			var area = document.createElement( 'textarea' );
			area.value = text;
			area.style.position = 'fixed';
			area.style.opacity = '0';
			document.body.appendChild( area );
			area.select();
			try {
				if ( document.execCommand( 'copy' ) ) {
					resolve();
				} else {
					reject( new Error( 'copy' ) );
				}
			} catch ( e ) {
				reject( e );
			}
			document.body.removeChild( area );
		} );
	}

	window.AICA = {
		data: data,
		t: t,
		generate: generate,
		usageText: usageText,
		textOf: textOf,
		copyText: copyText
	};
}( window, document ) );
