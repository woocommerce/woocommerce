/**
 * External dependencies
 */
import { store, getContext, getConfig } from '@wordpress/interactivity';
import type {
	ClientCartItem,
	Store as WooCommerce,
} from '@woocommerce/stores/woocommerce/cart';
import '@woocommerce/stores/woocommerce/products';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import type {
	AddToCartWithOptionsStore,
	Context as AddToCartWithOptionsStoreContext,
} from '../frontend';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

export type GroupedProductAddToCartWithOptionsStore =
	AddToCartWithOptionsStore & {
		actions: {
			validateGroupedProductQuantity: () => void;
			batchAddToCart: () => void;
		};
		callbacks: {
			validateQuantities: () => void;
		};
	};

const { actions } = store< GroupedProductAddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{
		actions: {
			validateGroupedProductQuantity() {
				actions.clearErrors( 'invalid-quantities' );

				const { errorMessages } = getConfig();
				const context =
					getContext< AddToCartWithOptionsStoreContext >();

				// Validate that at least one product quantity is above 0.
				const hasNonZeroQuantity = Object.values(
					context.quantity
				).some( ( qty ) => qty > 0 );

				if ( ! hasNonZeroQuantity ) {
					actions.addError( {
						code: 'groupedProductAddToCartMissingItems',
						message:
							errorMessages?.groupedProductAddToCartMissingItems ||
							'',
						group: 'invalid-quantities',
					} );

					return;
				}

				// Validate that all product quantities are within the min and max (or 0).
				const hasInvalidQuantity = Object.entries(
					context.quantity
				).some( ( [ id, qty ] ) => {
					const product = productsState.findProduct( {
						id: Number( id ),
						selectedAttributes: context.selectedAttributes,
					} );
					if ( ! product ) {
						return false;
					}
					const { minimum, maximum } = product.add_to_cart;
					return qty !== 0 && ( qty < minimum || qty > maximum );
				} );

				if ( hasInvalidQuantity ) {
					actions.addError( {
						code: 'invalidQuantities',
						message: errorMessages?.invalidQuantities || '',
						group: 'invalid-quantities',
					} );
				}
			},
			*batchAddToCart() {
				// Todo: Use the module exports instead of `store()` once the
				// woocommerce store is public.
				yield import( '@woocommerce/stores/woocommerce/cart' );

				const { quantity, selectedAttributes, groupedProductIds } =
					getContext< AddToCartWithOptionsStoreContext >();

				const addedItems: ClientCartItem[] = [];

				for ( const childProductId of groupedProductIds ) {
					if ( quantity[ childProductId ] === 0 ) {
						continue;
					}

					const product = productsState.findProduct( {
						id: Number( childProductId ),
						selectedAttributes,
					} );

					if ( ! product ) {
						continue;
					}

					addedItems.push( {
						id: Number( childProductId ),
						quantityToAdd: quantity[ childProductId ],
						variation: selectedAttributes,
						type: product.type,
					} );
				}

				const { actions: wooActions } = store< WooCommerce >(
					'woocommerce',
					{},
					{ lock: universalLock }
				);

				yield wooActions.batchAddCartItems( addedItems, {
					showCartUpdatesNotices: false,
				} );
			},
		},
		callbacks: {
			validateQuantities() {
				actions.validateGroupedProductQuantity();
			},
		},
	},
	{ lock: universalLock }
);
