( function () {
	'use strict';

	const settings = window.wcAgenticCommerceSettings;
	if ( ! settings || ! window.wp || ! window.wp.apiFetch ) {
		return;
	}

	const toggle = document.querySelector( '.wc-agentic-commerce-ucp-switch' );
	const knob = toggle && toggle.querySelector( '.woocommerce-input-toggle' );
	const message = document.querySelector( '.wc-agentic-commerce-ucp-message' );

	const render = ( enabled ) => {
		toggle.setAttribute( 'aria-checked', enabled ? 'true' : 'false' );
		knob.classList.toggle( 'woocommerce-input-toggle--enabled', enabled );
		knob.classList.toggle( 'woocommerce-input-toggle--disabled', ! enabled );
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
				// Status, the Overview and Sales readiness depend on the switch; reload to show them for the new state.
				// Not reload(): after Save changes, that would post the settings form again.
				window.location.assign( window.location.href.split( '#' )[ 0 ] );
			} catch ( error ) {
				render( ! enable );
				message.textContent = ( error && error.message ) || settings.i18n.error;
			} finally {
				toggle.disabled = false;
				knob.classList.remove( 'woocommerce-input-toggle--loading' );
			}
		} );
	}

	// "Hide" goes below an opened fold, as a button that closes it; without this script the summary toggles.
	document.querySelectorAll( '.wc-agentic-commerce-more' ).forEach( ( details ) => {
		const summary = details.querySelector( ':scope > summary' );
		const label = summary && summary.querySelector( '.wc-agentic-commerce-more__hide' );
		if ( ! label ) {
			return;
		}
		const hide = document.createElement( 'button' );
		hide.type = 'button';
		hide.className = 'button-link wc-agentic-commerce-more__close';
		hide.textContent = label.textContent;
		hide.addEventListener( 'click', () => {
			details.open = false;
			summary.focus();
		} );
		details.append( hide );
		details.classList.add( 'wc-agentic-commerce-more--js' );
		// The summary hides once open, so focus moves to what it revealed.
		details.addEventListener( 'toggle', () => {
			const active = details.ownerDocument.activeElement;
			if ( ! details.open || ( active !== summary && active !== details.ownerDocument.body ) ) {
				return;
			}
			const revealed = summary.nextElementSibling;
			if ( revealed && revealed !== hide ) {
				revealed.tabIndex = -1;
				revealed.focus();
			}
		} );
	} );

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
