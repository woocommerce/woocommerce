/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import { HTMLElementEvent } from '@woocommerce/types';

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

const updateButtonStates = ( inputElement: HTMLInputElement ) => {
	const getDataFromInput = ( input: HTMLInputElement ) => {
		const mockEvent = {
			target: {
				parentElement: input.parentElement,
			},
		} as HTMLElementEvent< HTMLButtonElement >;
		const data = getInputData( mockEvent );
		return (
			data || {
				currentValue: 0,
				minValue: 1,
				maxValue: undefined,
				step: 1,
				inputElement: input,
			}
		);
	};

	const { currentValue, minValue, maxValue, step } =
		getDataFromInput( inputElement );

	if ( ! currentValue || ! minValue || ! step ) {
		return;
	}

	const minusButton = inputElement.parentElement?.querySelector(
		'.wc-block-components-quantity-selector__button--minus'
	) as HTMLButtonElement | null;

	const plusButton = inputElement.parentElement?.querySelector(
		'.wc-block-components-quantity-selector__button--plus'
	) as HTMLButtonElement | null;

	if ( minusButton ) {
		minusButton.disabled = currentValue - step < minValue;
	}

	if ( plusButton ) {
		plusButton.disabled =
			maxValue !== undefined && currentValue + step > maxValue;
	}
};

store( 'woocommerce/add-to-cart-form', {
	state: {},
	actions: {
		addQuantity: ( event: HTMLElementEvent< HTMLButtonElement > ) => {
			const inputData = getInputData( event );
			if ( ! inputData ) {
				return;
			}
			const { currentValue, maxValue, step, inputElement } = inputData;
			const newValue = currentValue + step;

			if ( maxValue === undefined || newValue <= maxValue ) {
				inputElement.value = newValue.toString();
				dispatchChangeEvent( inputElement );
				updateButtonStates( inputElement );
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
				inputElement.value = newValue.toString();
				dispatchChangeEvent( inputElement );
				updateButtonStates( inputElement );
			}
		},
	},
	callbacks: {
		init: () => {
			const inputElement = document.querySelector(
				'.wc-block-components-quantity-selector__input'
			) as HTMLInputElement | null;

			if ( inputElement ) {
				updateButtonStates( inputElement );
			}
		},
	},
} );
