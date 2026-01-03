/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/product-data';

/**
 * Internal dependencies
 */
import { getProductData } from '../frontend';
import type { AddToCartWithOptionsStore } from '../frontend';

export type Context = {
	productId: number;
	allowZero?: boolean;
	inputElement?: HTMLInputElement | null;
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
		increaseQuantity: () => void;
		decreaseQuantity: () => void;
		handleQuantityBlur: () => void;
		handleQuantityCheckboxChange: () => void;
	};
	callbacks: {
		storeInputElementRef: () => void;
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
			get inputQuantity(): number {
				const { productId } = getContext< Context >();

				const quantity =
					addToCartWithOptionsStore.state.quantity?.[ productId ];

				return quantity === undefined ? 0 : quantity;
			},
		},
		actions: {
			increaseQuantity: () => {
				const { productId, inputElement } = getContext< Context >();

				if ( ! ( inputElement instanceof HTMLInputElement ) ) {
					return;
				}

				const currentValue = Number( inputElement.value ) || 0;

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
			},
			decreaseQuantity: () => {
				const { allowZero, productId, inputElement } =
					getContext< Context >();

				if ( ! ( inputElement instanceof HTMLInputElement ) ) {
					return;
				}

				const currentValue = Number( inputElement.value ) || 0;
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
				}
			},
			// We need to listen to blur events instead of change events because
			// the change event isn't triggered in invalid numbers (ie: writing
			// letters) if the current value is already invalid or an empty string.
			handleQuantityBlur: () => {
				const { allowZero, productId, inputElement } =
					getContext< Context >();
				const { selectedAttributes } = addToCartWithOptionsStore.state;

				const productObject = getProductData(
					productId,
					selectedAttributes
				);

				if ( ! productObject ) {
					return;
				}

				const isValueNaN = Number.isNaN( inputElement?.valueAsNumber );
				const { min } = productObject;

				if (
					allowZero &&
					( isValueNaN || inputElement?.valueAsNumber === 0 )
				) {
					addToCartWithOptionsStore.actions.setQuantity(
						productId,
						0
					);
					return;
				}

				// In other product types, we reset inputs to `min` if they are
				// 0 or NaN.
				const value = inputElement?.valueAsNumber ?? NaN;
				const newValue = ! isNaN( value ) && value > 0 ? value : min;

				addToCartWithOptionsStore.actions.setQuantity(
					productId,
					newValue
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
		callbacks: {
			storeInputElementRef: () => {
				const { ref } = getElement();
				if ( ref ) {
					const context = getContext< Context >();
					const inputElement =
						ref.querySelector< HTMLInputElement >( '.qty' );
					context.inputElement = inputElement;
				}
			},
		},
	},
	{ lock: universalLock }
);
