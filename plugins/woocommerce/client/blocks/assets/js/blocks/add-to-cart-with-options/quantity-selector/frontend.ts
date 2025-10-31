/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/product-context';
import type { HTMLElementEvent } from '@woocommerce/types';

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
				const { productData, inputQuantity } =
					addToCartWithOptionsStore.state;

				if ( ! productData ) {
					return true;
				}

				if ( productData.type === 'grouped' ) {
					return inputQuantity > 0;
				}

				const { min, step } = productData;

				const currentQuantity = inputQuantity || 0;

				return currentQuantity - step >= min;
			},
			get allowsIncrease() {
				const { productData, inputQuantity } =
					addToCartWithOptionsStore.state;

				if ( ! productData ) {
					return true;
				}

				const { max, step } = productData;

				const currentQuantity = inputQuantity || 0;
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

				const { productData: parentProductData, childProductData } =
					addToCartWithOptionsStore.state;

				const productObject =
					parentProductData?.type === 'grouped'
						? childProductData
						: parentProductData;

				let newValue = currentValue + 1;

				if ( productObject ) {
					const { max, min, step } = productObject;
					newValue = currentValue + step;
					newValue = Math.max( min, Math.min( max, newValue ) );

					addToCartWithOptionsStore.actions.setQuantity(
						productObject.id,
						newValue,
						inputElement
					);
				}
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

				const { productData: parentProductData, childProductData } =
					addToCartWithOptionsStore.state;

				const productObject =
					parentProductData?.type === 'grouped'
						? childProductData
						: parentProductData;

				let newValue = currentValue - 1;

				if ( productObject ) {
					const { min, step } = productObject;
					newValue = currentValue - step;

					if ( newValue < min ) {
						// In grouped product children, we allow decreasing the value
						// down to 0, even if the minimum value is greater than 0.
						if ( parentProductData?.type === 'grouped' ) {
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
						productObject.id,
						newValue,
						inputElement
					);
				}
			},
			// We need to listen to blur events instead of change events because
			// the change event isn't triggered in invalid numbers (ie: writing
			// letters) if the current value is already invalid or an empty string.
			handleQuantityBlur: (
				event: HTMLElementEvent< HTMLInputElement >
			) => {
				const { productData: parentProductData, childProductData } =
					addToCartWithOptionsStore.state;
				let min = 1;

				if ( ! parentProductData ) {
					return;
				}

				// In grouped products, we reset invalid inputs to ''.
				if (
					( Number.isNaN( event.target.valueAsNumber ) ||
						event.target.valueAsNumber === 0 ) &&
					parentProductData.type === 'grouped'
				) {
					addToCartWithOptionsStore.actions.setQuantity(
						childProductData.id,
						0,
						event.target
					);

					// This is an edge case where displayed value ('') doesn't represent internal state (0).
					if ( Number.isNaN( event.target.valueAsNumber ) ) {
						event.target.value = '';
					}
					return;
				}

				const currentProduct = childProductData ?? parentProductData;

				if ( ! currentProduct ) {
					return;
				}

				min = currentProduct.min;

				// In other product types, we reset inputs to `min` if they are
				// 0 or NaN.
				const newValue =
					Number.isFinite( event.target.valueAsNumber ) &&
					event.target.valueAsNumber > 0
						? event.target.valueAsNumber
						: min;

				// We must reset the quantity to force a value update, but if it's the same as the current value,
				// we don't need to dispatch a change event.
				const refForDispatch =
					newValue !== addToCartWithOptionsStore.state.inputQuantity
						? event.target
						: null;

				addToCartWithOptionsStore.actions.setQuantity(
					currentProduct.id,
					newValue,
					refForDispatch
				);
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
