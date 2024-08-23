/**
 * External dependencies
 */
import { getContext, store } from '@woocommerce/interactivity';
import { select, subscribe } from '@wordpress/data';
import preloadScript from '@woocommerce/base-utils/preload-script';
import lazyLoadScript from '@woocommerce/base-utils/lazy-load-script';
import {
	// getValidBlockAttributes,
	translateJQueryEventToNative,
} from '@woocommerce/base-utils';
// import { renderParentBlock } from '@woocommerce/atomic-utils';
// import { getRegisteredBlockComponents } from '@woocommerce/blocks-registry';

/**
 * Internal dependencies
 */
import { CART_STORE_KEY } from '../../data';
import {
	getMiniCartTotalsFromLocalStorage,
	getMiniCartTotalsFromServer,
	updateTotals,
} from '../mini-cart/utils/data';
import setStyles from '../mini-cart/utils/set-styles';
// import { MiniCartContentsBlock } from '../mini-cart/mini-cart-contents/block';
// import {
// 	blockName,
// 	attributes as miniCartContentsAttributes,
// } from '../mini-cart/mini-cart-contents/attributes';

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
		drawerClasses: string;
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
}

updateTotals( getMiniCartTotalsFromLocalStorage() );
getMiniCartTotalsFromServer().then( updateTotals );
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
// const removeJQueryAddingToCartEvent = translateJQueryEventToNative(
// 	'adding_to_cart',
// 	'wc-blocks_adding_to_cart'
// );

// const removeJQueryAddedToCartEvent = translateJQueryEventToNative(
// 	'added_to_cart',
// 	'wc-blocks_added_to_cart'
// );

// const removeJQueryRemovedFromCartEvent = translateJQueryEventToNative(
// 	'removed_from_cart',
// 	'wc-blocks_removed_from_cart'
// );

// const loadScripts = async () => {
// 	removeJQueryAddingToCartEvent();

// 	document.body.removeEventListener(
// 		'wc-blocks_adding_to_cart',
// 		loadScripts
// 	);

// 	const dependencies = window.wcBlocksMiniCartFrontendDependencies;

// 	console.log( dependencies );

// 	// Lazy load scripts.
// 	for ( const dependencyHandle in dependencies ) {
// 		const dependency = dependencies[ dependencyHandle ];
// 		await lazyLoadScript( {
// 			handle: dependencyHandle,
// 			...dependency,
// 		} );
// 	}
// };

store< Store >( 'woocommerce/mini-cart-interactivity', {
	state: {
		get displayQuantityBadgeStyle() {
			const context = getContext< Context >();
			return context.cartItemCount > 0 ? 'flex' : 'none';
		},

		get drawerClasses() {
			const context = getContext< Context >();

			return ! context.drawerOpen
				? 'wc-block-components-drawer__screen-overlay--is-hidden'
				: 'wc-block-components-drawer__screen-overlay--with-slide-in';
		},
	},

	callbacks: {
		initialize: () => {
			const context = getContext< Context >();
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
				// TODO - if we leave this here is non optimal because it means we immediately load the script deps we used to lazy load.
				// renderParentBlock( {
				// 	// @ts-expect-error - The type of renderParentBlock's Block argument is incorrect.
				// 	Block: MiniCartContentsBlock,
				// 	blockName,
				// 	getProps: ( el: Element ) => {
				// 		return {
				// 			attributes: getValidBlockAttributes(
				// 				miniCartContentsAttributes,
				// 				/* eslint-disable @typescript-eslint/no-explicit-any */
				// 				( el instanceof HTMLElement
				// 					? el.dataset
				// 					: {} ) as any
				// 			),
				// 		};
				// 	},
				// 	selector: '.wp-block-woocommerce-mini-cart-contents',
				// 	blockMap: getRegisteredBlockComponents( blockName ),
				// } );
			}
		},

		// loadScripts: async () => {
		// 	const context = getContext< Context >();
		// 	// Ensure we only call loadScripts once.
		// 	if ( context.scriptsLoaded ) {
		// 		return;
		// 	}

		// 	context.scriptsLoaded = true;

		// 	await loadScripts();

		// 	// Remove adding to cart event handler.
		// },

		// loadContents: async () => {
		// 	const context = getContext< Context >();
		// 	if ( ! context.scriptsLoaded ) {
		// 		loadScripts();
		// 	}

		// 	// document.body.removeEventListener(
		// 	// 	'wc-blocks_added_to_cart',
		// 	// 	// eslint-disable-next-line @typescript-eslint/no-use-before-define
		// 	// 	funcOnAddToCart
		// 	// );
		// 	// document.body.removeEventListener(
		// 	// 	'wc-blocks_removed_from_cart',
		// 	// 	// eslint-disable-next-line @typescript-eslint/no-use-before-define
		// 	// 	loadContentsWithRefresh
		// 	// );
		// 	removeJQueryAddedToCartEvent();
		// 	removeJQueryRemovedFromCartEvent();
		// },
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

	console.log( 'preloaded: ', dependencies );

	// Preload scripts
	for ( const dependencyHandle in dependencies ) {
		const dependency = dependencies[ dependencyHandle ];
		preloadScript( {
			handle: dependencyHandle,
			...dependency,
		} );
	}

	// const loadScripts = async () => {
	// 	// Ensure we only call loadScripts once.
	// 	if ( wasLoadScriptsCalled ) {
	// 		return;
	// 	}
	// 	wasLoadScriptsCalled = true;

	// 	// Remove adding to cart event handler.
	// 	document.body.removeEventListener(
	// 		'wc-blocks_adding_to_cart',
	// 		loadScripts
	// 	);
	// 	removeJQueryAddingToCartEvent();

	// 	// Lazy load scripts.
	// 	for ( const dependencyHandle in dependencies ) {
	// 		const dependency = dependencies[ dependencyHandle ];
	// 		await lazyLoadScript( {
	// 			handle: dependencyHandle,
	// 			...dependency,
	// 		} );
	// 	}
	// };

	// document.body.addEventListener( 'wc-blocks_adding_to_cart', loadScripts );

	// Load scripts if a page is reloaded via the back button (potentially out of date cart data).
	// Based on refreshCachedCartData() in assets/js/base/context/cart-checkout/cart/index.js.
	// window.addEventListener(
	// 	'pageshow',
	// 	( event: PageTransitionEvent ): void => {
	// 		if ( event?.persisted || getNavigationType() === 'back_forward' ) {
	// 			loadScripts();
	// 		}
	// 	}
	// );

	miniCartBlocks.forEach( ( miniCartBlock, i ) => {
		// if ( ! ( miniCartBlock instanceof HTMLElement ) ) {
		// 	return;
		// }
		// const miniCartButton = miniCartBlock.querySelector(
		// 	'.wc-block-mini-cart__button'
		// );
		// console.log( 'miniCartButton', miniCartButton );
		// const miniCartDrawerPlaceholderOverlay = miniCartBlock.querySelector(
		// 	'.wc-block-components-drawer__screen-overlay'
		// );
		// if ( ! miniCartButton || ! miniCartDrawerPlaceholderOverlay ) {
		// 	// Markup is not correct, abort.
		// 	return;
		// }
		// const loadContents = () => {
		// 	if ( ! wasLoadScriptsCalled ) {
		// 		loadScripts();
		// 	}
		// 	document.body.removeEventListener(
		// 		'wc-blocks_added_to_cart',
		// 		// eslint-disable-next-line @typescript-eslint/no-use-before-define
		// 		funcOnAddToCart
		// 	);
		// 	document.body.removeEventListener(
		// 		'wc-blocks_removed_from_cart',
		// 		// eslint-disable-next-line @typescript-eslint/no-use-before-define
		// 		loadContentsWithRefresh
		// 	);
		// 	removeJQueryAddedToCartEvent();
		// 	removeJQueryRemovedFromCartEvent();
		// };
		// const openDrawer = () => {
		// 	miniCartBlock.dataset.isInitiallyOpen = 'true';
		// 	miniCartDrawerPlaceholderOverlay.classList.add(
		// 		'wc-block-components-drawer__screen-overlay--with-slide-in'
		// 	);
		// 	miniCartDrawerPlaceholderOverlay.classList.remove(
		// 		'wc-block-components-drawer__screen-overlay--is-hidden'
		// 	);
		// 	loadContents();
		// };
		// const openDrawerWithRefresh = () => {
		// 	openDrawer();
		// };
		// const loadContentsWithRefresh = () => {
		// 	miniCartBlock.dataset.isInitiallyOpen = 'false';
		// 	loadContents();
		// };
		// Load the scripts if a device is touch-enabled. We don't get the mouseover or focus events on touch devices,
		// so the event listeners below won't work.
		// if (
		// 	'ontouchstart' in window ||
		// 	navigator.maxTouchPoints > 0 ||
		// 	window.matchMedia( '(pointer:coarse)' ).matches
		// ) {
		// 	loadScripts();
		// } else {
		// 	// miniCartButton.addEventListener( 'mouseover', loadScripts );
		// 	// miniCartButton.addEventListener( 'focus', loadScripts );
		// }
		// miniCartButton.addEventListener( 'click', openDrawer );
		// const funcOnAddToCart =
		// 	miniCartBlock.dataset.addToCartBehaviour === 'open_drawer'
		// 		? openDrawerWithRefresh
		// 		: loadContentsWithRefresh;
		// There might be more than one Mini-Cart block in the page. Make sure
		// only one opens when adding a product to the cart.
		// if ( i === 0 ) {
		// 	document.body.addEventListener(
		// 		'wc-blocks_added_to_cart',
		// 		funcOnAddToCart
		// 	);
		// 	document.body.addEventListener(
		// 		'wc-blocks_removed_from_cart',
		// 		loadContentsWithRefresh
		// 	);
		// }
	} );
} );
