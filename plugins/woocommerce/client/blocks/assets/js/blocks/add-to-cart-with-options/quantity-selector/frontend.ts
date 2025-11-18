/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/product-data';
import type { HTMLElementEvent } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { getProductData } from '../frontend';
import type { AddToCartWithOptionsStore } from '../frontend';

export type Context = {
	productId: number;
	allowZero?: boolean;
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const addToCartWithOptionsStore = store< AddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
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
		allowsQuantityChange: boolean;
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
			get allowsQuantityChange(): boolean {
				const { productData } = addToCartWithOptionsStore.state;

				if ( ! productData ) {
					return true;
				}

				return (
					productData.is_in_stock && ! productData.sold_individually
				);
			},
			get allowsDecrease() {
				// Note: in grouped products, `productData` will be the parent product.
				// We handle grouped products decrease differently because we
				// allow setting the quantity to 0.
				const { quantity, selectedAttributes } =
					addToCartWithOptionsStore.state;

				const { allowZero, productId } = getContext< Context >();

				const productObject = getProductData(
					productId,
					selectedAttributes
				);

				if ( ! productObject ) {
					return true;
				}

				const { id, min, step } = productObject;

				const currentQuantity = quantity[ id ] || 0;

				return (
					( allowZero && currentQuantity > 0 ) ||
					currentQuantity - step >= min
				);
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
				const { allowZero, productId } = getContext< Context >();
				const { selectedAttributes } = addToCartWithOptionsStore.state;

				const productObject = getProductData(
					productId,
					selectedAttributes
				);

				let newValue = currentValue - 1;

				if ( productObject ) {
					const { max, min, step } = productObject;
					newValue = currentValue - step;
					if ( allowZero && newValue < min && currentValue === min ) {
						newValue = 0;
					} else {
						newValue = Math.min( max, Math.max( min, newValue ) );
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
				const { allowZero, productId } = getContext< Context >();
				const { selectedAttributes } = addToCartWithOptionsStore.state;

				const productObject = getProductData(
					productId,
					selectedAttributes
				);

				if ( ! productObject ) {
					return;
				}

				const { min } = productObject;

				// In grouped products, we reset invalid inputs to ''.
				if (
					allowZero &&
					( Number.isNaN( event.target.valueAsNumber ) ||
						event.target.valueAsNumber === 0 )
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

				let newValue;
				if (
					Number.isFinite( event.target.valueAsNumber ) &&
					event.target.valueAsNumber > 0
				) {
					newValue = event.target.valueAsNumber;
				} else {
					newValue = allowZero ? 0 : min;
				}

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
