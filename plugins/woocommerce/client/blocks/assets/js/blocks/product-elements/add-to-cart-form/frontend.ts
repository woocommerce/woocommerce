/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';
import { HTMLElementEvent } from '@woocommerce/types';

export type ProductData = {
	id: number;
	type: string;
	quantity: number;
	quantityConstraints: {
		min: number;
		max: number | null;
		step: number;
	};
	parentProductId: number | null;
};

export type Context = {
	products: Record< number, ProductData >;
	currentProductId: number;
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

const getProductIdFromInput = ( inputElement: HTMLInputElement ): number => {
	// For grouped products, extract child ID from input name.
	const childProductIdMatch = inputElement.name.match( /quantity\[(\d+)\]/ );
	if ( childProductIdMatch ) {
		return parseInt( childProductIdMatch[ 1 ], 10 );
	}

	const context = getContext< Context >();

	// For simple products, use current product ID.
	return context.currentProductId;
};

const getQuantityStateInfo = () => {
	const context = getContext< Context >();
	const { products, currentProductId } = context;

	const product = products[ currentProductId ];
	if ( ! product ) {
		return {
			constraints: { min: 1, max: null, step: 1 },
			currentQuantity: 0,
		};
	}

	return {
		constraints: product.quantityConstraints,
		currentQuantity: product.quantity,
	};
};

const getInputData = ( event: HTMLElementEvent< HTMLButtonElement > ) => {
	const inputElement = getInputElementFromEvent( event );

	if ( ! inputElement ) {
		return;
	}

	const context = getContext< Context >();
	const productId = getProductIdFromInput( inputElement );
	const product = context.products[ productId ];

	if ( ! product ) {
		return;
	}

	const parsedValue = parseInt( inputElement.value, 10 );
	const currentValue = isNaN( parsedValue ) ? 0 : parsedValue;

	return {
		currentValue,
		minValue: product.quantityConstraints.min,
		maxValue: product.quantityConstraints.max,
		step: product.quantityConstraints.step,
		inputElement,
		productId,
	};
};

const dispatchChangeEvent = ( inputElement: HTMLInputElement ) => {
	const event = new Event( 'change' );

	inputElement.dispatchEvent( event );
};

store( 'woocommerce/add-to-cart-form', {
	state: {
		get allowsDecrease() {
			const { constraints, currentQuantity } = getQuantityStateInfo();
			const { min: minValue, step } = constraints;
			return currentQuantity - step >= minValue;
		},
		get allowsIncrease() {
			const { constraints, currentQuantity } = getQuantityStateInfo();
			const { max: maxValue, step } = constraints;
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
			const { currentValue, maxValue, step, inputElement, productId } =
				inputData;
			const newValue = currentValue + step;
			if ( maxValue === null || newValue <= maxValue ) {
				context.products[ productId ].quantity = newValue;
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
			const { currentValue, minValue, step, inputElement, productId } =
				inputData;
			const newValue = currentValue - step;
			if ( newValue >= minValue ) {
				context.products[ productId ].quantity = newValue;
				inputElement.value = newValue.toString();
				dispatchChangeEvent( inputElement );
			}
		},
		handleInputChange: ( event: HTMLElementEvent< HTMLInputElement > ) => {
			const inputElement = event.target as HTMLInputElement;
			const value = parseInt( inputElement.value, 10 );
			const context = getContext< Context >();
			const productId = getProductIdFromInput( inputElement );
			if ( context.products[ productId ] ) {
				context.products[ productId ].quantity = isNaN( value )
					? 0
					: value;
			}
		},
	},
} );
