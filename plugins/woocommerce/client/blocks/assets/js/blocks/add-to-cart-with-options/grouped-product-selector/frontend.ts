/**
 * External dependencies
 */
import { store, getContext, getConfig } from '@wordpress/interactivity';
import type { ClientCartItem } from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import type {
	AddToCartWithOptionsStore,
	Context as AddToCartWithOptionsStoreContext,
} from '../frontend';

import { getNewQuantity } from '../frontend';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

export type GroupedProductAddToCartWithOptionsStore =
	AddToCartWithOptionsStore & {
		actions: {
			setQuantity: ( value: number ) => void;
			addToCart: () => void;
		};
		callbacks: {
			validateGrouped: () => void;
		};
	};

const { actions } = store< GroupedProductAddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{
		actions: {
			*addToCart() {
				// Todo: Use the module exports instead of `store()` once the
				// woocommerce store is public.
				yield import( '@woocommerce/stores/woocommerce/cart' );

				const {
					quantity,
					selectedAttributes,
					productType,
					groupedProductIds,
				} = getContext< AddToCartWithOptionsStoreContext >();

				const addedItems: ClientCartItem[] = [];

				for ( const childProductId of groupedProductIds ) {
					if ( quantity[ childProductId ] === 0 ) {
						continue;
					}

					const newQuantity = getNewQuantity(
						childProductId,
						quantity[ childProductId ]
					);

					addedItems.push( {
						id: childProductId,
						quantity: newQuantity,
						variation: selectedAttributes,
						type: productType,
					} );
				}

				const { actions: wooActions } = store< WooCommerce >(
					'woocommerce',
					{},
					{ lock: universalLock }
				);

				yield wooActions.batchAddCartItems( addedItems );
			},
		},
		callbacks: {
			validateGrouped: () => {
				actions.clearErrors( 'grouped-product' );

				const { errorMessages } = getConfig();

				const { quantity } =
					getContext< AddToCartWithOptionsStoreContext >();

				const hasNonZeroQuantity = Object.values( quantity ).some(
					( val ) => val > 0
				);

				if ( ! hasNonZeroQuantity ) {
					actions.addError( {
						code: 'groupedProductAddToCartMissingItems',
						message:
							errorMessages?.groupedProductAddToCartMissingItems ||
							'',
						group: 'grouped-product',
					} );
				}
			},
		},
	},
	{ lock: universalLock }
);
