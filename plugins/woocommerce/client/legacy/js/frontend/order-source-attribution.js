( function ( $, wc_order_source_attribution ) {
	'use strict';

	const params = wc_order_source_attribution.params;
	const prefix = params.prefix;
	const cookieLifetime = Number( params.lifetime );
	const sessionLength = Number( params.session );

	/**
	 * Flattens the sbjs.get object into a schema compatible object.
	 *
	 * @param {Object} obj Sourcebuster data object, `sbjs.get`.
	 * @returns
	 */
	wc_order_source_attribution.sbjsDataToSchema = ( obj ) => ( {
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

	wc_order_source_attribution.initOrderTracking = () => {

		if ( params.allowTracking === 'no' ) {
			wc_order_source_attribution.removeTrackingCookies();
			return;
		}

		/**
		 * Initialize sourcebuster.js.
		 */
		sbjs.init( {
			lifetime: cookieLifetime,
			session_length: sessionLength,
			timezone_offset: '0', // utc
		} );

		/**
		 * Callback to set visitor source values in the checkout
		 * and register forms using sourcebuster object values.
		 * More info at https://sbjs.rocks/#/usage.
		 */
		const setFields = () => {

			if ( sbjs.get ) {
				for( const [ key, value ] of Object.entries( wc_order_source_attribution.sbjsDataToSchema( sbjs.get ) ) ) {
					$( `input[name="${prefix}${key}"]` ).val( value );
				}
			}
		};

		/**
		 * Add source values to checkout.
		 */
		$( document.body ).on( 'init_checkout', () => { setFields(); } );

		/**
		 * Add source values to register.
		 */
		if ( $( '.woocommerce form.register' ).length ) {
			setFields();
		}
	}

	/**
	 * Enable or disable order tracking analytics and marketing consent init and change.
	 */
	wc_order_source_attribution.setAllowTrackingConsent = ( allow ) => {
		if ( ! allow ) {
			return;
		}

		params.allowTracking = 'yes';
		wc_order_source_attribution.initOrderTracking();
	}

	/**
	 * Remove sourcebuster.js cookies whenever tracking is disabled or consent is revoked.
	 */
	wc_order_source_attribution.removeTrackingCookies = () => {
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
	wc_order_source_attribution.initOrderTracking();

}( jQuery, window.wc_order_source_attribution ) );
