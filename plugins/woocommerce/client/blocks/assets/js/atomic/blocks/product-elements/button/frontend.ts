/**
 * External dependencies
 */
import { store, getContext, useLayoutEffect } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/products';
import '@woocommerce/stores/woocommerce/cart';
import type { Store as WooCommerce } from '@woocommerce/stores/woocommerce/cart';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import type { AddToCartWithOptionsStore } from '../../../../blocks/add-to-cart-with-options/frontend';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

interface Context {
	addToCartText: string;
	displayViewCart: boolean;
	tempQuantity: number;
	animationStatus: AnimationStatus;
	hasPressedButton: boolean;
	inTheCartText: string;
}

enum AnimationStatus {
	IDLE = 'IDLE',
	SLIDE_OUT = 'SLIDE-OUT',
	SLIDE_IN = 'SLIDE-IN',
}

type ServerState = {
	state: {
		inTheCartText: string;
		addToCartText: string;
		noticeId: string;
	};
};

const { state: cartState, actions: cartActions } = store< WooCommerce >(
	'woocommerce/cart',
	{},
	{ lock: universalLock }
);

const { state: addToCartWithOptionsState } = store< AddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{},
	{ lock: universalLock }
);

const { state: productsState } = store< ProductsStore >(
	'woocommerce/products',
	{},
	{ lock: universalLock }
);

const productButtonStore = {
	state: {
		// The "X in cart" quantity, resolved through the cart store's
		// type-invariant `inCartQuantity` read keyed by the main/context product
		// id (`mainProductInContext` — the parent, never a variation). The cart
		// store resolves the total for ANY purchasable form (simple line,
		// resolved variation line, or the sum over a grouped parent's children),
		// so the button never branches on product type here.
		get inCartQuantity(): number {
			const mainProduct = productsState.mainProductInContext;

			if ( ! mainProduct ) {
				return 0;
			}

			return cartState.inCartQuantity( mainProduct.id );
		},
		get slideInAnimation() {
			const { animationStatus } = getContext< Context >();
			return animationStatus === AnimationStatus.SLIDE_IN;
		},
		get slideOutAnimation() {
			const { animationStatus } = getContext< Context >();
			return animationStatus === AnimationStatus.SLIDE_OUT;
		},
		get addToCartText(): string {
			const {
				animationStatus,
				tempQuantity,
				addToCartText,
				inTheCartText,
				hasPressedButton,
			} = getContext< Context >();

			// We use the temporary quantity when there's no animation, or
			// when the second part of the animation hasn't started yet.
			const showTemporaryNumber =
				animationStatus === AnimationStatus.IDLE ||
				animationStatus === AnimationStatus.SLIDE_OUT;
			const quantity = showTemporaryNumber
				? tempQuantity || 0
				: state.inCartQuantity;

			// Grouped products keep their bespoke "Added to cart" label instead
			// of a summed count: a grouped parent has no line of its own and its
			// children are added as separate lines. The copy fork keys on the
			// schema field `grouped_products` being non-empty — the same signal
			// the `inCartQuantity` aggregate branches on — never on
			// `product.type`. The in-cart quantity read stays type-invariant; only
			// the string differs. Gated on `hasPressedButton` so the label only
			// appears after the shopper adds, matching the pre-existing UX.
			const groupedProducts =
				productsState.mainProductInContext?.grouped_products;
			const isGrouped =
				Array.isArray( groupedProducts ) && groupedProducts.length > 0;

			if ( isGrouped ) {
				if ( state.inCartQuantity > 0 && hasPressedButton ) {
					// `inTheCartText` is the bespoke grouped label (no `###`).
					return inTheCartText;
				}
				return addToCartText;
			}

			// Every other product type shows the "### in cart" count, with the
			// placeholder replaced by the type-invariant in-cart total.
			if ( quantity > 0 ) {
				return inTheCartText.replace( '###', quantity.toString() );
			}

			return addToCartText;
		},
		get displayViewCart(): boolean {
			const { displayViewCart } = getContext< Context >();
			if ( ! displayViewCart ) return false;
			return state.inCartQuantity > 0;
		},
	},
	actions: {
		*addCartItem(): Generator< unknown, void > {
			// Fully agnostic — ONE path. `addItem()` (no argument) resolves the
			// payload itself: the context draft when one exists (a stepper-bearing
			// collection card, or the Add to Cart + Options form's draft), else
			// its `{ id: productInContext id, quantity: min }` fallback for a bare
			// Product Button. Adds are adds — `addItem` always POSTs add-item, so
			// rapid clicks compound server-side.
			const context = getContext< Context >();

			yield cartActions.addItem();

			context.displayViewCart = true;
		},
		handleAnimationEnd( event: AnimationEvent ) {
			const context = getContext< Context >();
			if ( event.animationName === 'slideOut' ) {
				// When the first part of the animation (slide-out) ends, we move
				// to the second part (slide-in).
				context.animationStatus = AnimationStatus.SLIDE_IN;
			} else if ( event.animationName === 'slideIn' ) {
				// When the second part of the animation ends, we update the
				// temporary quantity to sync it with the cart and reset the
				// animation status so it can be triggered again.
				context.tempQuantity = state.inCartQuantity;
				context.animationStatus = AnimationStatus.IDLE;
			}
		},
		handlePressedState() {
			const context = getContext< Context >();

			// Only handle the pressed state if the form is valid.
			if (
				addToCartWithOptionsState?.isFormValid === undefined ||
				addToCartWithOptionsState?.isFormValid
			) {
				context.hasPressedButton = true;

				// Only animate if the quantity number changes and there is no
				// animation in progress.
				if (
					context.tempQuantity !== state.inCartQuantity &&
					context.animationStatus === AnimationStatus.IDLE
				) {
					context.animationStatus = AnimationStatus.SLIDE_OUT;
				}
			}
		},
	},
	callbacks: {
		syncTempQuantityOnLoad() {
			const context = getContext< Context >();
			// When we instantiate this element, we sync the temporary
			// quantity with the quantity in the cart to avoid triggering
			// the animation. We do this only once, and we use
			// useLayoutEffect to avoid the useEffect flickering.
			// eslint-disable-next-line react-hooks/rules-of-hooks
			useLayoutEffect( () => {
				context.tempQuantity = state.inCartQuantity;
				// eslint-disable-next-line react-hooks/exhaustive-deps
			}, [] );
		},
		startAnimation() {
			const context = getContext< Context >();
			// We start the animation if the temporary quantity is out of
			// sync with the quantity in the cart and the animation hasn't
			// started yet.
			if (
				context.tempQuantity !== state.inCartQuantity &&
				context.animationStatus === AnimationStatus.IDLE
			) {
				context.animationStatus = AnimationStatus.SLIDE_OUT;
			}
		},
	},
};

const { state } = store< typeof productButtonStore & ServerState >(
	'woocommerce/product-button',
	productButtonStore,
	{ lock: true }
);
