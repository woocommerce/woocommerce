( function ( wc_order_attribution ) {
	'use strict';
	// Cache params reference for shorter reusability.
	const params = wc_order_attribution.params;

	// Helper functions.
	const $ = document.querySelector.bind( document );
	const propertyAccessor = ( obj, path ) => path.split( '.' ).reduce( ( acc, part ) => acc && acc[ part ], obj );

	/**
	 * Flattens the sbjs.get object into our schema compatible object
	 * according to the fields configuration.
	 * More info at https://sbjs.rocks/#/usage.
	 *
	 * @returns {Object} Schema compatible object.
	 */
	wc_order_attribution.getData = () => {
		const entries = Object.entries( wc_order_attribution.fields )
				.map( ( [ key, accessor ] ) => [ key, propertyAccessor( sbjs.get, accessor ) ] );
		return Object.fromEntries( entries );
	}

	/**
	 * Set wc_order_attribution input elements' values.
	 *
	 * @param {Object} values Object containing field values.
	 */
	function setFormValues( values ) {
		// Update inputs if any exist.
		if( $( `input[name^="${params.prefix}"]` ) ) {
			for( const key of Object.keys( wc_order_attribution.fields ) ) {
				$( `input[name="${params.prefix}${key}"]` ).value = values && values[ key ] || '';
			}
		}
	};
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
			setFormValues();
		} else {
			// If not done yet, initialize sourcebuster.js which populates `sbjs.get` object.
			sbjs.init( {
				lifetime: Number( params.lifetime ),
				session_length: Number( params.session ),
				timezone_offset: '0', // utc
			} );
			setFormValues( wc_order_attribution.getData() );
		}
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
			setFormValues( wc_order_attribution.getData() );
			previousInitCheckout && previousInitCheckout();
		};
	}

}( window.wc_order_attribution ) );
