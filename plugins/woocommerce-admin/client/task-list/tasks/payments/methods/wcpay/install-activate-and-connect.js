/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { WC_ADMIN_NAMESPACE } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from '~/lib/notices';

export function installActivateAndConnectWcpay(
	reject,
	createNotice,
	installAndActivatePlugins
) {
	const errorMessage = __(
		'There was an error connecting to WooCommerce Payments. Please try again or connect later in store settings.',
		'woocommerce-admin'
	);

	const connect = () => {
		apiFetch( {
			path: WC_ADMIN_NAMESPACE + '/plugins/connect-wcpay',
			method: 'POST',
		} )
			.then( ( response ) => {
				window.location = response.connectUrl;
			} )
			.catch( () => {
				createNotice( 'error', errorMessage );
				reject();
			} );
	};

	installAndActivatePlugins( [ 'woocommerce-payments' ] )
		.then( () => {
			recordEvent( 'woocommerce_payments_install', {
				context: 'tasklist',
			} );

			connect();
		} )
		.catch( ( error ) => {
			createNoticesFromResponse( error );
			reject();
		} );
}
