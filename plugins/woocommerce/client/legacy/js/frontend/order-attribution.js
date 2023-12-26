( function ( wc_order_attribution ) {
	'use strict';
	// Cache params reference for shorter reusability.
	const params = wc_order_attribution.params;

	// Helper functions.
	const $ = document.querySelector.bind( document );

	/**
	 * Flattens the sbjs.get object into a schema compatible object.
	 *
	 * @param {Object} obj Sourcebuster data object, `sbjs.get`.
	 * @returns
	 */
	wc_order_attribution.sbjsDataToSchema = ( obj ) => ( {
		type: obj.current.typ,
		url: obj.current_add.rf,

		utm_campaign: obj.current.cmp,
		utm_source: obj.current.src,
		utm_medium: obj.current.mdm,
		utm_content: obj.current.cnt,
		utm_id: obj.current.id,
		utm_term: obj.current.trm,

		session_entry: obj.current_add.ep,
		session_start_time: obj.current_add.fd,
		session_pages: obj.session.pgs,
		session_count: obj.udata.vst,

		user_agent: obj.udata.uag,
	} );

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
		const setFields = () => {

			if ( sbjs.get ) {
				for( const [ key, value ] of Object.entries( wc_order_attribution.sbjsDataToSchema( sbjs.get ) ) ) {
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
				setFields();
				previousInitCheckout && previousInitCheckout();
			};
		}

		/**
		 * Add source values to register form.
		 */
		if ( $( '.woocommerce form.register' ) !== null ) {
			setFields();
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
