/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { ProductsStore } from './products';
import './products'; // Ensure store is registered (side effect)

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

/**
 * Get a reference to the products store.
 */
const productsStore = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

/**
 * The state shape for the product context store.
 * State is hydrated server-side via wp_interactivity_state() in ProductContextStore::load_context().
 *
 * Note: selectedAttributes is intentionally NOT part of this state.
 * It is managed by the add-to-cart-with-options form context because it's
 * form-specific state. The product-context store only tracks which
 * product/variation is being viewed, not form state like attribute selections.
 */
export type ProductContextStoreState = {
	/**
	 * The main product ID (hydrated from server).
	 */
	productId: number;
	/**
	 * The selected variation ID, or null if no variation is selected (hydrated from server).
	 */
	variationId: number | null;
};

/**
 * Computed getters for the product context store.
 */
export type ProductContextStoreGetters = {
	/**
	 * Returns the main product from the products store.
	 */
	product: ProductResponseItem | undefined;
	/**
	 * Returns the selected variation from the products store, if variationId is set.
	 */
	variation: ProductResponseItem | undefined;
	/**
	 * Returns the currently active product for display/cart operations.
	 * This is the variation if one is selected, otherwise the main product.
	 */
	selectedProduct: ProductResponseItem | undefined;
	/**
	 * Returns the parent product if the current context is a variation.
	 */
	parent: ProductResponseItem | undefined;
};

/**
 * Actions for the product context store.
 */
export type ProductContextStoreActions = {
	/**
	 * Set the product ID in context.
	 */
	setProductId: ( id: number ) => void;
	/**
	 * Set the selected variation ID.
	 */
	setVariationId: ( id: number | null ) => void;
};

/**
 * The product context store type definition.
 */
export type ProductContextStore = {
	state: ProductContextStoreState & ProductContextStoreGetters;
	actions: ProductContextStoreActions;
};

/**
 * The woocommerce/product-context store.
 *
 * This store manages the context of which product (and optionally variation)
 * is currently being viewed or interacted with. State is hydrated server-side
 * via wp_interactivity_state() in ProductContextStore::load_context().
 *
 * Product data is accessed via the woocommerce/products store.
 *
 * State structure (hydrated from server):
 * - productId: The main product ID
 * - variationId: The selected variation ID (or null)
 *
 * Note: selectedAttributes is NOT part of this store. It's managed by the
 * add-to-cart-with-options form context since it's form-specific state.
 *
 * Computed getters:
 * - product: The main product from products store
 * - variation: The selected variation from products store
 * - selectedProduct: The active product (variation ?? product)
 * - parent: The parent product if viewing a variation
 */
const productContextStore = store< ProductContextStore >(
	'woocommerce/product-context',
	{
		state: {
			// These values are hydrated server-side via wp_interactivity_state()
			// in ProductContextStore::load_context().
			productId: 0,
			variationId: null,

			get product(): ProductResponseItem | undefined {
				const { productId } = productContextStore.state;
				if ( ! productId ) {
					return undefined;
				}
				return productsStore.state.products[ productId ];
			},

			get variation(): ProductResponseItem | undefined {
				const { variationId } = productContextStore.state;
				if ( ! variationId ) {
					return undefined;
				}
				return productsStore.state.productVariations[ variationId ];
			},

			get selectedProduct(): ProductResponseItem | undefined {
				const { variation, product } = productContextStore.state;
				return variation ?? product;
			},

			get parent(): ProductResponseItem | undefined {
				const { variation, product } = productContextStore.state;
				// If we have a variation selected, return the main product as parent
				if ( variation ) {
					return product;
				}
				// If the product itself is a variation, look up its parent
				if ( product?.parent_id ) {
					return productsStore.state.products[ product.parent_id ];
				}
				return undefined;
			},
		},
		actions: {
			setProductId( id: number ): void {
				productContextStore.state.productId = id;
			},

			setVariationId( id: number | null ): void {
				productContextStore.state.variationId = id;
			},
		},
	},
	{ lock: universalLock }
);
