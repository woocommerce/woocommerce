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

export function connectWcpay( createNotice, onCatch ) {
	const errorMessage = __(
		'There was an error connecting to WooPayments. Please try again or connect later in store settings.',
		'woocommerce'
	);
	apiFetch( {
		path: WC_ADMIN_NAMESPACE + '/plugins/connect-wcpay',
		method: 'POST',
	} )
		.then( ( response ) => {
			window.location = response.connectUrl;
		} )
		.catch( () => {
			createNotice( 'error', errorMessage );
			if ( typeof onCatch === 'function' ) {
				onCatch();
			}
		} );
}

export function installActivateAndConnectWcpay(
	reject,
	createNotice,
	installAndActivatePlugins
) {
	installAndActivatePlugins( [ 'woocommerce-payments' ] )
		.then( () => {
			recordEvent( 'woocommerce_payments_install', {
				context: 'tasklist',
			} );

			connectWcpay( createNotice, () => {
				reject();
			} );
		} )
		.catch( ( error ) => {
			createNoticesFromResponse( error );
			reject();
		} );
}

export function isWCPaySupported( countryCode ) {
	const supportedCountries = [
		'US',
		'PR',
		'AU',
		'CA',
		'DE',
		'ES',
		'FR',
		'GB',
		'IE',
		'IT',
		'NZ',
		'AT',
		'BE',
		'NL',
		'PL',
		'PT',
		'CH',
		'HK',
		'SG',
		'CY',
		'DK',
		'EE',
		'FI',
		'GR',
		'LU',
		'LT',
		'LV',
		'NO',
		'MT',
		'SI',
		'SK',
		'BG',
		'CZ',
		'HR',
		'HU',
		'RO',
		'SE',
		'JP',
		'AE',
	];

	return supportedCountries.includes( countryCode );
}
