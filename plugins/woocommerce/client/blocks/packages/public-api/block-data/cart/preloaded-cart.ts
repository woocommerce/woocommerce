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
 * Returns the cart the page was rendered with, when the server preloaded it (`AssetDataRegistry::hydrate_api_request`),
 * or null.
 */
export const getPreloadedCartResponse = (): PreloadedCartResponse | null => {
	const preloaded = getSetting<
		Record< string, Partial< PreloadedCartResponse > | undefined >
	>( 'preloadedApiRequests', {} );
	const response = preloaded?.[ '/wc/store/v1/cart' ];

	if ( ! response?.body || typeof response.body !== 'object' ) {
		return null;
	}

	return {
		body: response.body,
		headers: response.headers ?? {},
	};
};
