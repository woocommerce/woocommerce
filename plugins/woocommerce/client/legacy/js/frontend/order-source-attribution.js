( function ( $, params ) {
	'use strict';

	const prefix = params.prefix;
	const cookieLifetime = Number( params.lifetime );
	const sessionLength = Number( params.session );

	window.woocommerce_order_source_attribution = {};

	woocommerce_order_source_attribution.initOrderTracking = () => {

		if ( params.allowTracking === 'no' ) {
			woocommerce_order_source_attribution.removeTrackingCookies();
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
				$( `input[name="${prefix}type"]` ).val( sbjs.get.current.typ );
				$( `input[name="${prefix}url"]` ).val( sbjs.get.current_add.rf );

				$( `input[name="${prefix}utm_campaign"]` ).val( sbjs.get.current.cmp );
				$( `input[name="${prefix}utm_source"]` ).val( sbjs.get.current.src );
				$( `input[name="${prefix}utm_medium"]` ).val( sbjs.get.current.mdm );
				$( `input[name="${prefix}utm_content"]` ).val( sbjs.get.current.cnt );
				$( `input[name="${prefix}utm_id"]` ).val( sbjs.get.current.id );
				$( `input[name="${prefix}utm_term"]` ).val( sbjs.get.current.trm );

				$( `input[name="${prefix}session_entry"]` ).val( sbjs.get.current_add.ep );
				$( `input[name="${prefix}session_start_time"]` ).val( sbjs.get.current_add.fd );
				$( `input[name="${prefix}session_pages"]` ).val( sbjs.get.session.pgs );
				$( `input[name="${prefix}session_count"]` ).val( sbjs.get.udata.vst );

				$( `input[name="${prefix}user_agent"]` ).val( sbjs.get.udata.uag );
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
	woocommerce_order_source_attribution.setAllowTrackingConsent = ( allow ) => {
		if ( ! allow ) {
			return;
		}

		params.allowTracking = 'yes';
		woocommerce_order_source_attribution.initOrderTracking();
	}

	/**
	 * Remove sourcebuster.js cookies whenever tracking is disabled or consent is revoked.
	 */
	woocommerce_order_source_attribution.removeTrackingCookies = () => {
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
	woocommerce_order_source_attribution.initOrderTracking();

}( jQuery, window.wc_order_attribute_source_params ) );
