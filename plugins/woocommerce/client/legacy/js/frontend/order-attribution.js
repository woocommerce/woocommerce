( function ( wc_order_attribution ) {
	'use strict';
	// Cache params reference for shorter reusability.
	const params = wc_order_attribution.params;

	// Helper functions.
	const $ = document.querySelector.bind( document );
	const propertyAccessor = ( obj, path ) => path.split( '.' ).reduce( ( acc, part ) => acc && acc[ part ], obj );
	const returnNull = () => null;

	// Hardcode Checkout store key (`wc.wcBlocksData.CHECKOUT_STORE_KEY`), as we no longer have `wc-blocks-checkout` as a dependency.
	const CHECKOUT_STORE_KEY = 'wc/store/checkout';

	/**
	 * Get the order attribution data.
	 *
	 * Returns object full of `null`s if tracking is disabled.
	 *
	 * @returns {Object} Schema compatible object.
	 */
	function getData() {
		const accessor = params.allowTracking ? propertyAccessor : returnNull;
		const entries = Object.entries( wc_order_attribution.fields )
				.map( ( [ key, property ] ) => [ key, accessor( sbjs.get, property ) ] );
		return Object.fromEntries( entries );
	}

	/**
	 * Update `wc_order_attribution` input elements' values.
	 *
	 * @param {Object} values Object containing field values.
	 */
	function updateFormValues( values ) {
		// Update inputs if any exist.
		if( $( `input[name^="${params.prefix}"]` ) ) {
			for( const key of Object.keys( wc_order_attribution.fields ) ) {
				$( `input[name="${params.prefix}${key}"]` ).value = values && values[ key ] || '';
			}
		}

	};

	/**
	 * Update Checkout extension data.
	 *
	 * @param {Object} values Object containing field values.
	 */
	function updateCheckoutBlockData( values ) {
		// Update Checkout block data if available.
		if ( window.wp && window.wp.data && window.wp.data.dispatch && window.wc && window.wc.wcBlocksData ) {
			window.wp.data.dispatch( window.wc.wcBlocksData.CHECKOUT_STORE_KEY ).__internalSetExtensionData(
				'woocommerce/order-attribution',
				values,
				true
			);
		}
	}

	/**
	 * Initialize sourcebuster & set data, or clear cookies & data.
	 *
	 * @param {boolean} allow Whether to allow tracking or disable it.
	 */
	wc_order_attribution.setOrderTracking = function( allow ) {
		params.allowTracking = allow;
		if ( ! allow ) {
			// Reset cookies, and clear form data.
			removeTrackingCookies();
		} else {
			// If not done yet, initialize sourcebuster.js which populates `sbjs.get` object.
			sbjs.init( {
				lifetime: Number( params.lifetime ),
				session_length: Number( params.session ),
				timezone_offset: '0', // utc
			} );
		}
		const values = getData();
		updateFormValues( values );
		updateCheckoutBlockData( values );
	}

	/**
	 * Remove sourcebuster.js cookies.
	 * To be called whenever tracking is disabled or consent is revoked.
	 */
	function removeTrackingCookies() {
		const domain = window.location.hostname;
		const sbCookies = [
			'sbjs_current',
			'sbjs_current_add',
			'sbjs_first',
			'sbjs_first_add',
			'sbjs_session',
			'sbjs_udata',
			'sbjs_migrations',
			'sbjs_promo'
		];

		// Remove cookies
		sbCookies.forEach( ( name ) => {
			document.cookie = `${name}=; path=/; max-age=-999; domain=.${domain};`;
		} );
	}

	// Run init.
	wc_order_attribution.setOrderTracking( params.allowTracking );

	// Wait for (async) classic checkout initialization and set source values once loaded.
	if ( $( 'form.woocommerce-checkout' ) !== null ) {
		const previousInitCheckout = document.body.oninit_checkout;
		document.body.oninit_checkout = () => {
			updateFormValues( getData() );
			previousInitCheckout && previousInitCheckout();
		};
	}

	// Work around the lack of explicit script dependency for the checkout block.
	// Conditionally, wait for and use 'wp-data' & 'wc-blocks-checkout.

	// Wait for (async) block checkout initialization and set source values once loaded.
	function eventuallyInitializeCheckoutBlock() {
		if (
			window.wp && window.wp.data && typeof window.wp.data.subscribe === 'function'
		) {
			// Update checkout block data once more if the checkout store was loaded after this script.
			const unsubscribe = window.wp.data.subscribe( function () {
				unsubscribe();
				updateCheckoutBlockData( getData() );
			}, CHECKOUT_STORE_KEY );
		}
	};
	// Wait for DOMContentLoaded to make sure wp.data is in place, if applicable for the page.
	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", eventuallyInitializeCheckoutBlock);
	} else {
		eventuallyInitializeCheckoutBlock();
	}

}( window.wc_order_attribution ) );
