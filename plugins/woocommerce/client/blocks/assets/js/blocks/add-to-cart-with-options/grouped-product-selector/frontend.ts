/**
 * External dependencies
 */
import { store, getContext, getConfig } from '@wordpress/interactivity';
import type { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import type {
	AddToCartWithOptionsStore,
	Context as AddToCartWithOptionsStoreContext,
} from '../frontend';

import { getNewQuantity } from '../frontend';

interface GroupedCartItem {
	id: number;
	quantity: number;
	variation: SelectedAttributes[];
	type: string;
}

type Option = {
	value: string;
	label: string;
	isSelected: boolean;
};

type Context = AddToCartWithOptionsStoreContext & {
	name: string;
	selectedValue: string | null;
	option: Option;
	options: Option[];
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

export type GroupedProductAddToCartWithOptionsStore =
	AddToCartWithOptionsStore & {
		state: {
			variationId: number | null;
		};
		actions: {
			setQuantity: ( value: number ) => void;
			addToCart: () => void;
		};
		callbacks: {
			validateGrouped: () => void;
		};
	};

const { actions, state } = store< GroupedProductAddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{
		actions: {
			setQuantity( value: number ) {
				const context = getContext< Context >();

				const id = context.childProductId;

				context.quantity = {
					...context.quantity,
					[ id ]: value,
				};
			},
			*addToCart() {

				// Todo: Use the module exports instead of `store()` once the
				// woocommerce store is public.
				yield import( '@woocommerce/stores/woocommerce/cart' );

				const {
					quantity,
					selectedAttributes,
					productType,
					groupedProductIds,
				} = getContext< Context >();

				const addedItems: GroupedCartItem[] = [];

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

				const { actions } = store< WooCommerce >(
					'woocommerce',
					{},
					{ lock: universalLock }
				);

				yield actions.batchAddCartItems( addedItems );
				
			},
		},
		callbacks: {
			validateGrouped: () => {

				actions.clearErrors( 'grouped-product' );

				const { errorMessages } = getConfig();

				const { quantity } = getContext< Context >();

				const totalQuantity = Object.values(quantity).reduce((sum, val) => sum + val, 0);
				
				// At least one quantity is greater than 0.
				if ( totalQuantity <= 0 ) {
					actions.addError( {
						code: 'groupedProductAddToCartMissingItems',
						message: errorMessages?.groupedProductAddToCartMissingItems || '',
						group: 'grouped-product',
					} );
					return;
				}
			}

		},
	},
	{ lock: universalLock }
);
