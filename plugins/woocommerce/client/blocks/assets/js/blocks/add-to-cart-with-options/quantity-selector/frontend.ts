/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import type {
	Store as WooCommerce,
	DraftItem,
} from '@woocommerce/stores/woocommerce/cart';
import '@woocommerce/stores/woocommerce/products';
import '@woocommerce/stores/woocommerce/cart';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

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

const { state: cartState, actions: cartActions } = store< WooCommerce >(
	'woocommerce/cart',
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
		watchQuantityConstraints: () => void;
	};
};

// Declared before the store definition so the getters below can read
// `state.inputQuantity` during hydration.
const { state } = store< QuantitySelectorStore >(
	'woocommerce/add-to-cart-with-options-quantity-selector',
	{},
	{ lock: universalLock }
);

/**
 * Manually dispatches a 'change' event on the quantity input element.
 *
 * When users click the plus/minus stepper buttons, no 'change' event is fired
 * since there is no direct interaction with the input. Some extensions rely on
 * the change event to detect quantity changes, so we dispatch it programmatically.
 *
 * @see https://github.com/woocommerce/woocommerce/issues/53031
 *
 * @param inputElement The quantity input element to dispatch the event on.
 */
const dispatchChangeEvent = ( inputElement: HTMLInputElement ) => {
	inputElement.dispatchEvent( new Event( 'change', { bubbles: true } ) );
};

/**
 * The current draft quantity for the context product, or `undefined` when no
 * draft exists yet (nothing has been touched). Read straight from the shared
 * store envelope, so the same value backs the form and formless surfaces.
 */
const getDraftQuantity = (): number | undefined =>
	cartState.itemInContext.draft?.quantity;

/**
 * Commit a quantity to the context draft, then run the shared side effects: fire
 * the legacy `change` event so extensions listening on the input keep working,
 * and — when the target equals what the input already displays — write the value
 * back onto the element directly (a reactive signal set to an unchanged value
 * fires no update, yet the input may need a visible reset, e.g. the shopper typed
 * letters and the numeric value clamped back to the same number).
 *
 * @param quantity The absolute target quantity.
 */
const commitQuantity = ( quantity: number ): void => {
	const { inputElement } = getContext< Context >();
	const unchanged = getDraftQuantity() === quantity;

	cartActions.upsertDraftItem( { quantity } );

	if ( inputElement ) {
		if ( unchanged ) {
			inputElement.value = String( quantity );
		}
		dispatchChangeEvent( inputElement );
	}
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

				const { add_to_cart: addToCart } = product;
				const currentQuantity = state.inputQuantity;
				const { allowZero } = getContext< Context >();
				return (
					( !! allowZero && currentQuantity > 0 ) ||
					currentQuantity - addToCart.multiple_of >= addToCart.minimum
				);
			},
			get allowsIncrease() {
				const product = productsState.productInContext;

				if ( ! product ) {
					return true;
				}

				const { add_to_cart: addToCart } = product;
				const currentQuantity = state.inputQuantity;

				return (
					currentQuantity + addToCart.multiple_of <= addToCart.maximum
				);
			},
			// The displayed quantity: the context draft's quantity, or a sensible
			// default when nothing has been touched yet. `allowZero` surfaces
			// (grouped children) default to 0; everything else defaults to the
			// product's minimum purchase quantity — fixing the first-paint 0 the
			// removed SSR seed used to show.
			get inputQuantity(): number {
				const draftQuantity = getDraftQuantity();
				if ( typeof draftQuantity === 'number' ) {
					return draftQuantity;
				}
				if ( getContext< Context >().allowZero ) {
					return 0;
				}
				return (
					productsState.productInContext?.add_to_cart?.minimum ?? 1
				);
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
				const {
					minimum,
					maximum,
					multiple_of: multipleOf,
				} = product.add_to_cart;

				const newValue = Math.max(
					minimum,
					Math.min( maximum, currentValue + multipleOf )
				);

				commitQuantity( newValue );
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
				const {
					minimum,
					maximum,
					multiple_of: multipleOf,
				} = product.add_to_cart;

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
					commitQuantity( newValue );
				}
			},
			// We listen to blur instead of change because the change event isn't
			// triggered for invalid numbers (e.g. letters) when the current value
			// is already invalid or empty.
			handleQuantityBlur: () => {
				const { allowZero, inputElement } = getContext< Context >();

				const product = productsState.productInContext;

				if ( ! product ) {
					return;
				}

				const { add_to_cart: addToCart } = product;
				const isValueNaN = Number.isNaN( inputElement?.valueAsNumber );

				if (
					allowZero &&
					( isValueNaN || inputElement?.valueAsNumber === 0 )
				) {
					commitQuantity( 0 );
					return;
				}

				// In other product types, we reset inputs to `minimum` if they
				// are 0 or NaN.
				const value = inputElement?.valueAsNumber ?? NaN;
				const newValue =
					! isNaN( value ) && value > 0 ? value : addToCart.minimum;

				commitQuantity( newValue );
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

				commitQuantity( element.ref.checked ? 1 : 0 );
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
			// Quantity constraints can change when switching variations. This
			// watch re-clamps the draft quantity into the CURRENT product's
			// min/max. It reads `productInContext` (variation if one is
			// selected, else the main product) and runs for every product type:
			// for a non-variable product the constraints never change, so the
			// clamp is a no-op — behaviorally identical, but with no
			// type/variation branch. Bound on the quantity input, it moved here
			// from the variation selector: it is quantity logic and belongs with
			// the quantity input it observes.
			watchQuantityConstraints: () => {
				const { ref } = getElement();

				if ( ! ( ref instanceof HTMLInputElement ) ) {
					return;
				}

				// Do nothing while the user is typing in the input.
				if ( ref === ref.ownerDocument.activeElement ) {
					return;
				}

				const { productInContext: product } = productsState;

				if ( ! product ) {
					return;
				}

				const { minimum, maximum } = product.add_to_cart;

				// Read the draft directly (not the min-defaulted getter): only an
				// out-of-range EXISTING quantity needs re-clamping. An untouched
				// draft has no quantity to correct.
				const draft: DraftItem | undefined =
					cartState.itemInContext.draft;
				const currentValue = draft?.quantity;
				if ( typeof currentValue !== 'number' ) {
					return;
				}

				let newValue = currentValue;
				if ( currentValue < minimum ) {
					newValue = minimum;
				} else if ( currentValue > maximum ) {
					newValue = maximum;
				}

				if (
					newValue !== ref.valueAsNumber ||
					newValue !== currentValue
				) {
					commitQuantity( newValue );
				}
			},
		},
	},
	{ lock: universalLock }
);
