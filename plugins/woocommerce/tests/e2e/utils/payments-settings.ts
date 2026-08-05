/**
 * External dependencies
 */
import { request } from '@playwright/test';

/**
 * Internal dependencies
 */
import { deleteOption } from './options';

const resetGatewayOrder = async ( baseURL: string ) => {
	try {
		await deleteOption( request, baseURL, 'woocommerce_gateway_order' );
	} catch ( error ) {
		console.error( error );
	}
};

export { resetGatewayOrder };
