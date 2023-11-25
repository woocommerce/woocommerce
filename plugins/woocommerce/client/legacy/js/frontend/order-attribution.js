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
	 * @returns {array} key value paris in `Object.entries` format.
	 */
	wc_order_attribution.getData =
		() => Object.entries( wc_order_attribution.fields )
				.map( ( [ key, accessor ] ) => [ key, propertyAccessor( sbjs.get, accessor ) ] );

	/**
	 * Initialize the module.
	 */
	function initOrderTracking() {
		if ( params.allowTracking === 'no' ) {
			removeTrackingCookies();
			return;
		}

		/**
		 * Initialize sourcebuster.js.
		 */
		sbjs.init( {
			lifetime: Number( params.lifetime ),
			session_length: Number( params.session ),
			timezone_offset: '0', // utc
		} );

		/**
		 * Callback to set visitor source values in the checkout
		 * and register forms using sourcebuster object values.
		 * More info at https://sbjs.rocks/#/usage.
		 */
		const setFormValues = () => {

			if ( sbjs.get ) {
				for( const [ key, value ] of wc_order_attribution.getData() ) {
					$( `input[name="${params.prefix}${key}"]` ).value = value;
				}
			}
		};

		/**
		 * Add source values to the classic checkout form.
		 */
		if ( $( 'form.woocommerce-checkout' ) !== null ) {
			const previousInitCheckout = document.body.oninit_checkout;
			document.body.oninit_checkout = () => {
				setFormValues();
				previousInitCheckout && previousInitCheckout();
			};
		}

		/**
		 * Add source values to register form.
		 */
		if ( $( '.woocommerce form.register' ) !== null ) {
			setFormValues();
		}
	}

	/**
	 * Enable or disable order tracking analytics and marketing consent init and change.
	 */
	wc_order_attribution.setAllowTrackingConsent = ( allow ) => {
		if ( ! allow ) {
			return;
		}

		params.allowTracking = 'yes';
		initOrderTracking();
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
	initOrderTracking();

}( window.wc_order_attribution ) );
