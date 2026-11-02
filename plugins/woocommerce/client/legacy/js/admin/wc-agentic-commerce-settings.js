( function () {
	'use strict';

	const settings = window.wcAgenticCommerceSettings;
	if ( ! settings || ! window.wp || ! window.wp.apiFetch ) {
		return;
	}

	const toggle = document.querySelector( '.wc-agentic-commerce-ucp-switch' );
	const knob = toggle && toggle.querySelector( '.woocommerce-input-toggle' );
	const message = document.querySelector( '.wc-agentic-commerce-ucp-message' );
	const statusBadge = document.querySelector( '.wc-agentic-commerce-status' );
	const accessSummary = document.querySelector(
		'.wc-agentic-commerce-access-summary'
	);

	const render = ( enabled ) => {
		toggle.setAttribute( 'aria-checked', enabled ? 'true' : 'false' );
		knob.classList.toggle( 'woocommerce-input-toggle--enabled', enabled );
		knob.classList.toggle( 'woocommerce-input-toggle--disabled', ! enabled );
	};

	const renderStatus = ( status ) => {
		if ( ! statusBadge || ! settings.i18n.statusLabel[ status ] ) {
			return;
		}
		statusBadge.textContent = settings.i18n.statusLabel[ status ];
		statusBadge.className = statusBadge.className.replace(
			/wc-agentic-commerce-badge--\w+/,
			'wc-agentic-commerce-badge--' +
				( { active: 'pass', off: 'off', needs_attention: 'fix' }[ status ] || 'off' )
		);
	};

	if ( toggle && knob && message ) {
		toggle.addEventListener( 'click', async () => {
			const enable = toggle.getAttribute( 'aria-checked' ) !== 'true';
			toggle.disabled = true;
			knob.classList.add( 'woocommerce-input-toggle--loading' );
			try {
				const response = await window.wp.apiFetch( {
					path: settings.path,
					method: 'POST',
					data: { enabled: enable },
				} );
				render( response.enabled );
				renderStatus( response.status );
				message.textContent = response.enabled ? settings.i18n.on : '';
				if ( accessSummary ) {
					accessSummary.textContent =
						accessSummary.dataset[ response.enabled ? 'on' : 'off' ];
				}
			} catch ( error ) {
				render( ! enable );
				message.textContent = ( error && error.message ) || settings.i18n.error;
			} finally {
				toggle.disabled = false;
				knob.classList.remove( 'woocommerce-input-toggle--loading' );
			}
		} );
	}

	const run = document.querySelector( '.wc-agentic-commerce-run' );
	const runMessage = document.querySelector( '.wc-agentic-commerce-run-message' );
	if ( run && runMessage ) {
		run.addEventListener( 'click', async () => {
			run.disabled = true;
			try {
				const response = await window.wp.apiFetch( {
					path: settings.scanPath,
					method: 'POST',
				} );
				// A stalled queue scans in the request, so the new report is already stored.
				if ( ! response.scan_pending ) {
					// Not reload(): after Save changes, that would post the settings form again.
					window.location.assign( window.location.href.split( '#' )[ 0 ] );
					return;
				}
				runMessage.textContent = settings.i18n.running;
			} catch ( error ) {
				run.disabled = false;
				runMessage.textContent = ( error && error.message ) || settings.i18n.error;
			}
		} );
	}

	document.querySelectorAll( '.wc-agentic-commerce-copy' ).forEach( ( button ) => {
		button.addEventListener( 'click', async () => {
			const input = document.getElementById( button.dataset.copyTarget );
			if ( ! input || ! navigator.clipboard ) {
				return;
			}
			const label = button.textContent;
			await navigator.clipboard.writeText( input.value );
			button.textContent = settings.i18n.copied;
			setTimeout( () => {
				button.textContent = label;
			}, 2000 );
		} );
	} );
} )();
