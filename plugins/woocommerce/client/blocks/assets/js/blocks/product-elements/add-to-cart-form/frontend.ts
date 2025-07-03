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
	quantityConstraints: Record<
		number,
		{ min: number; max: number | null; step: number }
	>;
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
	const context = getContext< Context >();
	const productType = context.productType;
	const childProductIdMatch = inputElement.name.match( /quantity\[(\d+)\]/ );
	const childProductId = childProductIdMatch
		? parseInt( childProductIdMatch[ 1 ], 10 )
		: context.childProductId;
	const id =
		productType === 'grouped' && childProductId
			? childProductId
			: context.productId;
	const constraints = context.quantityConstraints?.[ id as number ] || {
		min: productType === 'grouped' && childProductId ? 0 : 1,
		step: 1,
	};
	const minValue = constraints.min;
	const maxValue = constraints.max;
	const step = constraints.step;
	const currentValue = isNaN( parsedValue ) ? 0 : parsedValue;
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
			const {
				quantity,
				childProductId,
				productType,
				quantityConstraints,
				productId,
			} = context;
			const id =
				productType === 'grouped' && childProductId
					? childProductId
					: productId;
			const constraints = quantityConstraints?.[ id as number ] || {
				min: productType === 'grouped' && childProductId ? 0 : 1,
				step: 1,
			};
			const minValue = constraints.min;
			const step = constraints.step;
			const currentQuantity =
				productType === 'grouped' && childProductId
					? quantity?.[ childProductId ] || 0
					: quantity?.[ productId as number ] || 0;
			return currentQuantity - step >= minValue;
		},
		get allowsIncrease() {
			const context = getContext< Context >();
			const {
				quantity,
				childProductId,
				productType,
				quantityConstraints,
				productId,
			} = context;
			const id =
				productType === 'grouped' && childProductId
					? childProductId
					: productId;
			const constraints = quantityConstraints?.[ id as number ] || {
				min: productType === 'grouped' && childProductId ? 0 : 1,
				step: 1,
			};
			const maxValue = constraints.max;
			const step = constraints.step;
			const currentQuantity =
				productType === 'grouped' && childProductId
					? quantity?.[ childProductId ] || 0
					: quantity?.[ productId as number ] || 0;
			return maxValue === null || currentQuantity + step <= maxValue;
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
			if ( maxValue === null || newValue <= maxValue ) {
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
