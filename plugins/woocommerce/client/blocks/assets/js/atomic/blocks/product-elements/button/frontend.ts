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
	groupedProductIds?: number[];
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
		// The "X in cart" quantity, resolved through the shared-store envelope
		// keyed by the main/context product id (`mainProductInContext` — the
		// parent, never a variation). `findItem({ id })` runs the resolution
		// ladder against the context draft when one exists (a stepper-bearing
		// collection card, or the form's draft, which carries the variation),
		// else against a bare `{ id }` draft (a plain Product Button on a
		// shop/grid). Both collapse to the same call.
		get inCartQuantity(): number {
			const mainProduct = productsState.mainProductInContext;

			if ( ! mainProduct ) {
				return 0;
			}

			return (
				cartState.findItem( { id: mainProduct.id } ).cart?.quantity ?? 0
			);
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
				groupedProductIds,
				hasPressedButton,
				inTheCartText,
			} = getContext< Context >();

			// We use the temporary quantity when there's no animation, or
			// when the second part of the animation hasn't started yet.
			const showTemporaryNumber =
				animationStatus === AnimationStatus.IDLE ||
				animationStatus === AnimationStatus.SLIDE_OUT;
			const quantity = showTemporaryNumber
				? tempQuantity || 0
				: state.inCartQuantity;

			if ( productsState.productInContext?.type === 'grouped' ) {
				// Grouped products keep one draft per CHILD (keyed by child id),
				// not by the grouped parent, so the parent has no envelope line of
				// its own. The button's own server-seeded `groupedProductIds`
				// context is the only handle to the child ids; each child's
				// in-cart quantity is read through the shared envelope
				// (`findItem({ id: childId })`), same surface as above.
				const groupedProductIdsInCart = groupedProductIds?.map(
					( productId ) =>
						cartState.findItem( { id: productId } ).cart
							?.quantity || 0
				);
				if (
					groupedProductIdsInCart?.some( ( qty ) => qty > 0 ) &&
					hasPressedButton
				) {
					return inTheCartText;
				}
				return addToCartText;
			}

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
