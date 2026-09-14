/**
 * Custom Place Order Button API
 *
 * Shared functionality for custom place order buttons across checkout, order-pay,
 * and add-payment-method pages. This module provides the core registration and
 * button management logic.
 *
 * @since 10.6.0
 */

( function ( $ ) {
	'use strict';

	// Initialize the global wc.customPlaceOrderButton namespace
	window.wc = window.wc || {};
	window.wc.customPlaceOrderButton = window.wc.customPlaceOrderButton || {};

	// Inject critical CSS inline to ensure it works even when themes dequeue woocommerce.css
	( function injectStyles() {
		var styleId = 'wc-custom-place-order-button-styles';
		if ( document.getElementById( styleId ) ) {
			return;
		}
		var style = document.createElement( 'style' );
		style.id = styleId;
		style.textContent = 'form.has-custom-place-order-button #place_order { display: none !important; }';
		document.head.appendChild( style );
	} )();

	/**
	 * Registry for custom place order buttons.
	 * Key: gateway_id, Value: { render: function, cleanup: function }
	 */
	var customPlaceOrderButtons = {};

	/**
	 * Currently active custom button gateway ID, or null if using the default button.
	 */
	var activeCustomButtonGateway = null;

	/**
	 * Container element for the custom place order button.
	 */
	var $customButtonContainer = null;

	/**
	 * Get the current form element based on page context.
	 *
	 * @return {jQuery} The form element.
	 */
	function getForm() {
		if ( $( 'form.checkout' ).length ) {
			return $( 'form.checkout' ).first();
		}
		if ( $( '#order_review' ).length ) {
			return $( '#order_review' ).first();
		}
		if ( $( '#add_payment_method' ).length ) {
			return $( '#add_payment_method' ).first();
		}
		return $( [] );
	}

	/**
	 * Get a list of gateway IDs with custom place order buttons from server config.
	 *
	 * @return {Array} List of gateway IDs
	 */
	function getGatewaysWithCustomButton() {
		// Try multiple param sources for compatibility across pages
		if ( typeof wc_checkout_params !== 'undefined' && wc_checkout_params.gateways_with_custom_place_order_button ) {
			return wc_checkout_params.gateways_with_custom_place_order_button;
		}
		if ( typeof wc_add_payment_method_params !== 'undefined' && wc_add_payment_method_params.gateways_with_custom_place_order_button ) {
			return wc_add_payment_method_params.gateways_with_custom_place_order_button;
		}
		return [];
	}

	/**
	 * Check if a gateway has a custom place order button registered via a server-side flag.
	 *
	 * @param {string} gatewayId - The payment gateway ID
	 * @return {boolean} True if gateway has custom button
	 */
	function gatewayHasCustomPlaceOrderButton( gatewayId ) {
		return getGatewaysWithCustomButton().indexOf( gatewayId ) !== -1;
	}

	/**
	 * Create or get the container for custom place order buttons.
	 * The container is scoped to the current form to handle multiple forms on a page.
	 * We're not creating the container server-side to avoid introducing a new template
	 * that might break compatibility with subscriptions or other extensions.
	 *
	 * @return {jQuery} The container element
	 */
	function getOrCreateCustomButtonContainer() {
		if ( $customButtonContainer && $customButtonContainer.length && $.contains( document, $customButtonContainer[ 0 ] ) ) {
			return $customButtonContainer;
		}

		var $placeOrderButton = getForm().find( '#place_order' );
		if ( ! $placeOrderButton.length ) {
			return $( [] );
		}

		$customButtonContainer = $( '<div class="wc-custom-place-order-button"></div>' );
		$placeOrderButton.after( $customButtonContainer );

		return $customButtonContainer;
	}

	/**
	 * Remove the custom button container.
	 */
	function removeCustomButtonContainer() {
		if ( $customButtonContainer && $customButtonContainer.length ) {
			$customButtonContainer.remove();
			$customButtonContainer = null;
		}
	}

	/**
	 * Clean up the current custom button if any.
	 */
	function cleanupCurrentCustomButton() {
		if ( activeCustomButtonGateway && customPlaceOrderButtons[ activeCustomButtonGateway ] ) {
			try {
				customPlaceOrderButtons[ activeCustomButtonGateway ].cleanup();
			} catch ( e ) {
				// Log errors to help gateway developers debug their cleanup implementation.
				// eslint-disable-next-line no-console
				console.error( 'Error in custom place order button cleanup:', e );
			}
		}
		removeCustomButtonContainer();
		activeCustomButtonGateway = null;
	}

	/**
	 * Show custom place order button for a gateway, or show default button.
	 * The `api` object is needed because the add-payment-method page is different than a checkout or a pay-for-order page.
	 * Each page can decide how to implement the `validate` and `submit` methods.
	 *
	 * @param {string} gatewayId - The payment gateway ID
	 * @param {Object} api - The API object to pass to render callback
	 */
	function maybeShowCustomPlaceOrderButton( gatewayId, api ) {
		var $form = getForm();

		// Clean up any displayed custom button, if any
		if ( activeCustomButtonGateway && customPlaceOrderButtons[ activeCustomButtonGateway ] ) {
			try {
				customPlaceOrderButtons[ activeCustomButtonGateway ].cleanup();
			} catch ( e ) {
				// Log errors to help gateway developers debug their cleanup implementation.
				// eslint-disable-next-line no-console
				console.error( 'Error in custom place order button cleanup:', e );
			}
		}

		var isCustomButtonRegistered = Boolean( customPlaceOrderButtons[ gatewayId ] );
		if ( isCustomButtonRegistered ) {
			// Hide the default button and show the custom one, instead.
			$form.addClass( 'has-custom-place-order-button' );
			activeCustomButtonGateway = gatewayId;

			var $container = getOrCreateCustomButtonContainer();
			$container.empty();

			try {
				customPlaceOrderButtons[ gatewayId ].render( $container.get( 0 ), api );
			} catch ( e ) {
				// Log errors to help gateway developers debug their render implementation.
				// eslint-disable-next-line no-console
				console.error( 'Error rendering custom place order button:', e );
			}
		} else {
			// Only show default button if gateway doesn't have a custom button pending registration.
			// This prevents flash when gateway JS loads after the initial click.
			// Basically, when `__maybeShow` is called for a gateway that isn't registered yet but has the server-side flag set,
			// we keep the default button hidden instead of flashing it.
			if ( ! gatewayHasCustomPlaceOrderButton( gatewayId ) ) {
				$form.removeClass( 'has-custom-place-order-button' );
			}
			activeCustomButtonGateway = null;
			removeCustomButtonContainer();
		}
	}

	/**
	 * Hide default button immediately if selected gateway has custom button (prevents flash).
	 *
	 * @param {string} gatewayId - The payment gateway ID
	 */
	function maybeHideDefaultButtonOnInit( gatewayId ) {
		if ( gatewayHasCustomPlaceOrderButton( gatewayId ) ) {
			var $form = getForm();
			$form.addClass( 'has-custom-place-order-button' );
		}
	}

	/**
	 * Register a custom place order button for a payment gateway.
	 *
	 * @param {string} gatewayId - The payment gateway ID (e.g., 'google_pay')
	 * @param {Object} config - Configuration object
	 * @param {Function} config.render - Function called to render the button. Receives (container, api)
	 * @param {Function} config.cleanup - Function called when switching away from this gateway
	 */
	function registerCustomPlaceOrderButton( gatewayId, config ) {
		// Silently ignore if already registered (prevents double-registration issues)
		if ( customPlaceOrderButtons[ gatewayId ] ) {
			return;
		}

		if ( typeof gatewayId !== 'string' || ! gatewayId ) {
			// Log validation errors to help gateway developers fix incorrect API usage.
			// eslint-disable-next-line no-console
			console.error( 'wc.customPlaceOrderButton.register: gatewayId must be a non-empty string' );
			return;
		}
		if ( typeof config !== 'object' || config === null ) {
			// Log validation errors to help gateway developers fix incorrect API usage.
			// eslint-disable-next-line no-console
			console.error( 'wc.customPlaceOrderButton.register: config must be an object' );
			return;
		}
		if ( typeof config.render !== 'function' ) {
			// Log validation errors to help gateway developers fix incorrect API usage.
			// eslint-disable-next-line no-console
			console.error( 'wc.customPlaceOrderButton.register: render must be a function' );
			return;
		}
		if ( typeof config.cleanup !== 'function' ) {
			// Log validation errors to help gateway developers fix incorrect API usage.
			// eslint-disable-next-line no-console
			console.error( 'wc.customPlaceOrderButton.register: cleanup must be a function' );
			return;
		}

		customPlaceOrderButtons[ gatewayId ] = config;

		// If this gateway is already selected, notify that registration is complete
		if ( getForm().find( 'input[name="payment_method"]:checked' ).val() === gatewayId ) {
			// since this API needs to be used on checkout/pay for order/my account pages,
			// we need to trigger a global event to ensure it's picked up by the WC Core JS used in those pages.
			$( document.body ).trigger( 'wc_custom_place_order_button_registered', [ gatewayId ] );
		}
	}

	// Export functions to the global namespace
	// Public API (for gateway developers)
	window.wc.customPlaceOrderButton.register = registerCustomPlaceOrderButton;

	// Internal API (used by WooCommerce core, not for external use)
	window.wc.customPlaceOrderButton.__maybeShow = maybeShowCustomPlaceOrderButton;
	window.wc.customPlaceOrderButton.__maybeHideDefaultButtonOnInit = maybeHideDefaultButtonOnInit;
	window.wc.customPlaceOrderButton.__cleanup = cleanupCurrentCustomButton;
	window.wc.customPlaceOrderButton.__getForm = getForm;

} )( jQuery );
