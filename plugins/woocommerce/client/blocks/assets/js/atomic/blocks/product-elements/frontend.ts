/**
 * External dependencies
 */
import {
	getElement,
	store,
	getContext,
	getConfig,
} from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/product-data';
import type { ProductDataStore } from '@woocommerce/stores/woocommerce/product-data';
import type {
	ProductData,
	WooCommerceConfig,
} from '@woocommerce/stores/woocommerce/cart';
import { sanitizeHTML } from '@woocommerce/sanitize';

/**
 * Internal dependencies
 */
import {
	fetchVariationData,
	getCachedVariationData,
} from '../../../base/utils/variations/variation-data-store';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productDataState } = store< ProductDataStore >(
	'woocommerce/product-data',
	{},
	{ lock: universalLock }
);

const ALLOWED_TAGS = [
	'a',
	'b',
	'em',
	'i',
	'strong',
	'p',
	'br',
	'span',
	'bdi',
	'del',
	'ins',
];
const ALLOWED_ATTR = [
	'class',
	'target',
	'href',
	'rel',
	'name',
	'download',
	'aria-hidden',
];

export type Context = {
	productElementKey:
		| 'price_html'
		| 'availability'
		| 'sku'
		| 'weight'
		| 'dimensions';
};

/**
 * Check if lazy loading is enabled for a product.
 *
 * @param productId The product ID.
 * @return True if lazy loading is enabled.
 */
function isLazyLoadEnabled( productId: number ): boolean {
	const { products } = getConfig( 'woocommerce' ) as WooCommerceConfig;
	return products?.[ productId ]?.lazy_load === true;
}

/**
 * Get variation data from pre-loaded config or shared cache.
 *
 * @param productId   The parent product ID.
 * @param variationId The variation ID.
 * @return The variation data or undefined.
 */
function getVariationData(
	productId: number,
	variationId: number
): ProductData | undefined {
	const { products } = getConfig( 'woocommerce' ) as WooCommerceConfig;

	// First check if data exists in config (pre-loaded).
	const preloadedData = products?.[ productId ]?.variations?.[ variationId ];
	if ( preloadedData && 'price_html' in preloadedData ) {
		return preloadedData;
	}

	// Check shared cache for lazy-loaded data.
	const cachedData = getCachedVariationData( variationId );
	if ( cachedData ) {
		return cachedData as ProductData;
	}

	return undefined;
}

const productElementStore = store(
	'woocommerce/product-elements',
	{
		state: {
			get productData(): ProductData | undefined {
				if ( ! productDataState?.productId ) {
					return undefined;
				}

				const { products } = getConfig(
					'woocommerce'
				) as WooCommerceConfig;

				if ( ! products ) {
					return undefined;
				}

				const variationId = productDataState?.variationId || 0;

				// If a variation is selected, try to get its data.
				if ( variationId ) {
					const variationData = getVariationData(
						productDataState.productId,
						variationId
					);
					if ( variationData ) {
						return variationData;
					}
				}

				// Fall back to parent product data.
				return products?.[ productDataState.productId ];
			},
		},
		callbacks: {
			updateValue: async () => {
				const element = getElement();

				if ( ! element.ref || ! productDataState?.productId ) {
					return;
				}

				const { productElementKey } = getContext< Context >();
				const variationId = productDataState?.variationId || 0;
				const productId = productDataState.productId;

				// Check if we need to fetch data lazily.
				if (
					variationId &&
					isLazyLoadEnabled( productId ) &&
					! getVariationData( productId, variationId )
				) {
					// Show loading state.
					element.ref.style.opacity = '0.5';

					// Fetch the variation data (uses shared cache).
					const fetchedData = await fetchVariationData( variationId );

					// Remove loading state.
					element.ref.style.opacity = '1';

					if ( fetchedData ) {
						const html =
							fetchedData[ productElementKey as keyof typeof fetchedData ];
						if ( typeof html === 'string' ) {
							element.ref.innerHTML = sanitizeHTML( html, {
								tags: ALLOWED_TAGS,
								attr: ALLOWED_ATTR,
							} );
						}
					}
					return;
				}

				// Use pre-loaded or cached data.
				const productElementHtml =
					productElementStore?.state?.productData?.[
						productElementKey
					];

				if ( typeof productElementHtml === 'string' ) {
					element.ref.innerHTML = sanitizeHTML( productElementHtml, {
						tags: ALLOWED_TAGS,
						attr: ALLOWED_ATTR,
					} );
				}
			},
		},
	},
	{ lock: true }
);

export type ProductElementStore = typeof productElementStore;