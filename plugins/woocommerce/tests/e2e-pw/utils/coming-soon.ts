/**
 * External dependencies
 */
import { request } from '@playwright/test';

/**
 * Internal dependencies
 */
import { setOption } from './options';

const setComingSoon = async ( {
	baseURL,
	enabled,
}: {
	baseURL: string;
	enabled: string;
} ) => {
	try {
		await setOption( request, baseURL, 'woocommerce_coming_soon', enabled );
	} catch ( error ) {
		console.error( error );
	}
};

export { setComingSoon };
