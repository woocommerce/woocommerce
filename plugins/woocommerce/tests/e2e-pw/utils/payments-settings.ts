/**
 * External dependencies
 */
import { request } from '@playwright/test';

/**
 * Internal dependencies
 */
import { deleteOption } from './options';

/**
 * Reset the gateway order option in WooCommerce settings.
 *
 * @param baseURL - The base URL of the WordPress site.
 */
export const resetGatewayOrder = async ( baseURL: string ): Promise< void > => {
	try {
		await deleteOption( request, baseURL, 'woocommerce_gateway_order' );
	} catch ( error ) {
		console.log( error );
	}
};
