/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';
import type { ProductResponseItem } from '@woocommerce/types';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const productsStore = store< ProductsStore >( 'woocommerce/products', {} );

/**
 * Per-element context set via data-wp-context on wrapper elements (e.g. the
 * SingleProduct block). When present, this takes precedence over the
 * server-hydrated state so that each product in a loop gets its own IDs.
 */
type ProductContext = {
	productId: number;
	variationId?: number | null;
};

export type ProductContextStore = {
	state: {
		productId: number;
		variationId: number | null;
		/**
		 * The main product for this page/block. Always the top-level product
		 * (e.g. the variable product "Hoodie"), never a variation.
		 * Resolves productId from per-block context when available.
		 */
		product: ProductResponseItem | undefined;
		/**
		 * The currently selected variation, or null if none is selected.
		 * For simple/grouped products, this is always null.
		 */
		selectedVariation: ProductResponseItem | null;
	};
};

const productContextStore = store< ProductContextStore >(
	'woocommerce/product-context',
	{
		state: {
			get product(): ProductResponseItem | undefined {
				const context = getContext< ProductContext >();
				const productId =
					context?.productId ||
					productContextStore.state.productId;

				if ( ! productId ) {
					return undefined;
				}
				return productsStore.state.products[ productId ];
			},

			get selectedVariation(): ProductResponseItem | null {
				const { variationId } = productContextStore.state;
				if ( ! variationId ) {
					return null;
				}
				return (
					productsStore.state.productVariations[ variationId ] ?? null
				);
			},
		},
	},
	{ lock: universalLock }
);
