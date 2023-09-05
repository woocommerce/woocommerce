/* eslint-disable @typescript-eslint/no-explicit-any */
/**
 * External dependencies
 */
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { store as interactivityStore } from '@woocommerce/interactivity';
import { dispatch, select, subscribe } from '@wordpress/data';
import { Cart } from '@woocommerce/type-defs/cart';
import { createRoot } from '@wordpress/element';
import NoticeBanner from '@woocommerce/base-components/notice-banner';

type Context = {
	woocommerce: {
		isLoading: boolean;
		addToCartText: string;
		productId: number;
		displayViewCart: boolean;
		quantityToAdd: number;
		temporaryNumberOfItems: number;
		animationStatus: AnimationStatus;
	};
};

enum AnimationStatus {
	IDLE = 'IDLE',
	SLIDE_OUT = 'SLIDE-OUT',
	SLIDE_IN = 'SLIDE-IN',
}

type State = {
	woocommerce: {
		cart: Cart | undefined;
		inTheCartText: string;
	};
};

type Store = {
	state: State;
	context: Context;
	selectors: any;
	ref: HTMLElement;
};

const storeNoticeClass = '.wc-block-store-notices';

const createNoticeContainer = () => {
	const noticeContainer = document.createElement( 'div' );
	noticeContainer.classList.add( storeNoticeClass.replace( '.', '' ) );
	return noticeContainer;
};

const injectNotice = ( domNode: Element, errorMessage: string ) => {
	const root = createRoot( domNode );

	root.render(
		<NoticeBanner status="error" onRemove={ () => root.unmount() }>
			{ errorMessage }
		</NoticeBanner>
	);

	domNode?.scrollIntoView( {
		behavior: 'smooth',
		inline: 'nearest',
	} );
};

const getProductById = ( cartState: Cart | undefined, productId: number ) => {
	return cartState?.items.find( ( item ) => item.id === productId );
};

const getTextButton = ( {
	addToCartText,
	inTheCartText,
	numberOfItems,
}: {
	addToCartText: string;
	inTheCartText: string;
	numberOfItems: number;
} ) => {
	if ( numberOfItems === 0 ) {
		return addToCartText;
	}
	return inTheCartText.replace( '###', numberOfItems.toString() );
};

const productButtonSelectors = {
	woocommerce: {
		addToCartText: ( store: Store ) => {
			const { context, state, selectors } = store;

			// We use the temporary number of items when there's no animation, or the
			// second part of the animation hasn't started.
			if (
				context.woocommerce.animationStatus === AnimationStatus.IDLE ||
				context.woocommerce.animationStatus ===
					AnimationStatus.SLIDE_OUT
			) {
				return getTextButton( {
					addToCartText: context.woocommerce.addToCartText,
					inTheCartText: state.woocommerce.inTheCartText,
					numberOfItems: context.woocommerce.temporaryNumberOfItems,
				} );
			}

			return getTextButton( {
				addToCartText: context.woocommerce.addToCartText,
				inTheCartText: state.woocommerce.inTheCartText,
				numberOfItems:
					selectors.woocommerce.numberOfItemsInTheCart( store ),
			} );
		},
		displayViewCart: ( store: Store ) => {
			const { context, selectors } = store;
			if ( ! context.woocommerce.displayViewCart ) return false;
			if ( ! selectors.woocommerce.hasCartLoaded( store ) ) {
				return context.woocommerce.temporaryNumberOfItems > 0;
			}
			return selectors.woocommerce.numberOfItemsInTheCart( store ) > 0;
		},
		hasCartLoaded: ( { state }: { state: State } ) => {
			return state.woocommerce.cart !== undefined;
		},
		numberOfItemsInTheCart: ( { state, context }: Store ) => {
			const product = getProductById(
				state.woocommerce.cart,
				context.woocommerce.productId
			);
			return product?.quantity || 0;
		},
		slideOutAnimation: ( { context }: Store ) =>
			context.woocommerce.animationStatus === AnimationStatus.SLIDE_OUT,
		slideInAnimation: ( { context }: Store ) =>
			context.woocommerce.animationStatus === AnimationStatus.SLIDE_IN,
	},
};

interactivityStore(
	// @ts-expect-error: Store function isn't typed.
	{
		selectors: productButtonSelectors,
		actions: {
			woocommerce: {
				addToCart: async ( store: Store ) => {
					const { context, selectors, ref } = store;

					if ( ! ref.classList.contains( 'ajax_add_to_cart' ) ) {
						return;
					}

					context.woocommerce.isLoading = true;

					// Allow 3rd parties to validate and quit early.
					// https://github.com/woocommerce/woocommerce/blob/154dd236499d8a440edf3cde712511b56baa8e45/plugins/woocommerce/client/legacy/js/frontend/add-to-cart.js/#L74-L77
					const event = new CustomEvent(
						'should_send_ajax_request.adding_to_cart',
						{ detail: [ ref ], cancelable: true }
					);
					const shouldSendRequest =
						document.body.dispatchEvent( event );

					if ( shouldSendRequest === false ) {
						const ajaxNotSentEvent = new CustomEvent(
							'ajax_request_not_sent.adding_to_cart',
							{ detail: [ false, false, ref ] }
						);
						document.body.dispatchEvent( ajaxNotSentEvent );
						return true;
					}

					try {
						await dispatch( storeKey ).addItemToCart(
							context.woocommerce.productId,
							context.woocommerce.quantityToAdd
						);

						// After the cart has been updated, sync the temporary number of
						// items again.
						context.woocommerce.temporaryNumberOfItems =
							selectors.woocommerce.numberOfItemsInTheCart(
								store
							);
					} catch ( error ) {
						const storeNoticeBlock =
							document.querySelector( storeNoticeClass );

						if ( ! storeNoticeBlock ) {
							document
								.querySelector( '.entry-content' )
								?.prepend( createNoticeContainer() );
						}

						const domNode =
							storeNoticeBlock ??
							document.querySelector( storeNoticeClass );

						if ( domNode ) {
							injectNotice( domNode, error.message );
						}

						// We don't care about errors blocking execution, but will
						// console.error for troubleshooting.
						// eslint-disable-next-line no-console
						console.error( error );
					} finally {
						context.woocommerce.displayViewCart = true;
						context.woocommerce.isLoading = false;
					}
				},
				handleAnimationEnd: (
					store: Store & { event: AnimationEvent }
				) => {
					const { event, context, selectors } = store;
					if ( event.animationName === 'slideOut' ) {
						// When the first part of the animation (slide-out) ends, we move
						// to the second part (slide-in).
						context.woocommerce.animationStatus =
							AnimationStatus.SLIDE_IN;
					} else if ( event.animationName === 'slideIn' ) {
						// When the second part of the animation ends, we update the
						// temporary number of items to sync it with the cart and reset the
						// animation status so it can be triggered again.
						context.woocommerce.temporaryNumberOfItems =
							selectors.woocommerce.numberOfItemsInTheCart(
								store
							);
						context.woocommerce.animationStatus =
							AnimationStatus.IDLE;
					}
				},
			},
		},
		init: {
			woocommerce: {
				syncTemporaryNumberOfItemsOnLoad: ( store: Store ) => {
					const { selectors, context } = store;
					// If the cart has loaded when we instantiate this element, we sync
					// the temporary number of items with the number of items in the cart
					// to avoid triggering the animation. We do this only once, but we
					// use useLayoutEffect to avoid the useEffect flickering.
					if ( selectors.woocommerce.hasCartLoaded( store ) ) {
						context.woocommerce.temporaryNumberOfItems =
							selectors.woocommerce.numberOfItemsInTheCart(
								store
							);
					}
				},
			},
		},
		effects: {
			woocommerce: {
				startAnimation: ( store: Store ) => {
					const { context, selectors } = store;
					// We start the animation if the cart has loaded, the temporary number
					// of items is out of sync with the number of items in the cart, the
					// button is not loading (because that means the user started the
					// interaction) and the animation hasn't started yet.
					if (
						selectors.woocommerce.hasCartLoaded( store ) &&
						context.woocommerce.temporaryNumberOfItems !==
							selectors.woocommerce.numberOfItemsInTheCart(
								store
							) &&
						! context.woocommerce.isLoading &&
						context.woocommerce.animationStatus ===
							AnimationStatus.IDLE
					) {
						context.woocommerce.animationStatus =
							AnimationStatus.SLIDE_OUT;
					}
				},
			},
		},
	},
	{
		afterLoad: ( store: Store ) => {
			const { state, selectors } = store;
			// Subscribe to changes in Cart data.
			subscribe( () => {
				const cartData = select( storeKey ).getCartData();
				const isResolutionFinished =
					select( storeKey ).hasFinishedResolution( 'getCartData' );
				if ( isResolutionFinished ) {
					state.woocommerce.cart = cartData;
				}
			}, storeKey );

			// This selector triggers a fetch of the Cart data. It is done in a
			// `requestIdleCallback` to avoid potential performance issues.
			requestIdleCallback( () => {
				if ( ! selectors.woocommerce.hasCartLoaded( store ) ) {
					select( storeKey ).getCartData();
				}
			} );
		},
	}
);
