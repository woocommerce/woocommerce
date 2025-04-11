/**
 * External dependencies
 */
import {
	store,
	getContext as getContextFn,
	useLayoutEffect,
} from '@wordpress/interactivity';
import type { Store as WooCommerce } from '@woocommerce/stores/woocommerce/cart';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

interface Context {
	addToCartText: string;
	productId: number;
	displayViewCart: boolean;
	quantityToAdd: number;
	tempQuantity: number;
	animationStatus: AnimationStatus;
}

enum AnimationStatus {
	IDLE = 'IDLE',
	SLIDE_OUT = 'SLIDE-OUT',
	SLIDE_IN = 'SLIDE-IN',
}

interface Store {
	state: {
		inTheCartText: string;
		quantity: number;
		hasCartLoaded: boolean;
		slideInAnimation: boolean;
		slideOutAnimation: boolean;
		addToCartText: string;
		displayViewCart: boolean;
		noticeId: string;
	};
	actions: {
		addCartItem: () => void;
		refreshCartItems: () => void;
		handleAnimationEnd: ( event: AnimationEvent ) => void;
	};
	callbacks: {
		startAnimation: () => void;
		syncTempQuantityOnLoad: () => void;
	};
}

const getContext = () => getContextFn< Context >();

const { state: wooState } = store< WooCommerce >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

const { state } = store< Store >(
	'woocommerce/product-button',
	{
		state: {
			get quantity() {
				const { productId } = getContext();
				const product = wooState.cart?.items.find(
					( item ) => item.id === productId
				);
				return product?.quantity || 0;
			},
			get slideInAnimation() {
				const { animationStatus } = getContext();
				return animationStatus === AnimationStatus.SLIDE_IN;
			},
			get slideOutAnimation() {
				const { animationStatus } = getContext();
				return animationStatus === AnimationStatus.SLIDE_OUT;
			},
			get addToCartText(): string {
				const { animationStatus, tempQuantity, addToCartText } =
					getContext();

				// We use the temporary quantity when there's no animation, or
				// when the second part of the animation hasn't started yet.
				const showTemporaryNumber =
					animationStatus === AnimationStatus.IDLE ||
					animationStatus === AnimationStatus.SLIDE_OUT;
				const quantity = showTemporaryNumber
					? tempQuantity || 0
					: state.quantity;

				if ( quantity === 0 ) return addToCartText;

				return state.inTheCartText.replace(
					'###',
					quantity.toString()
				);
			},
			get displayViewCart(): boolean {
				const { displayViewCart } = getContext();
				if ( ! displayViewCart ) return false;
				return state.quantity > 0;
			},
		},
		actions: {
			*addCartItem() {
				const context = getContext();
				const { productId, quantityToAdd } = context;

				// Todo: Use the module exports instead of `store()` once the
				// woocommerce store is public.
				yield import( '@woocommerce/stores/woocommerce/cart' );

				const { actions } = store< WooCommerce >(
					'woocommerce',
					{},
					{ lock: universalLock }
				);

				yield actions.addCartItem( {
					id: productId,
					quantity: state.quantity + quantityToAdd,
				} );

				context.displayViewCart = true;
			},
			*refreshCartItems() {
				// Todo: Use the module exports instead of `store()` once the
				// woocommerce store is public.
				yield import( '@woocommerce/stores/woocommerce/cart' );
				const { actions } = store< WooCommerce >(
					'woocommerce',
					{},
					{ lock: universalLock }
				);
				actions.refreshCartItems();
			},
			handleAnimationEnd( event: AnimationEvent ) {
				const context = getContext();
				if ( event.animationName === 'slideOut' ) {
					// When the first part of the animation (slide-out) ends, we move
					// to the second part (slide-in).
					context.animationStatus = AnimationStatus.SLIDE_IN;
				} else if ( event.animationName === 'slideIn' ) {
					// When the second part of the animation ends, we update the
					// temporary quantity to sync it with the cart and reset the
					// animation status so it can be triggered again.
					context.tempQuantity = state.quantity;
					context.animationStatus = AnimationStatus.IDLE;
				}
			},
		},
		callbacks: {
			syncTempQuantityOnLoad() {
				const context = getContext();
				// When we instantiate this element, we sync the temporary
				// quantity with the quantity in the cart to avoid triggering
				// the animation. We do this only once, and we use
				// useLayoutEffect to avoid the useEffect flickering.
				// eslint-disable-next-line react-hooks/rules-of-hooks
				useLayoutEffect( () => {
					context.tempQuantity = state.quantity;
					// eslint-disable-next-line react-hooks/exhaustive-deps
				}, [] );
			},
			startAnimation() {
				const context = getContext();
				// We start the animation if the temporary quantity is out of
				// sync with the quantity in the cart and the animation hasn't
				// started yet.
				if (
					context.tempQuantity !== state.quantity &&
					context.animationStatus === AnimationStatus.IDLE
				) {
					context.animationStatus = AnimationStatus.SLIDE_OUT;
				}
			},
		},
	},
	{ lock: true }
);
