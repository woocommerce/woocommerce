/**
 * Shared variation data store for lazy-loading variation information.
 *
 * This module provides a centralized cache and fetch mechanism for variation
 * data that can be used by multiple blocks (ProductPrice, AddToCartWithOptions, etc.)
 * to avoid duplicate API requests and share cached data.
 */

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

// Shared cache for fetched variation data.
const variationDataCache: Record< number, VariationData > = {};

// Track in-flight requests to avoid duplicate fetches.
const pendingRequests: Record< number, Promise< VariationData | null > > = {};

/**
 * Fetch variation data from the Store API.
 *
 * This fetches the full variation data and caches it for use by any block
 * that needs variation information (price, stock status, etc.).
 *
 * @param variationId The variation ID to fetch.
 * @return Promise resolving to the variation data or null on error.
 */
export async function fetchVariationData(
	variationId: number
): Promise< VariationData | null > {
	// Return cached data if available.
	if ( variationDataCache[ variationId ] ) {
		return variationDataCache[ variationId ];
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

			variationDataCache[ variationId ] = variationData;
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
	return variationDataCache[ variationId ];
}

/**
 * Check if variation data is cached.
 *
 * @param variationId The variation ID.
 * @return True if the variation data is cached.
 */
export function isVariationDataCached( variationId: number ): boolean {
	return variationId in variationDataCache;
}

/**
 * Manually set variation data in the cache.
 *
 * This can be used to populate the cache from pre-loaded data
 * without making an API request.
 *
 * @param variationId   The variation ID.
 * @param variationData The variation data to cache.
 */
export function setCachedVariationData(
	variationId: number,
	variationData: VariationData
): void {
	variationDataCache[ variationId ] = variationData;
}