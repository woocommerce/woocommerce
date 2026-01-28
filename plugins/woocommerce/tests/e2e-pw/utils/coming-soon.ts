/**
 * External dependencies
 */
import { request } from '@playwright/test';

/**
 * Internal dependencies
 */
import { setOption } from './options';

/**
 * Options for setting coming soon mode.
 */
export interface ComingSoonOptions {
	baseURL: string;
	enabled: string;
}

/**
 * Set the coming soon mode for the site.
 *
 * @param options         - Configuration options
 * @param options.baseURL - Base URL of the site
 * @param options.enabled - Whether coming soon mode is enabled ('yes' or 'no')
 */
export const setComingSoon = async ( {
	baseURL,
	enabled,
}: ComingSoonOptions ): Promise< void > => {
	try {
		await setOption( request, baseURL, 'woocommerce_coming_soon', enabled );
	} catch ( error ) {
		console.log( error );
	}
};
