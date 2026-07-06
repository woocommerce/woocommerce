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
import type {
	Context as AddToCartWithOptionsContext,
	AddToCartWithOptionsStore,
} from '../../../../blocks/add-to-cart-with-options/frontend';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

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

const { state: wooState } = store< WooCommerce >(
	'woocommerce',
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
		get quantity(): number {
			const product = productsState.productInContext;

			if ( ! product ) {
				return 0;
			}

			// T9 DEMO — grocery pattern (E48): when the card has a seeded draft
			// (stepper present, product identity from the `<li>`'s
			// `woocommerce/products` context — T12), read the in-cart line through
			// the `itemInContext` envelope instead of a raw scan. With the item
			// unambiguously in the cart, `itemInContext.cart` is the exact line, so
			// "X in cart" reads from the envelope — the read-side half of the
			// boundary-break.
			const envelope = wooState.itemInContext;
			if ( envelope?.draft ) {
				return envelope.cart?.quantity ?? 0;
			}

			const formContext = getContext< AddToCartWithOptionsContext >(
				'woocommerce/add-to-cart-with-options'
			);

			const item = wooState.findItemInCart( {
				id: product.id,
				variation: formContext?.selectedAttributes,
			} );

			return item?.quantity ?? 0;
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

			if ( productsState.productInContext?.type === 'grouped' ) {
				const groupedProductIdsInCart = groupedProductIds?.map(
					( productId ) => {
						const product = wooState.findItemInCart( {
							id: productId,
						} );
						return product?.quantity || 0;
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
			if ( ! displayViewCart ) return false;
			return state.quantity > 0;
		},
	},
	actions: {
		*addCartItem(): Generator< unknown, void > {
			const product = productsState.productInContext;

			if ( ! product ) {
				return;
			}

			// T9 DEMO PATH (boundary-breaking use case E14/E48, PR #65570).
			// When this button sits in a Product Collection card that also
			// renders a Product Quantity (stepper) block, the server seeds a draft
			// for the card's product (ProductTemplate::seed_card_draft) and the
			// card's product identity comes from the `<li>`'s
			// `woocommerce/products` context (T12). The stepper edits that draft;
			// the button must POST it (with the shopper-chosen quantity) via
			// `addItem()` instead of the legacy fixed-quantity `addCartItem`. We
			// detect the case by the presence of a context draft — resolved
			// SYNCHRONOUSLY, before any `yield`, because the context is only
			// guaranteed in scope for the synchronous portion of a generator
			// action (same rule the ATCWO submit handler follows). NOTE: this is a
			// deliberately narrow demo branch; the button's full migration onto
			// drafts/envelope is T7a.
			const contextDraft = wooState.itemInContext?.draft;
			const context = getContext< Context >();

			if ( contextDraft ) {
				// Todo: Use the module exports instead of `store()` once the
				// woocommerce store is public.
				yield import( '@woocommerce/stores/woocommerce/cart' );

				const { actions: wooActions } = store< WooCommerce >(
					'woocommerce',
					{},
					{ lock: universalLock }
				);

				// Post the draft (identity rule 5: adds are adds). The draft
				// carries the stepper's chosen quantity.
				yield wooActions.addItem( contextDraft );

				context.displayViewCart = true;
				return;
			}

			// Todo: Use the module exports instead of `store()` once the
			// woocommerce store is public.
			yield import( '@woocommerce/stores/woocommerce/cart' );

			const { actions } = store< WooCommerce >(
				'woocommerce',
				{},
				{ lock: universalLock }
			);

			// Pass quantityToAdd as a delta. The cart store will add this
			// to the current quantity, ensuring rapid clicks compound correctly.
			yield actions.addCartItem(
				{
					id: product.id,
					quantityToAdd: context.quantityToAdd,
					type: product.type,
				},
				{
					showCartUpdatesNotices: false,
				}
			);

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
