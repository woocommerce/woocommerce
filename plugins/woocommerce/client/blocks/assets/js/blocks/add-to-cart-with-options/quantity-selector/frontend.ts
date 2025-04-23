/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type { HTMLElementEvent } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { AddToCartWithOptionsStore } from '../frontend';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const getInputElementFromEvent = (
	event: HTMLElementEvent< HTMLButtonElement >
) => {
	const target = event.target as HTMLButtonElement;

	const inputElement = target.parentElement?.querySelector(
		'.input-text.qty.text'
	) as HTMLInputElement | null | undefined;

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

const { actions: wooAddToCartWithOptionsActions } =
	store< AddToCartWithOptionsStore >(
		'woocommerce/add-to-cart-with-options',
		{},
		{ lock: universalLock }
	);

store( 'woocommerce/add-to-cart-with-options', {
	actions: {
		addQuantity: ( event: HTMLElementEvent< HTMLButtonElement > ) => {
			const inputData = getInputData( event );
			if ( ! inputData ) {
				return;
			}
			const { currentValue, maxValue, step, inputElement } = inputData;
			const newValue = currentValue + step;

			if ( maxValue === undefined || newValue <= maxValue ) {
				wooAddToCartWithOptionsActions?.setQuantity( newValue );
				inputElement.value = newValue.toString();
				dispatchChangeEvent( inputElement );
			}
		},
		removeQuantity: ( event: HTMLElementEvent< HTMLButtonElement > ) => {
			const inputData = getInputData( event );
			if ( ! inputData ) {
				return;
			}
			const { currentValue, minValue, step, inputElement } = inputData;
			const newValue = currentValue - step;

			if ( newValue >= minValue ) {
				wooAddToCartWithOptionsActions?.setQuantity( newValue );
				inputElement.value = newValue.toString();
				dispatchChangeEvent( inputElement );
			}
		},
	},
} );
