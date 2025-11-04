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
				const { activeProduct, inputQuantity, minQuantity } =
					addToCartWithOptionsStore.state;

				if ( ! activeProduct ) {
					return true;
				}

				const { step } = activeProduct;
				const currentQuantity = inputQuantity || 0;

				return currentQuantity - step >= minQuantity;
			},
			get allowsIncrease() {
				const { activeProduct, inputQuantity } =
					addToCartWithOptionsStore.state;

				if ( ! activeProduct ) {
					return true;
				}

				const { max, step } = activeProduct;
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
				const { activeProduct } = addToCartWithOptionsStore.state;

				if ( ! activeProduct ) {
					return;
				}

				const { id, max, min, step } = activeProduct;
				const newValue = Math.max(
					min,
					Math.min( max, currentValue + step )
				);

				addToCartWithOptionsStore.actions.setQuantity(
					id,
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
				const { activeProduct, isGroupedProduct, minQuantity } =
					addToCartWithOptionsStore.state;

				if ( ! activeProduct ) {
					return;
				}

				const { id, min, step } = activeProduct;
				let newValue = currentValue - step;

				// Enforce minimum
				if ( newValue < minQuantity ) {
					// Grouped products: allow stepping down to min, then to 0
					if ( isGroupedProduct ) {
						newValue = currentValue > min ? min : 0;
					} else {
						newValue = minQuantity;
					}
				}

				if ( newValue !== currentValue ) {
					addToCartWithOptionsStore.actions.setQuantity(
						id,
						newValue,
						inputElement
					);
				}
			},
			handleQuantityBlur: (
				event: HTMLElementEvent< HTMLInputElement >
			) => {
				const { activeProduct, isGroupedProduct, minQuantity, inputQuantity } =
					addToCartWithOptionsStore.state;

				if ( ! activeProduct ) {
					return;
				}

				const isInvalidOrZero =
					Number.isNaN( event.target.valueAsNumber ) ||
					event.target.valueAsNumber === 0;

				// Grouped products: reset to 0 (empty) for invalid/zero inputs
				if ( isInvalidOrZero && isGroupedProduct ) {
					addToCartWithOptionsStore.actions.setQuantity(
						activeProduct.id,
						0,
						event.target
					);

					// Keep display as empty string for NaN
					if ( Number.isNaN( event.target.valueAsNumber ) ) {
						event.target.value = '';
					}
					return;
				}

				// Other products: reset to minimum for invalid/zero inputs
				const newValue = isInvalidOrZero
					? minQuantity
					: event.target.valueAsNumber;

				// Only dispatch change if value actually changed
				const refForDispatch = newValue !== inputQuantity
					? event.target
					: null;

				addToCartWithOptionsStore.actions.setQuantity(
					activeProduct.id,
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
					element.ref.checked ? 1 : 0,
					null
				);
			},
		},
	},
	{ lock: universalLock }
);
