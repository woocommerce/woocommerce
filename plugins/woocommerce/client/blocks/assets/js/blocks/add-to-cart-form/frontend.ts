/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import { HTMLElementEvent } from '@woocommerce/types';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const getInputElementFromEvent = (
	event: HTMLElementEvent< HTMLButtonElement >
) => {
	const target = event.target as HTMLButtonElement;

	const inputElement = target.parentElement?.querySelector(
		'.wc-block-components-quantity-selector__input'
	) as HTMLInputElement | null | undefined;

	return inputElement;
};

const getInputData = ( event: HTMLElementEvent< HTMLButtonElement > ) => {
	const inputElement = getInputElementFromEvent( event );

	if ( ! inputElement ) {
		return;
	}

	const parsedValue = parseFloat( inputElement.value );
	const parsedMinValue = parseFloat( inputElement.min );
	const parsedMaxValue = parseFloat( inputElement.max );
	const parsedStep = parseFloat( inputElement.step );

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

const roundDecimals = (
	value: number,
	min: number,
	max: number,
	step: number
): string => {
	const stepDecimals = ( step.toString().split( '.' )[ 1 ] || '' ).length;
	const minDecimals = ( min.toString().split( '.' )[ 1 ] || '' ).length;
	const maxDecimals = ( max.toString().split( '.' )[ 1 ] || '' ).length;
	const decimals = Math.max( stepDecimals, minDecimals, maxDecimals );
	return value.toFixed( decimals );
};

/**
 * Manually dispatches a 'change' event on the quantity input element.
 *
 * When users click the plus/minus stepper buttons, no 'change' event is fired
 * since there is no direct interaction with the input. However, some extensions
 * rely on the change event to detect quantity changes. This function ensures
 * those extensions continue working by programmatically dispatching the event.
 *
 * @see https://github.com/woocommerce/woocommerce/issues/53031
 *
 * @param inputElement - The quantity input element to dispatch the event on.
 */
const dispatchChangeEvent = ( inputElement: HTMLInputElement ) => {
	const event = new Event( 'change', { bubbles: true } );

	inputElement.dispatchEvent( event );
};

// Note: this store is also used by the Add to Cart + Options block when
// rendering third party product types that don't use block template parts.
store(
	'woocommerce/add-to-cart-form',
	{
		state: {
			get allowsIncrease() {
				return true;
			},
			get allowsDecrease() {
				return true;
			},
		},
		actions: {
			increaseQuantity: (
				event: HTMLElementEvent< HTMLButtonElement >
			) => {
				const inputData = getInputData( event );
				if ( ! inputData ) {
					return;
				}
				const { currentValue, minValue, maxValue, step, inputElement } =
					inputData;
				const newValue = currentValue + step;

				if ( maxValue === undefined || newValue <= maxValue ) {
					inputElement.value = roundDecimals(
						newValue,
						minValue,
						maxValue ?? Infinity,
						step
					);
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
				const { currentValue, minValue, maxValue, step, inputElement } =
					inputData;
				const newValue = currentValue - step;

				if ( newValue >= minValue ) {
					inputElement.value = roundDecimals(
						newValue,
						minValue,
						maxValue ?? Infinity,
						step
					);
					dispatchChangeEvent( inputElement );
				}
			},
		},
	},
	{ lock: universalLock }
);
