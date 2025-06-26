/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';
import { HTMLElementEvent } from '@woocommerce/types';

export type Context = {
	productId?: number;
	productType?: string;
	quantity: Record< number, number >;
	childProductId?: number;
};

const getInputElementFromEvent = (
	event: HTMLElementEvent< HTMLButtonElement >
) => {
	const target = event.target as HTMLButtonElement;

	const inputElement = target.parentElement?.querySelector(
		'.wc-block-components-quantity-selector__input'
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
	// Parse childProductId from input name if present.
	const childProductIdMatch = inputElement.name.match( /quantity\[(\d+)\]/ );
	const childProductId = childProductIdMatch
		? parseInt( childProductIdMatch[ 1 ], 10 )
		: undefined;
	return {
		currentValue,
		minValue,
		maxValue,
		step,
		inputElement,
		childProductId,
	};
};

const dispatchChangeEvent = ( inputElement: HTMLInputElement ) => {
	const event = new Event( 'change' );
	inputElement.dispatchEvent( event );
};

store( 'woocommerce/add-to-cart-form', {
	state: {
		get allowsDecrease() {
			const context = getContext< Context >();
			const { quantity, childProductId, productType } = context;
			let currentQuantity = 0;
			let selector =
				'.wc-block-components-quantity-selector__input[name="quantity"]';
			if ( productType === 'grouped' && childProductId ) {
				currentQuantity = quantity?.[ childProductId ] || 0;
				selector = `.wc-block-components-quantity-selector__input[name="quantity[${ childProductId }]"]`;
			} else {
				currentQuantity =
					quantity?.[ context.productId as number ] || 0;
			}
			const inputElement = document.querySelector(
				selector
			) as HTMLInputElement | null;

			if ( ! inputElement ) {
				return false;
			}

			const parsedMinValue = parseInt( inputElement.min, 10 );
			const parsedStep = parseInt( inputElement.step, 10 );
			const defaultMinValue = childProductId ? 0 : 1;
			const minValue = isNaN( parsedMinValue )
				? defaultMinValue
				: parsedMinValue;

			const step = isNaN( parsedStep ) ? 1 : parsedStep;
			return currentQuantity - step >= minValue;
		},
		get allowsIncrease() {
			const context = getContext< Context >();
			const { quantity, childProductId, productType } = context;
			let currentQuantity = 0;
			let selector =
				'.wc-block-components-quantity-selector__input[name="quantity"]';
			if ( productType === 'grouped' && childProductId ) {
				currentQuantity = quantity?.[ childProductId ] || 0;
				selector = `.wc-block-components-quantity-selector__input[name="quantity[${ childProductId }]"]`;
			} else {
				currentQuantity =
					quantity?.[ context.productId as number ] || 0;
			}
			const inputElement = document.querySelector(
				selector
			) as HTMLInputElement | null;

			if ( ! inputElement ) {
				return false;
			}

			const parsedMaxValue = parseInt( inputElement.max, 10 );
			const parsedStep = parseInt( inputElement.step, 10 );

			const maxValue = isNaN( parsedMaxValue )
				? undefined
				: parsedMaxValue;
			const step = isNaN( parsedStep ) ? 1 : parsedStep;
			return maxValue === undefined || currentQuantity + step <= maxValue;
		},
	},
	actions: {
		addQuantity: ( event: HTMLElementEvent< HTMLButtonElement > ) => {
			const inputData = getInputData( event );
			if ( ! inputData ) {
				return;
			}
			const context = getContext< Context >();
			const {
				currentValue,
				maxValue,
				step,
				inputElement,
				childProductId,
			} = inputData;
			const newValue = currentValue + step;

			if ( maxValue === undefined || newValue <= maxValue ) {
				if ( childProductId ) {
					context.quantity = {
						...context.quantity,
						[ childProductId ]: newValue,
					};
				} else {
					context.quantity = {
						...context.quantity,
						[ context.productId as number ]: newValue,
					};
				}
				inputElement.value = newValue.toString();
				dispatchChangeEvent( inputElement );
			}
		},
		removeQuantity: ( event: HTMLElementEvent< HTMLButtonElement > ) => {
			const inputData = getInputData( event );
			if ( ! inputData ) {
				return;
			}
			const context = getContext< Context >();
			const {
				currentValue,
				minValue,
				step,
				inputElement,
				childProductId,
			} = inputData;
			const newValue = currentValue - step;

			if ( newValue >= minValue ) {
				if ( childProductId ) {
					context.quantity = {
						...context.quantity,
						[ childProductId ]: newValue,
					};
				} else {
					context.quantity = {
						...context.quantity,
						[ context.productId as number ]: newValue,
					};
				}
				inputElement.value = newValue.toString();
				dispatchChangeEvent( inputElement );
			}
		},
		handleInputChange: ( event: HTMLElementEvent< HTMLInputElement > ) => {
			const inputElement = event.target as HTMLInputElement;
			const value = parseInt( inputElement.value, 10 );
			const childProductIdMatch =
				inputElement.name.match( /quantity\[(\d+)\]/ );
			const childProductId = childProductIdMatch
				? parseInt( childProductIdMatch[ 1 ], 10 )
				: undefined;
			const context = getContext< Context >();
			if ( childProductId ) {
				context.quantity = {
					...context.quantity,
					[ childProductId ]: isNaN( value ) ? 0 : value,
				};
			} else {
				context.quantity = {
					...context.quantity,
					[ context.productId as number ]: isNaN( value ) ? 0 : value,
				};
			}
		},
	},
} );
