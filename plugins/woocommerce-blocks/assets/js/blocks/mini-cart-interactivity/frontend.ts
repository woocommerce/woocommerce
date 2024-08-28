/**
 * External dependencies
 */
import { getContext, store } from '@woocommerce/interactivity';
import { select, subscribe } from '@wordpress/data';
import preloadScript from '@woocommerce/base-utils/preload-script';
import lazyLoadScript, {
	isScriptTagInDOM,
} from '@woocommerce/base-utils/lazy-load-script';
import {
	getNavigationType,
	translateJQueryEventToNative,
} from '@woocommerce/base-utils';
import { _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { CART_STORE_KEY } from '../../data';
import {
	getAmount,
	getMiniCartTotalsFromLocalStorage,
	getMiniCartTotalsFromServer,
} from './utils/data';
import setStyles from '../mini-cart/utils/set-styles';

interface dependencyData {
	src: string;
	version?: string;
	after?: string;
	before?: string;
	translations?: string;
}

interface Store {
	state: {
		displayQuantityBadgeStyle: string;
		priceAriaLabel: string;
	};
	callbacks: {
		initialize: () => void;
		toggleDrawerOpen: ( event: Event ) => void;
		loadScripts: () => Promise< void >;
	};
}

interface Context {
	cartItemCount: number;
	drawerOpen: boolean;
	scriptsLoaded: boolean;
	drawerIsLoading: boolean;
	// JSON stringified cart totals.
	cartItemTotals: string;
	amount: string;
	hasHiddenPrice: boolean;
}

setStyles();

declare global {
	interface Window {
		wcBlocksMiniCartInteractivityFrontendDependencies: Record<
			string,
			dependencyData
		>;
	}
}

// Make it so we can read jQuery events triggered by WC Core elements.
const removeJQueryAddingToCartEvent = translateJQueryEventToNative(
	'adding_to_cart',
	'wc-blocks_adding_to_cart'
);
const removeJQueryAddedToCartEvent = translateJQueryEventToNative(
	'added_to_cart',
	'wc-blocks_added_to_cart'
);
const removeJQueryRemovedFromCartEvent = translateJQueryEventToNative(
	'removed_from_cart',
	'wc-blocks_removed_from_cart'
);

const loadScripts = async () => {
	const dependencies =
		window.wcBlocksMiniCartInteractivityFrontendDependencies;

	// Lazy load scripts.
	for ( const dependencyHandle in dependencies ) {
		const dependency = dependencies[ dependencyHandle ];

		// This check is performed within lazyLoadScript, but if we do it here it simplifies logic of when to call loadScripts.
		if (
			! isScriptTagInDOM( `${ dependencyHandle }-js`, dependency.src )
		) {
			await lazyLoadScript( {
				handle: dependencyHandle,
				...dependency,
			} );
		}
	}
};

store< Store >( 'woocommerce/mini-cart-interactivity', {
	state: {
		get displayQuantityBadgeStyle() {
			const context = getContext< Context >();
			return context.cartItemCount > 0 ? 'flex' : 'none';
		},

		get priceAriaLabel() {
			const context = getContext< Context >();

			const { cartItemCount, hasHiddenPrice, amount } = context;

			return hasHiddenPrice
				? sprintf(
						/* translators: %s number of products in cart. */
						_n(
							'%1$d item in cart',
							'%1$d items in cart',
							cartItemCount,
							'woocommerce'
						),
						cartItemCount
				  )
				: sprintf(
						/* translators: %1$d is the number of products in the cart. %2$s is the cart total */
						_n(
							'%1$d item in cart, total price of %2$s',
							'%1$d items in cart, total price of %2$s',
							cartItemCount,
							'woocommerce'
						),
						cartItemCount,
						amount
				  );
		},
	},

	callbacks: {
		initialize: async () => {
			const context = getContext< Context >();

			// First populate the cartItemTotals with the totals from the local storage.
			const localStorageTotals = getMiniCartTotalsFromLocalStorage();

			if ( localStorageTotals ) {
				const [ totals, quantity ] = localStorageTotals;
				const amount = getAmount( totals );
				context.cartItemTotals = JSON.stringify( totals );
				context.cartItemCount = quantity;
				context.amount = amount;
			}

			const serverTotals = await getMiniCartTotalsFromServer();

			if ( serverTotals ) {
				const [ serverUpdatedTotals, serverUpdatedQuantity ] =
					serverTotals;
				const amount = getAmount( serverUpdatedTotals );
				// If we have the totals from the server, update them on the dataset.
				context.cartItemTotals = JSON.stringify( serverUpdatedTotals );
				context.cartItemCount = serverUpdatedQuantity;
				context.amount = amount;
			}

			subscribe( () => {
				const cartData = select( CART_STORE_KEY ).getCartData();

				const isResolutionFinished =
					select( CART_STORE_KEY ).hasFinishedResolution(
						'getCartData'
					);
				if ( isResolutionFinished ) {
					context.drawerIsLoading = false;
					context.cartItemCount = cartData.itemsCount;
				}
			} );
		},

		toggleDrawerOpen: () => {
			const context = getContext< Context >();
			context.drawerOpen = ! context.drawerOpen;

			if ( context.drawerOpen ) {
				document.body.dispatchEvent(
					new Event( 'wc-mini-cart-interactivity-open-drawer' )
				);
			}
		},

		loadScripts: async () => {
			const context = getContext< Context >();

			if ( ! context.scriptsLoaded ) {
				context.scriptsLoaded = true;
				await loadScripts();
			}

			// Remove adding to cart event handler.
			document.body.removeEventListener(
				'wc-blocks_adding_to_cart',
				loadScripts
			);

			document.body.addEventListener(
				'wc-mini-cart-interactivity-close-drawer',
				() => {
					context.drawerOpen = false;
				}
			);

			removeJQueryAddedToCartEvent();
			removeJQueryRemovedFromCartEvent();
			removeJQueryAddingToCartEvent();
		},
	},
} );

window.addEventListener( 'load', () => {
	const miniCartBlocks = document.querySelectorAll(
		'.wc-block-mini-cart-interactivity'
	);

	if ( miniCartBlocks.length === 0 ) {
		return;
	}

	const dependencies =
		window.wcBlocksMiniCartInteractivityFrontendDependencies;

	// Preload scripts
	for ( const dependencyHandle in dependencies ) {
		const dependency = dependencies[ dependencyHandle ];
		preloadScript( {
			handle: dependencyHandle,
			...dependency,
		} );
	}

	document.body.addEventListener( 'wc-blocks_adding_to_cart', loadScripts );

	// Load scripts if a page is reloaded via the back button (potentially out of date cart data).
	// Based on refreshCachedCartData() in assets/js/base/context/cart-checkout/cart/index.js.
	window.addEventListener(
		'pageshow',
		( event: PageTransitionEvent ): void => {
			if ( event?.persisted || getNavigationType() === 'back_forward' ) {
				loadScripts();
			}
		}
	);
} );
