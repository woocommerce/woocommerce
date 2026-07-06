/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';
/**
 * Internal dependencies
 */
import type {
	AddToCartWithOptionsStore,
	Context as AddToCartWithOptionsContext,
} from '../frontend';
import {
	getContextProductId,
	getDraftQuantity,
	setDraftQuantity,
} from '../cart-drafts';

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

/**
 * Commit a new quantity for the context draft.
 *
 * The quantity stepper lives in TWO surfaces now:
 *
 * - Inside the Add to Cart + Options FORM (its original home), where the
 *   `woocommerce/add-to-cart-with-options` store's `setQuantity` owns the extra
 *   form bookkeeping (compat `context.quantity`, form validation, the manual
 *   change event that keeps extensions listening on the input working).
 * - Inside a Product Collection CARD (T9 demo — the boundary-breaking use case
 *   from PR #65570 / E14/E48), where there is NO form store context: the card is
 *   not wrapped by the form, so `getContext('woocommerce/add-to-cart-with-options')`
 *   resolves to `undefined` and the form `setQuantity` — which dereferences
 *   `context.quantity` and runs form-only validation — cannot run.
 *
 * Both surfaces share the SAME underlying truth: the shared `woocommerce/cart`
 * draft, keyed by the shared `woocommerce::{ productId }` context (identity rule
 * 3). So this helper always writes the draft directly via `setDraftQuantity`
 * (the single write path both the form and the card go through), and ONLY when a
 * form store context is present does it additionally delegate to the form's
 * `setQuantity` for the form-specific side effects. Detecting the form context
 * (rather than the shared context) keeps in-form behavior byte-identical while
 * letting the stepper function standalone in a collection card.
 *
 * @param productId The draft's product id (the shared-context/key product id).
 * @param value     The absolute target quantity.
 */
const commitQuantity = ( productId: number, value: number ): void => {
	// Is the stepper rendered inside the Add to Cart + Options form? The form
	// wrapper provides the `woocommerce/add-to-cart-with-options` context; a
	// collection card does not. `getContext` returns `undefined` (not throws)
	// for a namespace with no provider in the current scope.
	const formContext = getContext< AddToCartWithOptionsContext >(
		'woocommerce/add-to-cart-with-options'
	);

	if ( formContext ) {
		// In-form path: unchanged. The form's `setQuantity` writes the draft
		// (via the same `setDraftQuantity`) plus its own bookkeeping.
		addToCartWithOptionsStore.actions.setQuantity( productId, value );
		return;
	}

	// T9 demo path (collection card, no form): write the draft directly. The
	// bound `state.inputQuantity` re-renders from the draft; the add button
	// posts this draft via `woocommerce/cart::actions.addItem()`.
	setDraftQuantity( productId, value );
};

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

				commitQuantity( getDraftKey( productId ), newValue );
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
					commitQuantity( getDraftKey( productId ), newValue );
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
					commitQuantity( getDraftKey( productId ), 0 );
					return;
				}

				// In other product types, we reset inputs to `minimum` if they
				// are 0 or NaN.
				const value = inputElement?.valueAsNumber ?? NaN;
				const newValue =
					! isNaN( value ) && value > 0 ? value : addToCart.minimum;

				commitQuantity( getDraftKey( productId ), newValue );
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

				commitQuantity(
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
