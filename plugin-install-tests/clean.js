/**
 * External dependencies
 */
import { rmSync } from 'fs';

/**
 * Internal dependencies
 */

import config from './config.js';

export const clean = ( showError = false ) => {
	const plugins = [
		'woocommerce-payments',
		'woocommerce-services',
		'mailpoet',
		'google-listings-and-ads',
		'pinterest-for-woocommerce',
		'tiktok-for-business',
	];

	plugins.forEach( ( plugin ) => {
		try {
			rmSync( config.WP_PLUGIN_DIR + plugin, { recursive: true } );
		} catch ( e ) {
			if ( showError ) {
				console.log( e );
			}
		}
	} );
};
