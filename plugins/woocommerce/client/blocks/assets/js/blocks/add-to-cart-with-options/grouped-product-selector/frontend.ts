/**
 * External dependencies
 */
import { store, getContext, getConfig } from '@wordpress/interactivity';
import type {
	ClientCartItem,
	Store as WooCommerce,
} from '@woocommerce/stores/woocommerce/cart';
import type { ProductDataStore } from '@woocommerce/stores/woocommerce/product-data';

/**
 * Internal dependencies
 */
import type {
	AddToCartWithOptionsStore,
	Context as AddToCartWithOptionsStoreContext,
} from '../frontend';
import { getNewQuantity, getProductData } from '../frontend';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productDataState } = store< ProductDataStore >(
	'woocommerce/product-data',
	{},
	{ lock: universalLock }
);

export type GroupedProductAddToCartWithOptionsStore =
	AddToCartWithOptionsStore & {
		actions: {
			validateQuantity: ( value?: number ) => void;
			addToCart: () => void;
		};
		callbacks: {
			validateQuantities: () => void;
		};
	};

const { actions } = store< GroupedProductAddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{
		actions: {
			validateQuantity() {
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
					const productObject = getProductData(
						Number( id ),
						productDataState.selectedAttributes
					);
					if ( ! productObject ) {
						return false;
					}
					return (
						qty !== 0 &&
						( qty < productObject.min || qty > productObject.max )
					);
				} );

				if ( hasInvalidQuantity ) {
					actions.addError( {
						code: 'invalidQuantities',
						message: errorMessages?.invalidQuantities || '',
						group: 'invalid-quantities',
					} );
				}
			},
			*addToCart() {
				// Todo: Use the module exports instead of `store()` once the
				// woocommerce store is public.
				yield import( '@woocommerce/stores/woocommerce/cart' );

				const { quantity, groupedProductIds } =
					getContext< AddToCartWithOptionsStoreContext >();

				const addedItems: ClientCartItem[] = [];

				for ( const childProductId of groupedProductIds ) {
					if ( quantity[ childProductId ] === 0 ) {
						continue;
					}

					const newQuantity = getNewQuantity(
						childProductId,
						quantity[ childProductId ]
					);

					const productObject = getProductData(
						Number( childProductId ),
						productDataState.selectedAttributes
					);

					if ( ! productObject ) {
						continue;
					}

					addedItems.push( {
						id: Number( childProductId ),
						quantity: newQuantity,
						variation: productDataState.selectedAttributes || [],
						type: productObject.type,
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
				actions.validateQuantity();
			},
		},
	},
	{ lock: universalLock }
);
