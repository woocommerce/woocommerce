/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import { isErrorResponse } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { cartEventsEmitter, CART_EVENTS } from '../../../../events/cart-events';

type WooCommerce = {
	state: {
		cart: {
			items: unknown[];
		};
		isProcessing: boolean;
	};
};

type ProceedToCheckoutContext = {
	checkoutUrl: string;
	buttonLabel: string;
	isLoading: boolean;
};

type ProceedToCheckoutStore = {
	state: {
		readonly isDisabled: boolean;
		isStickyVisible: boolean;
		stickyBackgroundColor: string;
	};
	actions: {
		handleClick: ( event: MouseEvent ) => void;
	};
	callbacks: {
		onPageShow: () => () => void;
		initStickyObserver: () => () => void;
	};
};

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

// Access shared woocommerce store for cart state.
const { state: woocommerceState } = store< WooCommerce >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

// Store-level state for sticky behavior. These are in the store's state
// object so the IAPI reactivity system tracks reads/writes and triggers
// re-renders of directives that depend on them.
const { state: ptcState } = store< ProceedToCheckoutStore >(
	'woocommerce/proceed-to-checkout',
	{
		state: {
			get isDisabled(): boolean {
				return woocommerceState.isProcessing;
			},
			isStickyVisible: false,
			stickyBackgroundColor: '',
		},
		actions: {
			*handleClick( event: MouseEvent ) {
				event.preventDefault();

				const context = getContext< ProceedToCheckoutContext >();

				if ( woocommerceState.isProcessing || context.isLoading ) {
					return;
				}

				// Dispatch proceed-to-checkout event. Observers can abort.
				const responses: Awaited<
					ReturnType< typeof cartEventsEmitter.emitWithAbort >
				> = yield cartEventsEmitter.emitWithAbort(
					CART_EVENTS.PROCEED_TO_CHECKOUT,
					null
				);

				if ( responses.some( isErrorResponse ) ) {
					return;
				}

				context.isLoading = true;
				window.location.href = context.checkoutUrl;
			},
		},
		callbacks: {
			onPageShow() {
				// Capture context while in directive scope (getContext only
				// works inside IAPI directive callbacks, not in event handlers).
				const context = getContext< ProceedToCheckoutContext >();
				const callback = () => {
					context.isLoading = false;
				};
				window.addEventListener( 'pageshow', callback );
				return () => {
					window.removeEventListener( 'pageshow', callback );
				};
			},
			initStickyObserver() {
				const { ref } = getElement();
				if ( ! ref ) {
					return;
				}

				// Compute body background color once.
				const computedColor =
					getComputedStyle( document.body ).backgroundColor;
				const bgColor =
					! computedColor ||
					computedColor === 'rgba(0, 0, 0, 0)' ||
					computedColor === 'transparent'
						? '#fff'
						: computedColor;

				const observer = new IntersectionObserver(
					( entries ) => {
						const entry = entries[ 0 ];
						if ( entry.isIntersecting ) {
							ptcState.isStickyVisible = false;
							ptcState.stickyBackgroundColor = '';
						} else {
							const isBelow =
								entry.boundingClientRect.top > 0;
							ptcState.isStickyVisible = isBelow;
							ptcState.stickyBackgroundColor = isBelow
								? bgColor
								: '';
						}
					},
					{ threshold: [ 0, 0.5, 1 ] }
				);

				observer.observe( ref );

				return () => {
					observer.disconnect();
				};
			},
		},
	},
	{ lock: true }
);
