/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import type { CartResponse } from '@woocommerce/types';

export interface PreloadedCartResponse {
	body: CartResponse;
	headers: Record< string, string >;
}

/**
 * Returns the cart the page was rendered with, when the server preloaded it (`AssetDataRegistry::hydrate_api_request`
 * prints it as `wcSettings.preloadedCart`), or null.
 */
export const getPreloadedCartResponse = (): PreloadedCartResponse | null => {
	const response = getSetting< Partial< PreloadedCartResponse > | null >(
		'preloadedCart',
		null
	);

	if ( ! response?.body || typeof response.body !== 'object' ) {
		return null;
	}

	return {
		body: response.body,
		headers: response.headers ?? {},
	};
};
