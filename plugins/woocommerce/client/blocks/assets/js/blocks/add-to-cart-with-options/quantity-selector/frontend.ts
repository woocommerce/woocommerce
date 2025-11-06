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
		inputQuantity: number;
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

const { state } = store< QuantitySelectorStore >(
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
				const { productData, quantity } =
					addToCartWithOptionsStore.state;

				if ( ! productData ) {
					return true;
				}

				if ( productData.type === 'grouped' ) {
					const { productId } = getContext< Context >();

					return quantity[ productId ] > 0;
				}

				const { id, min, step } = productData;

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
			get inputQuantity(): number {
				const { productId } = getContext< Context >();

				return (
					addToCartWithOptionsStore.state.draftQuantity ??
					( addToCartWithOptionsStore.state.quantity?.[ productId ] ||
						0 )
				);
			},
		},
		actions: {
			// Hold a draft value in state to allow a non-committed intermediate state of the input.
			storeDraftValue: (
				event: HTMLElementEvent< HTMLInputElement >
			) => {
				if (
					isNaN( Number( event.target.value ) ) ||
					event.target.value === ''
				) {
					addToCartWithOptionsStore.state.draftQuantity = '';
				} else {
					addToCartWithOptionsStore.state.draftQuantity =
						Number( event.target.value ) || 0;
				}
			},
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
					newValue,
					inputElement
				);
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
				const { productData, selectedAttributes } =
					addToCartWithOptionsStore.state;

				const parentProductObject = productData;

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
				const { productData, selectedAttributes } =
					addToCartWithOptionsStore.state;
				let min = 1;

				if ( ! productData ) {
					return;
				}

				const { productId } = getContext< Context >();

				// In grouped products, we reset invalid inputs to 0.
				if (
					( Number.isNaN( event.target.valueAsNumber ) ||
						event.target.valueAsNumber === 0 ) &&
					productData.type === 'grouped'
				) {
					addToCartWithOptionsStore.actions.setQuantity(
						productId,
						0,
						event.target
					);
					return;
				}

				const childProductData =
					productData.type === 'grouped'
						? getProductData( productId, selectedAttributes )
						: productData;

				if ( ! childProductData ) {
					return;
				}

				min = childProductData.min;

				// In other product types, we reset inputs to `min` if they are
				// 0 or NaN.
				const newValue =
					Number.isFinite( event.target.valueAsNumber ) &&
					event.target.valueAsNumber > 0
						? event.target.valueAsNumber
						: min;

				addToCartWithOptionsStore.actions.setQuantity(
					productId,
					newValue,
					event.target
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
