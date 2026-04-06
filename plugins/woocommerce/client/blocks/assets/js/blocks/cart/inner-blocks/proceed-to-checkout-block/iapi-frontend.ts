/**
 * External dependencies
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
/**
 * The cart events emitter is loaded as a traditional script and exposed
 * on window.wc.blocksCartEvents. We access it at runtime rather than
 * importing from source to avoid pulling @woocommerce/types into the
 * script module build.
 */
type CartEventsEmitter = {
	emitWithAbort: (
		eventName: string,
		data: unknown
	) => Promise< Array< { type: string } > >;
};

const getCartEventsEmitter = (): CartEventsEmitter =>
	( window as unknown as { wc: { blocksCartEvents: CartEventsEmitter } } ).wc
		.blocksCartEvents;

const CART_EVENTS = {
	PROCEED_TO_CHECKOUT: 'cart_proceed_to_checkout',
} as const;

/**
 * Check if an observer response is an error type.
 * Inlined to avoid importing @woocommerce/types (not available in script modules).
 */
const isErrorOrFailResponse = ( response: unknown ): boolean => {
	if (
		typeof response !== 'object' ||
		response === null ||
		! ( 'type' in response )
	) {
		return false;
	}
	const type = ( response as { type: string } ).type;
	return type === 'error' || type === 'failure';
};

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
				const emitter = getCartEventsEmitter();
				const responses: Array< { type: string } > =
					yield emitter.emitWithAbort(
					CART_EVENTS.PROCEED_TO_CHECKOUT,
					null
				);

				if ( responses.some( isErrorOrFailResponse ) ) {
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
