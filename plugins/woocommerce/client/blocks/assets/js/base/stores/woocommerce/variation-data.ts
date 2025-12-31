/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';

/**
 * Shape of variation data stored in the cache.
 */
export type VariationData = {
	price_html: string;
	is_in_stock: boolean;
	sold_individually: boolean;
	sku: string;
	availability?: string;
};

export type Store = {
	state: {
		variations: Record< number, VariationData >;
	};
};

// Track in-flight requests to avoid duplicate fetches.
const pendingRequests: Record< number, Promise< VariationData | null > > = {};

const { state } = store< Store >(
	'woocommerce/variation-data',
	{
		state: {
			variations: {},
		},
	},
	{ lock: true }
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

	// Return existing promise if request is in flight.
	if ( pendingRequests[ variationId ] ) {
		return pendingRequests[ variationId ];
	}

	// Create new request.
	pendingRequests[ variationId ] = ( async () => {
		try {
			const response = await fetch(
				`/wp-json/wc/store/v1/products/${ variationId }`,
				{
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
					},
				}
			);

			if ( ! response.ok ) {
				throw new Error( `HTTP error! status: ${ response.status }` );
			}

			const data = await response.json();

			// Extract the fields we need and cache them.
			const variationData: VariationData = {
				price_html: data.price_html || '',
				is_in_stock: data.is_in_stock ?? true,
				sold_individually: data.sold_individually ?? false,
				sku: data.sku || '',
				availability: data.is_in_stock
					? ''
					: data.availability?.availability || '',
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
			delete pendingRequests[ variationId ];
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