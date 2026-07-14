/**
 * External dependencies
 */
import { store, getContext, useLayoutEffect } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/products';
import type { Store as WooCommerce } from '@woocommerce/stores/woocommerce/cart';
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import type { AddToCartWithOptionsStore } from '../../../../blocks/add-to-cart-with-options/frontend';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

/**
 * The button's own local context, server-seeded per instance by
 * `ProductButton::render()`.
 *
 * Product identity and cart state are read from `woocommerce/products` and
 * `woocommerce/cart` (see `productButtonStore` below); nothing here carries
 * form- or wrapper-specific data — the button never knows whether it is
 * standalone, inside a form, or on a collection card.
 */
export interface Context {
	/** The label shown when the product is not (yet) in the cart. */
	addToCartText: string;
	/** Displays the "View cart" link once something has been added. */
	displayViewCart: boolean;
	/** The quantity delta this click adds, posted verbatim to `addItem()`. */
	quantityToAdd: number;
	/** The quantity last synced into the label before an animation runs. */
	tempQuantity: number;
	animationStatus: AnimationStatus;
	/** Whether this button has been clicked this session; gates the grouped-product "in cart" label so leftover cart state from a prior visit doesn't show it prematurely. */
	hasPressedButton: boolean;
	/** The label template shown once the product is in the cart. */
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

const { state: wooState } = store< WooCommerce >(
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
		get slideInAnimation() {
			const { animationStatus } = getContext< Context >();
			return animationStatus === AnimationStatus.SLIDE_IN;
		},
		get slideOutAnimation() {
			const { animationStatus } = getContext< Context >();
			return animationStatus === AnimationStatus.SLIDE_OUT;
		},
		/**
		 * The button's label.
		 *
		 * Derives product identity from `productsState.productInContext` and
		 * reads `wooState.inCartQuantity` for the in-cart count — the same
		 * envelope-backed getter the cart store exposes to every consumer, so
		 * this resolves identically whether the button renders standalone,
		 * inside a form, or on a collection card. It never reads any
		 * form-specific context.
		 */
		get addToCartText(): string {
			const {
				animationStatus,
				tempQuantity,
				addToCartText,
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
				: wooState.inCartQuantity;

			if ( productsState.productInContext?.type === 'grouped' ) {
				// `inCartQuantity` already aggregates every grouped child's
				// own in-cart quantity (see the cart store's getter); the
				// grouped label itself only ever toggles between "add to
				// cart" and a static "in cart" string (no interpolated
				// count), and — unlike the simple/variable branch below —
				// only shows once `hasPressedButton`, so cart state left
				// over from a previous visit never shows it prematurely.
				if ( wooState.inCartQuantity > 0 && hasPressedButton ) {
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
			return wooState.inCartQuantity > 0;
		},
	},
	actions: {
		/**
		 * Adds the in-context product to the cart.
		 *
		 * Derives the product solely from `productsState.productInContext`
		 * and posts `context.quantityToAdd` as an explicit `addItem()`
		 * payload — a delta, added by the server to any existing line, so
		 * rapid clicks keep compounding correctly. No draft is involved:
		 * this is the standalone/collection-card path, which has no form to
		 * have populated one.
		 */
		*addItem(): Generator< unknown, void > {
			const product = productsState.productInContext;

			if ( ! product ) {
				return;
			}

			// Todo: Use the module exports instead of `store()` once the
			// woocommerce store is public.
			yield import( '@woocommerce/stores/woocommerce/cart' );

			const { actions } = store< WooCommerce >(
				'woocommerce/cart',
				{},
				{ lock: universalLock }
			);

			const context = getContext< Context >();

			yield actions.addItem( {
				id: product.id,
				quantity: context.quantityToAdd,
			} );

			context.displayViewCart = true;
		},
		*refresh() {
			// Todo: Use the module exports instead of `store()` once the
			// woocommerce store is public.
			yield import( '@woocommerce/stores/woocommerce/cart' );
			const { actions } = store< WooCommerce >(
				'woocommerce/cart',
				{},
				{ lock: universalLock }
			);
			actions.refresh();
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
				context.tempQuantity = wooState.inCartQuantity;
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
					context.tempQuantity !== wooState.inCartQuantity &&
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
				context.tempQuantity = wooState.inCartQuantity;
				// eslint-disable-next-line react-hooks/exhaustive-deps
			}, [] );
		},
		startAnimation() {
			const context = getContext< Context >();
			// We start the animation if the temporary quantity is out of
			// sync with the quantity in the cart and the animation hasn't
			// started yet.
			if (
				context.tempQuantity !== wooState.inCartQuantity &&
				context.animationStatus === AnimationStatus.IDLE
			) {
				context.animationStatus = AnimationStatus.SLIDE_OUT;
			}
		},
	},
};

store< typeof productButtonStore & ServerState >(
	'woocommerce/product-button',
	productButtonStore,
	{ lock: true }
);
