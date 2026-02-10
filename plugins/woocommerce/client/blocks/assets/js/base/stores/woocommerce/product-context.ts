/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type { ProductResponseItem } from '@woocommerce/types';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const productsStore = store< ProductsStore >( 'woocommerce/products', {
	state: {
		products: {},
		productVariations: {},
	},
} );

export type ProductContextState = {
	productId: number;
	variationId: number | null;
};

const productContextStore = store< {
	state: ProductContextState & {
		product: ProductResponseItem | undefined;
		variation: ProductResponseItem | undefined;
		selectedProduct: ProductResponseItem | undefined;
	};
	actions: {
		setProductId: ( productId: number ) => void;
		setVariationId: ( variationId: number | null ) => void;
	};
} >(
	'woocommerce/product-context',
	{
		state: {
			get product(): ProductResponseItem | undefined {
				return productsStore.state.products[
					productContextStore.state.productId
				];
			},
			get variation(): ProductResponseItem | undefined {
				const { variationId } = productContextStore.state;
				if ( variationId === null ) {
					return undefined;
				}
				return productsStore.state.productVariations[ variationId ];
			},
			get selectedProduct(): ProductResponseItem | undefined {
				return (
					productContextStore.state.variation ??
					productContextStore.state.product
				);
			},
		},
		actions: {
			setProductId: ( productId: number ) => {
				productContextStore.state.productId = productId;
			},
			setVariationId: ( variationId: number | null ) => {
				productContextStore.state.variationId = variationId;
			},
		},
	},
	{ lock: universalLock }
);

export type ProductContextStore = typeof productContextStore;
