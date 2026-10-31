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
			} catch ( error ) {
				render( ! enable );
				message.textContent = ( error && error.message ) || settings.i18n.error;
			} finally {
				toggle.disabled = false;
				knob.classList.remove( 'woocommerce-input-toggle--loading' );
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

	const summary = document.getElementById( 'woocommerce_return_policy_summary' );
	if ( summary ) {
		const counter = document.createElement( 'p' );
		counter.className = 'description';
		counter.setAttribute( 'aria-live', 'polite' );
		const update = () => {
			counter.textContent = settings.i18n.counter
				.replace( '%1$d', String( Array.from( summary.value ).length ) )
				.replace( '%2$d', String( settings.maxLength ) );
		};
		summary.insertAdjacentElement( 'afterend', counter );
		summary.addEventListener( 'input', update );
		update();
	}
} )();
