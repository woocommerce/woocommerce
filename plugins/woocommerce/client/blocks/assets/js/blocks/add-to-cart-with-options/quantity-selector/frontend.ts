/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/cart';
import type { Store as WooCommerce } from '@woocommerce/stores/woocommerce/cart';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';
/**
 * Internal dependencies
 */
import type { AddToCartWithOptionsStore } from '../frontend';

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

// Todo: Use the module exports instead of `store()` once the woocommerce
// store is public.
const { state: cartState } = store< WooCommerce >(
	'woocommerce/cart',
	{},
	{ lock: universalLock }
);

const addToCartWithOptionsStore = store< AddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{},
	{ lock: universalLock }
);

/**
 * Resolves the quantity to display for a product id.
 *
 * Prefers the resolved collection's cart draft for the id — the collection
 * keyed by the nearest declared `context.draftKey`, falling back to the
 * store's reserved global key — which every surface resolving that same
 * collection writes to and reads from, so an edit made on one surface is
 * reflected on every other — falling back to this block instance's own
 * locally-tracked quantity when the resolved collection holds no draft for
 * the id yet.
 *
 * A transient `NaN` written to the local quantity (see `setQuantity` in the
 * parent `woocommerce/add-to-cart-with-options` store) forces the bound
 * input to refresh when an invalid value is corrected back to its previous
 * number; that local `NaN` is returned as-is, ahead of the draft, so the
 * forced refresh still reaches the input.
 *
 * @param productId The product id to resolve a displayed quantity for.
 * @return The quantity to display, or `0` when neither source has one.
 */
function resolveDisplayQuantity( productId: number ): number {
	const localQuantity = addToCartWithOptionsStore.state.quantity?.[
		productId
	] as number | undefined;

	if ( typeof localQuantity === 'number' && Number.isNaN( localQuantity ) ) {
		return localQuantity;
	}

	const draftQuantity = cartState.itemInContext.draft?.quantity;

	if ( typeof draftQuantity === 'number' ) {
		return draftQuantity;
	}

	return localQuantity ?? 0;
}

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

				const currentQuantity = resolveDisplayQuantity( id ) || 0;

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

				const currentQuantity = resolveDisplayQuantity( id ) || 0;

				return (
					currentQuantity + addToCart.multiple_of <= addToCart.maximum
				);
			},
			get inputQuantity(): number {
				const product = productsState.productInContext;

				if ( ! product ) {
					return 0;
				}

				return resolveDisplayQuantity( product.id );
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
					productId,
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
						productId,
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
						productId,
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
					productId,
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
					product.id,
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
