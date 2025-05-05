/**
 * External dependencies
 */
import type { FormEvent, HTMLElementEvent } from 'react';
import { store, getContext } from '@wordpress/interactivity';
import type { Store as WooCommerce } from '@woocommerce/stores/woocommerce/cart';
import type { CartVariationItem } from '@woocommerce/types';

export type AvailableVariation = {
	attributes: Record< string, string >;
};

export type Context = {
	productId: number;
	variation: CartVariationItem[];
	availableVariations: AvailableVariation[];
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

const getInputElementFromEvent = (
	event: HTMLElementEvent< HTMLButtonElement >
) => {
	const target = event.target as HTMLButtonElement;

	const inputElement = target.parentElement?.querySelector(
		'.input-text.qty.text'
	) as HTMLInputElement | null;

	return inputElement;
};

const getInputData = ( event: HTMLElementEvent< HTMLButtonElement > ) => {
	const inputElement = getInputElementFromEvent( event );

	if ( ! inputElement ) {
		return;
	}

	const parsedValue = parseInt( inputElement.value, 10 );
	const parsedMinValue = parseInt( inputElement.min, 10 );
	const parsedMaxValue = parseInt( inputElement.max, 10 );
	const parsedStep = parseInt( inputElement.step, 10 );

	const currentValue = isNaN( parsedValue ) ? 0 : parsedValue;
	const minValue = isNaN( parsedMinValue ) ? 1 : parsedMinValue;
	const maxValue = isNaN( parsedMaxValue ) ? undefined : parsedMaxValue;
	const step = isNaN( parsedStep ) ? 1 : parsedStep;

	return {
		currentValue,
		minValue,
		maxValue,
		step,
		inputElement,
	};
};

const dispatchChangeEvent = ( inputElement: HTMLInputElement ) => {
	const event = new Event( 'change' );
	inputElement.dispatchEvent( event );
};

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
			increaseQuantity: (
				event: HTMLElementEvent< HTMLButtonElement >
			) => {
				const inputData = getInputData( event );
				if ( ! inputData ) {
					return;
				}
				const { currentValue, maxValue, step, inputElement } =
					inputData;
				const newValue = currentValue + step;

				if ( maxValue === undefined || newValue <= maxValue ) {
					addToCartWithOptionsStore.actions.setQuantity( newValue );
					inputElement.value = newValue.toString();
					dispatchChangeEvent( inputElement );
				}
			},
			decreaseQuantity: (
				event: HTMLElementEvent< HTMLButtonElement >
			) => {
				const inputData = getInputData( event );
				if ( ! inputData ) {
					return;
				}
				const { currentValue, minValue, step, inputElement } =
					inputData;
				const newValue = currentValue - step;

				if ( newValue >= minValue ) {
					addToCartWithOptionsStore.actions.setQuantity( newValue );
					inputElement.value = newValue.toString();
					dispatchChangeEvent( inputElement );
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
	},
	{ lock: true }
);

export type AddToCartWithOptionsStore = typeof addToCartWithOptionsStore;
