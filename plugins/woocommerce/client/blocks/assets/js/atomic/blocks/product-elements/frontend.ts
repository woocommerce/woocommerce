/**
 * External dependencies
 */
import {
	getElement,
	store,
	getContext,
	getConfig,
} from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/product-context';
import type { ProductContextStore } from '@woocommerce/stores/woocommerce/product-context';
import type { ProductResponseItem } from '@woocommerce/types';
import type { WooCommerceConfig } from '@woocommerce/stores/woocommerce/cart';
import { sanitizeHTML } from '@woocommerce/sanitize';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productContextState } = store< ProductContextStore >(
	'woocommerce/product-context',
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
		| 'dimensions'
		| 'variation_description'
		| 'min'
		| 'max'
		| 'step';
};

/**
 * Maps productElementKey values to the corresponding field on ProductResponseItem.
 * Keys not in this map (min, max, step) fall through to the legacy getConfig path.
 */
const FIELD_MAP: Record<
	string,
	( p: ProductResponseItem ) => string | undefined
> = {
	price_html: ( p ) => p.price_html,
	sku: ( p ) => p.sku,
	availability: ( p ) => p.stock_availability?.text,
	weight: ( p ) => p.formatted_weight,
	dimensions: ( p ) => p.formatted_dimensions,
	variation_description: ( p ) => p.description,
};

type ProductData = Record< string, string | number | undefined >;

const productElementStore = store(
	'woocommerce/product-elements',
	{
		state: {
			get productData(): ProductData | undefined {
				const product = productContextState?.product;

				if ( ! product ) {
					return undefined;
				}

				const activeProduct =
					productContextState?.selectedVariation ?? product;

				const { productElementKey } = getContext< Context >();

				const fieldAccessor = FIELD_MAP[ productElementKey ];

				// For keys in FIELD_MAP, resolve directly from the products store.
				if ( fieldAccessor ) {
					return {
						[ productElementKey ]:
							fieldAccessor( activeProduct ),
					} as ProductData;
				}

				// For unmigrated keys (min, max, step), fall through to getConfig.
				const { products } = getConfig(
					'woocommerce'
				) as WooCommerceConfig;

				if ( ! products ) {
					return undefined;
				}

				const variationId =
					productContextState?.selectedVariation?.id;

				return (
					products?.[ product.id ]?.variations?.[
						variationId || 0
					] || products?.[ product.id ]
				);
			},
		},
		callbacks: {
			updateValue: () => {
				const element = getElement();

				if ( ! element.ref || ! productContextState?.product ) {
					return;
				}

				const { productElementKey } = getContext< Context >();

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
