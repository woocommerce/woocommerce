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
import type {
	ProductData,
	WooCommerceConfig,
} from '@woocommerce/stores/woocommerce/cart';
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
		| 'dimensions';
};

const productElementStore = store(
	'woocommerce/product-elements',
	{
		state: {
			get productData(): ProductData | undefined {
				const mainProductId =
					productContextState?.parentProduct?.id ??
					productContextState?.currentProduct?.id;

				if ( ! mainProductId ) {
					return undefined;
				}

				const { products } = getConfig(
					'woocommerce'
				) as WooCommerceConfig;

				if ( ! products ) {
					return undefined;
				}

				const variationId =
					productContextState?.parentProduct !== null
						? productContextState?.currentProduct?.id
						: undefined;

				return (
					products?.[ mainProductId ]?.variations?.[
						variationId || 0
					] || products?.[ mainProductId ]
				);
			},
		},
		callbacks: {
			updateValue: () => {
				const element = getElement();

				if ( ! element.ref || ! productContextState?.currentProduct ) {
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
