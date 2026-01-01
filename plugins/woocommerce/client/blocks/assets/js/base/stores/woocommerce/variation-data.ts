/**
 * External dependencies
 */
import { getConfig, store } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import type { VariationData, WooCommerceConfig } from './cart';

export type { VariationData };

export type Store = {
	state: {
		variations: Record< number, VariationData >;
	};
};

// Track in-flight requests to avoid duplicate fetches.
// Use window to ensure true singleton across separate bundles.
declare global {
	interface Window {
		__wcVariationPendingRequests?: Record<
			number,
			Promise< VariationData | null >
		>;
	}
}

const getPendingRequests = (): Record<
	number,
	Promise< VariationData | null >
> => {
	if ( ! window.__wcVariationPendingRequests ) {
		window.__wcVariationPendingRequests = {};
	}
	return window.__wcVariationPendingRequests;
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state } = store< Store >(
	'woocommerce/variation-data',
	{
		state: {
			variations: {},
		},
	},
	{ lock: universalLock }
);

/**
 * Fetch variation data from the Store API.
 *
 * This fetches the full variation data and caches it in the store for use by
 * any block that needs variation information (price, stock status, etc.).
 *
 * @param variationId The variation ID to fetch.
 * @return Promise resolving to the variation data or null on error.
 */
export async function fetchVariationData(
	variationId: number
): Promise< VariationData | null > {
	// Return cached data if available.
	if ( state.variations[ variationId ] ) {
		return state.variations[ variationId ];
	}

	const pendingRequests = getPendingRequests();

	// Return existing promise if request is in flight.
	if ( pendingRequests[ variationId ] ) {
		return pendingRequests[ variationId ];
	}

	// Create new request.
	pendingRequests[ variationId ] = ( async () => {
		try {
			const config = getConfig( 'woocommerce' ) as WooCommerceConfig;
			const { restUrl = '/wp-json/', nonce = '' } = config;

			const response = await fetch(
				`${ restUrl }wc/store/v1/products/${ variationId }`,
				{
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						...( nonce && { 'X-WC-Store-API-Nonce': nonce } ),
					},
				}
			);

			if ( ! response.ok ) {
				throw new Error( `HTTP error! status: ${ response.status }` );
			}

			const data = await response.json();

			// Extract variation attributes as a Record<string, string>.
			const attributes: Record< string, string > = {};
			if ( Array.isArray( data.variation ) ) {
				for ( const attr of data.variation ) {
					if ( attr.attribute && attr.value ) {
						attributes[ attr.attribute ] = attr.value;
					}
				}
			}

			// Extract the fields we need and cache them.
			const variationData: VariationData = {
				attributes,
				is_in_stock: data.is_in_stock ?? true,
				sold_individually: data.sold_individually ?? false,
				price_html: data.price_html || '',
				image_id: data.images?.[ 0 ]?.id,
				availability: data.is_in_stock
					? ''
					: data.availability?.availability || '',
				variation_description: data.description || '',
				sku: data.sku || '',
				weight: data.weight,
				dimensions: data.dimensions
					? `${ data.dimensions.length } × ${ data.dimensions.width } × ${ data.dimensions.height }`
					: undefined,
				min: data.add_to_cart?.minimum,
				max: data.add_to_cart?.maximum,
				step: data.add_to_cart?.multiple_of,
			};

			state.variations[ variationId ] = variationData;
			return variationData;
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error(
				`Failed to fetch variation ${ variationId }:`,
				error
			);
			return null;
		} finally {
			delete getPendingRequests()[ variationId ];
		}
	} )();

	return pendingRequests[ variationId ];
}

/**
 * Get cached variation data if available.
 *
 * This does NOT trigger a fetch - use fetchVariationData() if you need
 * to ensure the data is loaded.
 *
 * @param variationId The variation ID.
 * @return The cached variation data or undefined if not cached.
 */
export function getCachedVariationData(
	variationId: number
): VariationData | undefined {
	return state.variations[ variationId ];
}

export { state };
