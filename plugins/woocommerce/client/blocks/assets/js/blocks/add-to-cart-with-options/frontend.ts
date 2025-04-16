/**
 * External dependencies
 */
import type { FormEvent } from 'react';
import { store, getContext } from '@wordpress/interactivity';
import type { Store as WooCommerce } from '@woocommerce/stores/woocommerce/cart';
import type { CartVariationItem } from '@woocommerce/types';

type Context = {
	productId: number;
	variation: CartVariationItem[];
	quantity: number;
	tempQuantity: number;
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: wooState } = store< WooCommerce >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

const addToCartWithOptionsStore = store(
	'woocommerce/add-to-cart-with-options',
	{
		actions: {
			setQuantity( value: number ) {
				const context = getContext< Context >();
				context.quantity = value;
			},
			setAttribute( attribute: string, value: string ) {
				const context = getContext< Context >();
				const index = context.variation.findIndex(
					( variation ) => variation.attribute === attribute
				);
				if ( index >= 0 ) {
					context.variation[ index ] = {
						attribute,
						value,
					};
				} else {
					context.variation.push( {
						attribute,
						value,
					} );
				}
			},
			removeAttribute( attribute: string ) {
				const context = getContext< Context >();
				const index = context.variation.findIndex(
					( variation ) => variation.attribute === attribute
				);
				if ( index >= 0 ) {
					context.variation.splice( index, 1 );
				}
			},
			*handleSubmit( event: FormEvent< HTMLFormElement > ) {
				event.preventDefault();

				// Todo: Use the module exports instead of `store()` once the
				// woocommerce store is public.
				yield import( '@woocommerce/stores/woocommerce/cart' );

				const { actions } = store< WooCommerce >(
					'woocommerce',
					{},
					{ lock: universalLock }
				);

				const { productId, quantity, variation } =
					getContext< Context >();
				const product = wooState.cart?.items.find(
					( item ) => item.id === productId
				);
				const currentQuantity = product?.quantity || 0;

				yield actions.addCartItem( {
					id: productId,
					quantity: currentQuantity + quantity,
					variation,
				} );
			},
		},
	}
);

export type AddToCartWithOptionsStore = typeof addToCartWithOptionsStore;
