/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';
/**
 * Internal dependencies
 */
import type { AddToCartWithOptionsStore } from '../frontend';
import { getContextProductId, getDraftQuantity } from '../cart-drafts';

/**
 * The id the current draft is keyed by: the shared `woocommerce` context
 * product id (identity rule 3 — the MAIN/parent product id; on grouped child
 * rows, the row's own `woocommerce::{ childId }` context). Falls back to the
 * resolved product's id when called out of a shared-context scope.
 *
 * This is deliberately NOT `productInContext.id`: for variable products that
 * resolves to the selected VARIATION's id, while the draft (and its quantity)
 * lives under the parent id. The product's `add_to_cart` constraints, on the
 * other hand, DO come from `productInContext` (variation-specific min/max).
 *
 * @param productId The resolved product's id (fallback).
 * @return The draft key.
 */
const getDraftKey = ( productId: number ): number =>
	getContextProductId() ?? productId;

export type Context = {
	allowZero?: boolean;
	inputElement?: HTMLInputElement | null;
};

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

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
				const product = productsState.productInContext;

				if ( ! product ) {
					return true;
				}

				return product.is_in_stock && ! product.sold_individually;
			},
			get allowsDecrease() {
				const product = productsState.productInContext;

				if ( ! product ) {
					return true;
				}

				const { id, add_to_cart: addToCart } = product;

				// Quantity lives on the shared-store draft, keyed by the shared
				// context product id (see getDraftKey).
				const currentQuantity = getDraftQuantity( getDraftKey( id ) );

				const { allowZero } = getContext< Context >();
				return (
					( allowZero && currentQuantity > 0 ) ||
					currentQuantity - addToCart.multiple_of >= addToCart.minimum
				);
			},
			get allowsIncrease() {
				const product = productsState.productInContext;

				if ( ! product ) {
					return true;
				}

				const { id, add_to_cart: addToCart } = product;

				const currentQuantity = getDraftQuantity( getDraftKey( id ) );

				return (
					currentQuantity + addToCart.multiple_of <= addToCart.maximum
				);
			},
			get inputQuantity(): number {
				const product = productsState.productInContext;

				if ( ! product ) {
					return 0;
				}

				return getDraftQuantity( getDraftKey( product.id ) );
			},
		},
		actions: {
			increaseQuantity: () => {
				const { inputElement } = getContext< Context >();

				if ( ! ( inputElement instanceof HTMLInputElement ) ) {
					return;
				}

				const product = productsState.productInContext;

				if ( ! product ) {
					return;
				}

				const currentValue = Number( inputElement.value ) || 0;
				const { id: productId, add_to_cart: addToCart } = product;
				const { minimum, maximum, multiple_of: multipleOf } = addToCart;

				const newValue = Math.max(
					minimum,
					Math.min( maximum, currentValue + multipleOf )
				);

				addToCartWithOptionsStore.actions.setQuantity(
					getDraftKey( productId ),
					newValue
				);
			},
			decreaseQuantity: () => {
				const { allowZero, inputElement } = getContext< Context >();

				if ( ! ( inputElement instanceof HTMLInputElement ) ) {
					return;
				}

				const product = productsState.productInContext;

				if ( ! product ) {
					return;
				}

				const currentValue = Number( inputElement.value ) || 0;
				const { id: productId, add_to_cart: addToCart } = product;
				const { minimum, maximum, multiple_of: multipleOf } = addToCart;

				let newValue = currentValue - multipleOf;
				if (
					allowZero &&
					newValue < minimum &&
					currentValue === minimum
				) {
					newValue = 0;
				} else {
					newValue = Math.min(
						maximum,
						Math.max( minimum, newValue )
					);
				}

				if ( newValue !== currentValue ) {
					addToCartWithOptionsStore.actions.setQuantity(
						getDraftKey( productId ),
						newValue
					);
				}
			},
			// We need to listen to blur events instead of change events because
			// the change event isn't triggered in invalid numbers (ie: writing
			// letters) if the current value is already invalid or an empty string.
			handleQuantityBlur: () => {
				const { allowZero, inputElement } = getContext< Context >();

				const product = productsState.productInContext;

				if ( ! product ) {
					return;
				}

				const { id: productId, add_to_cart: addToCart } = product;
				const isValueNaN = Number.isNaN( inputElement?.valueAsNumber );

				if (
					allowZero &&
					( isValueNaN || inputElement?.valueAsNumber === 0 )
				) {
					addToCartWithOptionsStore.actions.setQuantity(
						getDraftKey( productId ),
						0
					);
					return;
				}

				// In other product types, we reset inputs to `minimum` if they
				// are 0 or NaN.
				const value = inputElement?.valueAsNumber ?? NaN;
				const newValue =
					! isNaN( value ) && value > 0 ? value : addToCart.minimum;

				addToCartWithOptionsStore.actions.setQuantity(
					getDraftKey( productId ),
					newValue
				);
			},
			handleQuantityCheckboxChange: () => {
				const element = getElement();

				if ( ! ( element.ref instanceof HTMLInputElement ) ) {
					return;
				}

				const product = productsState.productInContext;

				if ( ! product ) {
					return;
				}

				addToCartWithOptionsStore.actions.setQuantity(
					getDraftKey( product.id ),
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
