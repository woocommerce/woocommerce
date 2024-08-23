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

		loadScripts: async () => {
			console.log( 'loadScripts' );

			await loadScripts();

			// Remove adding to cart event handler.
			document.body.removeEventListener(
				'wc-blocks_adding_to_cart',
				loadScripts
			);

			removeJQueryAddingToCartEvent();

			// Remove adding to cart event handler.
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

	console.log( 'preloaded: ', dependencies );

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
