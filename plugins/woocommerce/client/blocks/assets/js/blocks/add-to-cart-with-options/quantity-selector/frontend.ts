/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/product-data';
import type { HTMLElementEvent } from '@woocommerce/types';
import type { ProductDataStore } from '@woocommerce/stores/woocommerce/product-data';

/**
 * Internal dependencies
 */
import { getProductData } from '../frontend';
import type { AddToCartWithOptionsStore } from '../frontend';

export type Context = {
	productId: number;
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const addToCartWithOptionsStore = store< AddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{},
	{ lock: universalLock }
);

const { state: productDataState } = store< ProductDataStore >(
	'woocommerce/product-data',
	{},
	{ lock: universalLock }
);

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
export const dispatchChangeEvent = ( inputElement: HTMLInputElement ) => {
	const event = new Event( 'change', { bubbles: true } );
	inputElement.dispatchEvent( event );
};

export type QuantitySelectorStore = {
	state: {
		allowsDecrease: boolean;
		allowsIncrease: boolean;
	};
	actions: {
		increaseQuantity: (
			event: HTMLElementEvent< HTMLButtonElement >
		) => void;
		decreaseQuantity: (
			event: HTMLElementEvent< HTMLButtonElement >
		) => void;
		handleQuantityBlur: (
			event: HTMLElementEvent< HTMLInputElement >
		) => void;
		handleQuantityCheckboxChange: (
			event: HTMLElementEvent< HTMLInputElement >
		) => void;
	};
};

store< QuantitySelectorStore >(
	'woocommerce/add-to-cart-with-options-quantity-selector',
	{
		state: {
			get allowsDecrease() {
				const { quantity, selectedAttributes } =
					addToCartWithOptionsStore.state;

				// Note: in grouped products, this will be the parent product.
				// We handle grouped products decrease differently because we
				// allow setting the quantity to 0.
				const productObject = getProductData(
					productDataState.productId,
					selectedAttributes
				);

				if ( ! productObject ) {
					return true;
				}

				if ( productObject.type === 'grouped' ) {
					const { productId } = getContext< Context >();

					return quantity[ productId ] > 0;
				}

				const { id, min, step } = productObject;

				const currentQuantity = quantity[ id ] || 0;

				return currentQuantity - step >= min;
			},
			get allowsIncrease() {
				const { quantity, selectedAttributes } =
					addToCartWithOptionsStore.state;

				const { productId } = getContext< Context >();

				const productObject = getProductData(
					productId,
					selectedAttributes
				);

				if ( ! productObject ) {
					return true;
				}

				const { id, max, step } = productObject;

				const currentQuantity = quantity[ id ] || 0;

				return currentQuantity + step <= max;
			},
		},
		actions: {
			increaseQuantity: (
				event: HTMLElementEvent< HTMLButtonElement >
			) => {
				const inputElement =
					event.target.parentElement?.querySelector( '.qty' );

				if ( ! ( inputElement instanceof HTMLInputElement ) ) {
					return;
				}

				const currentValue = Number( inputElement.value ) || 0;

				const { productId } = getContext< Context >();
				const { selectedAttributes } = addToCartWithOptionsStore.state;

				const productObject = getProductData(
					productId,
					selectedAttributes
				);

				let newValue = currentValue + 1;
				if ( productObject ) {
					const { max, min, step } = productObject;
					newValue = currentValue + step;
					newValue = Math.max( min, Math.min( max, newValue ) );
				}

				addToCartWithOptionsStore.actions.setQuantity(
					productId,
					newValue
				);
				inputElement.value = newValue.toString();
				dispatchChangeEvent( inputElement );
			},
			decreaseQuantity: (
				event: HTMLElementEvent< HTMLButtonElement >
			) => {
				const inputElement =
					event.target.parentElement?.querySelector( '.qty' );

				if ( ! ( inputElement instanceof HTMLInputElement ) ) {
					return;
				}

				const currentValue = Number( inputElement.value ) || 0;

				const { productId } = getContext< Context >();
				const { selectedAttributes } = addToCartWithOptionsStore.state;

				const parentProductObject = getProductData(
					productDataState.productId,
					selectedAttributes
				);

				let productObject = parentProductObject;

				if ( parentProductObject?.type === 'grouped' ) {
					productObject = getProductData(
						productId,
						selectedAttributes
					);
				}

				let newValue = currentValue - 1;

				if ( productObject ) {
					const { min, step } = productObject;
					newValue = currentValue - step;

					if ( newValue < min ) {
						// In grouped product children, we allow decreasing the value
						// down to 0, even if the minimum value is greater than 0.
						if ( parentProductObject?.type === 'grouped' ) {
							if ( currentValue > min ) {
								newValue = min;
							} else {
								newValue = 0;
							}
						} else {
							newValue = min;
						}
					}
				}

				if ( newValue !== currentValue ) {
					addToCartWithOptionsStore.actions.setQuantity(
						productId,
						newValue
					);

					inputElement.value = newValue.toString();
					dispatchChangeEvent( inputElement );
				}
			},
			// We need to listen to blur events instead of change events because
			// the change event isn't triggered in invalid numbers (ie: writing
			// letters) if the current value is already invalid or an empty string.
			handleQuantityBlur: (
				event: HTMLElementEvent< HTMLInputElement >
			) => {
				const { selectedAttributes } = addToCartWithOptionsStore.state;
				let min = 1;
				const productObject = getProductData(
					productDataState.productId,
					selectedAttributes
				);

				if ( ! productObject ) {
					return;
				}

				const { productId } = getContext< Context >();

				// In grouped products, we reset invalid inputs to ''.
				if (
					( Number.isNaN( event.target.valueAsNumber ) ||
						event.target.valueAsNumber === 0 ) &&
					productObject.type === 'grouped'
				) {
					addToCartWithOptionsStore.actions.setQuantity(
						productId,
						0
					);
					if ( Number.isNaN( event.target.valueAsNumber ) ) {
						event.target.value = '';
					}
					dispatchChangeEvent( event.target );
					return;
				}

				let childProductObject = null;

				if ( productObject.type === 'grouped' ) {
					childProductObject = getProductData(
						productId,
						selectedAttributes
					);

					if ( ! childProductObject ) {
						return;
					}
				} else {
					childProductObject = productObject;
				}

				min = childProductObject.min;

				// In other product types, we reset inputs to `min` if they are
				// 0 or NaN.
				const newValue =
					Number.isFinite( event.target.valueAsNumber ) &&
					event.target.valueAsNumber > 0
						? event.target.valueAsNumber
						: min;

				addToCartWithOptionsStore.actions.setQuantity(
					productId,
					newValue
				);
				event.target.value = newValue.toString();
				dispatchChangeEvent( event.target );
			},
			handleQuantityCheckboxChange: () => {
				const element = getElement();

				if ( ! ( element.ref instanceof HTMLInputElement ) ) {
					return;
				}

				const { productId } = getContext< Context >();

				addToCartWithOptionsStore.actions.setQuantity(
					productId,
					element.ref.checked ? 1 : 0
				);
			},
		},
	},
	{ lock: universalLock }
);
