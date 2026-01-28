/**
 * WooCommerce Fraud Protection Checkout Script
 *
 * Handles three checkout types:
 * - blocks: Uses applyExtensionCartUpdate
 * - shortcode: Uses update_checkout event
 * - add-payment-method: Uses AJAX verify + page reload
 */
( function () {
	'use strict';

	const config = window.wcFraudProtection;
	if ( ! config ) {
		// eslint-disable-next-line no-console
		console.error( '[FraudProtection] Config not found' );
		return;
	}

	const TIMEOUT_MS = config.timeoutMs || 5000;

	/**
	 * Shortcode checkout: Add hidden input and trigger update_checkout.
	 *
	 * @param {string} sessionId The Blackbox session ID.
	 */
	const triggerShortcodeUpdate = ( sessionId ) => {
		const $ = window.jQuery;
		if ( ! $ ) {
			return;
		}

		const form = $( 'form.checkout' );
		if ( form.length ) {
			form.find( 'input[name="blackbox_session_id"]' ).remove();
			form.append(
				`<input type="hidden" name="blackbox_session_id" value="${ sessionId }" />`
			);
		}

		$( document.body ).trigger( 'update_checkout' );
		// eslint-disable-next-line no-console
		console.log(
			'[FraudProtection] Triggered update_checkout with session:',
			sessionId
		);
	};

	/**
	 * Blocks checkout: Call applyExtensionCartUpdate via wp.data.
	 *
	 * @param {string} sessionId The Blackbox session ID.
	 */
	const triggerBlocksUpdate = async ( sessionId ) => {
		const wpData = window.wp && window.wp.data;
		const dispatch = wpData && wpData.dispatch;
		if ( ! dispatch ) {
			// eslint-disable-next-line no-console
			console.error( '[FraudProtection] wp.data not available' );
			return;
		}

		try {
			await dispatch( 'wc/store/cart' ).applyExtensionCartUpdate( {
				namespace: config.namespace,
				data: { blackbox_session_id: sessionId },
			} );
			// eslint-disable-next-line no-console
			console.log(
				'[FraudProtection] Cart updated with session:',
				sessionId
			);
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( '[FraudProtection] Cart update failed:', error );
		}
	};

	/**
	 * Add payment method: AJAX verify, then page reload.
	 * Payment methods are hidden until session is ALLOWED.
	 *
	 * @param {string} sessionId The Blackbox session ID.
	 */
	const verifyAndReload = async ( sessionId ) => {
		const $ = window.jQuery;
		if ( ! $ ) {
			return;
		}

		try {
			const response = await $.ajax( {
				url: config.ajaxUrl,
				method: 'POST',
				data: {
					security: config.nonce,
					blackbox_session_id: sessionId,
				},
			} );

			if ( response.success ) {
				// Session verified - reload to show payment methods
				// eslint-disable-next-line no-console
				console.log(
					'[FraudProtection] Verification successful, reloading page'
				);
				window.location.reload();
			} else {
				// Blocked - show error (payment methods stay hidden)
				var errorMessage =
					( response.data && response.data.message ) ||
					'Unable to proceed.';
				// eslint-disable-next-line no-console
				console.error(
					'[FraudProtection] Verification failed:',
					errorMessage
				);
				$( '.woocommerce-add-payment-method' ).prepend(
					'<div class="woocommerce-error">' + errorMessage + '</div>'
				);
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( '[FraudProtection] AJAX verify failed:', error );
			// Fail-open: reload and let server decide
			window.location.reload();
		}
	};

	const initialize = async () => {
		// Skip if session is already verified (ALLOWED or BLOCKED).
		// Only PENDING sessions need verification.
		if ( config.isSessionVerified ) {
			// eslint-disable-next-line no-console
			console.log(
				'[FraudProtection] Session already verified, skipping initialization'
			);
			return;
		}

		let sessionId = '';

		// Try to get session_id from Blackbox
		if ( window.Blackbox && window.Blackbox.init ) {
			try {
				sessionId = await Promise.race( [
					window.Blackbox.init(),
					new Promise( ( _, reject ) =>
						setTimeout(
							() => reject( new Error( 'Blackbox timeout' ) ),
							TIMEOUT_MS
						)
					),
				] );
				// eslint-disable-next-line no-console
				console.log(
					'[FraudProtection] Got Blackbox session:',
					sessionId
				);
			} catch ( error ) {
				// Fail-open: continue with empty session_id
				// eslint-disable-next-line no-console
				console.warn(
					'[FraudProtection] Blackbox init failed, continuing with empty session:',
					error
				);
			}
		} else {
			// eslint-disable-next-line no-console
			console.warn(
				'[FraudProtection] Blackbox SDK not loaded, continuing with empty session'
			);
		}

		// Trigger appropriate action based on checkout type
		switch ( config.checkoutType ) {
			case 'blocks':
				await triggerBlocksUpdate( sessionId );
				break;
			case 'shortcode':
				triggerShortcodeUpdate( sessionId );
				break;
			case 'add-payment-method':
				await verifyAndReload( sessionId );
				break;
		}
	};

	// Start initialization when DOM is ready
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initialize );
	} else {
		initialize();
	}
} )();
