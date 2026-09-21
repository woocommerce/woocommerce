/**
 * External dependencies
 */
import { store, getContext, useLayoutEffect } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce';
import type {
	WooCommerceStore,
	ProductScopeContext,
} from '@woocommerce/stores/woocommerce';

/**
 * Internal dependencies
 */
import type { AddToCartWithOptionsStore } from '../../../../blocks/add-to-cart-with-options/frontend';

interface Context {
	addToCartText: string;
	groupedProductIds?: number[];
	displayViewCart: boolean;
	quantityToAdd: number;
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

const { state: wooState, actions: wooActions } = store< WooCommerceStore >(
	'woocommerce',
	{},
	{
		lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
	}
);

const { state: addToCartWithOptionsState } = store< AddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{},
	{
		lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
	}
);

const productButtonStore = {
	state: {
		get quantity(): number {
			const product = wooState.productScope.product;

			if ( ! product ) {
				return 0;
			}

			return wooState.productScope.cartItem?.quantity ?? 0;
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
				: state.quantity;

			if ( wooState.productScope.product?.type === 'grouped' ) {
				const groupedProductIdsInCart = groupedProductIds?.map(
					( productId ) => {
						const cartItem = wooState.findProductScope( {
							productId,
						} ).cartItem;
						return cartItem?.quantity || 0;
					}
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
			if ( ! displayViewCart ) {
				return false;
			}
			return state.quantity > 0;
		},
	},
	actions: {
		*addCartItem(): Generator< unknown, void > {
			const product = wooState.productScope.product;

			if ( ! product ) {
				return;
			}

			const context = getContext< Context >();
			const scopeContext =
				getContext< ProductScopeContext >( 'woocommerce' );
			const scopeName = scopeContext?.scopeName ?? '_default';
			const record = wooState.productScopes[ scopeName ]?.draftCartItem;
			const { variation } = wooState.productScope;

			// The payload form removes no record, so a shopper's typed
			// quantity in a form sharing this scope survives the click.
			// The button has no quantity input of its own, so it always
			// posts its own filtered `quantityToAdd`, never the scope's
			// draft quantity.
			yield wooActions.addCartItem(
				{
					...record,
					id: product.id,
					variation,
					quantity: context.quantityToAdd,
				},
				{
					showCartUpdatesNotices: false,
				}
			);

			context.displayViewCart = true;
		},
		*refreshCart() {
			yield wooActions.refreshCart();
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
				context.tempQuantity = state.quantity;
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
					context.tempQuantity !== state.quantity &&
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
				context.tempQuantity = state.quantity;
				// eslint-disable-next-line react-hooks/exhaustive-deps
			}, [] );
		},
		startAnimation() {
			const context = getContext< Context >();
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
};

const { state } = store< typeof productButtonStore & ServerState >(
	'woocommerce/product-button',
	productButtonStore,
	{ lock: true }
);
