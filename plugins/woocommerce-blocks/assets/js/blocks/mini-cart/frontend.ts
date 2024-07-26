/**
 * External dependencies
 */
import preloadScript from '@woocommerce/base-utils/preload-script';
import lazyLoadScript from '@woocommerce/base-utils/lazy-load-script';
import getNavigationType from '@woocommerce/base-utils/get-navigation-type';
import { translateJQueryEventToNative } from '@woocommerce/base-utils/legacy-events';

/**
 * Internal dependencies
 */
import {
	getMiniCartTotalsFromLocalStorage,
	getMiniCartTotalsFromServer,
	updateTotals,
} from './utils/data';
import setStyles from './utils/set-styles';

interface dependencyData {
	src: string;
	version?: string;
	after?: string;
	before?: string;
	translations?: string;
}

updateTotals( getMiniCartTotalsFromLocalStorage() );
getMiniCartTotalsFromServer().then( updateTotals );
setStyles();

declare global {
	interface Window {
		wcBlocksMiniCartFrontendDependencies: Record< string, dependencyData >;
	}
}

window.addEventListener( 'load', () => {
	const miniCartBlocks = document.querySelectorAll( '.wc-block-mini-cart' );
	let wasLoadScriptsCalled = false;

	if ( miniCartBlocks.length === 0 ) {
		return;
	}

	const dependencies = window.wcBlocksMiniCartFrontendDependencies;

	// Preload scripts
	for ( const dependencyHandle in dependencies ) {
		const dependency = dependencies[ dependencyHandle ];
		preloadScript( {
			handle: dependencyHandle,
			...dependency,
		} );
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
		// Ensure we only call loadScripts once.
		if ( wasLoadScriptsCalled ) {
			return;
		}
		wasLoadScriptsCalled = true;

		// Remove adding to cart event handler.
		document.body.removeEventListener(
			'wc-blocks_adding_to_cart',
			loadScripts
		);
		removeJQueryAddingToCartEvent();

		// Lazy load scripts.
		for ( const dependencyHandle in dependencies ) {
			const dependency = dependencies[ dependencyHandle ];
			await lazyLoadScript( {
				handle: dependencyHandle,
				...dependency,
			} );
		}
	};

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

	miniCartBlocks.forEach( ( miniCartBlock, i ) => {
		if ( ! ( miniCartBlock instanceof HTMLElement ) ) {
			return;
		}

		const miniCartButton = miniCartBlock.querySelector(
			'.wc-block-mini-cart__button'
		);
		const miniCartDrawerPlaceholderOverlay = miniCartBlock.querySelector(
			'.wc-block-components-drawer__screen-overlay'
		);

		if ( ! miniCartButton || ! miniCartDrawerPlaceholderOverlay ) {
			// Markup is not correct, abort.
			return;
		}

		const loadContents = () => {
			if ( ! wasLoadScriptsCalled ) {
				loadScripts();
			}
			document.body.removeEventListener(
				'wc-blocks_added_to_cart',
				// eslint-disable-next-line @typescript-eslint/no-use-before-define
				funcOnAddToCart
			);
			document.body.removeEventListener(
				'wc-blocks_removed_from_cart',
				// eslint-disable-next-line @typescript-eslint/no-use-before-define
				loadContentsWithRefresh
			);
			removeJQueryAddedToCartEvent();
			removeJQueryRemovedFromCartEvent();
		};

		const openDrawer = () => {
			miniCartBlock.dataset.isInitiallyOpen = 'true';

			miniCartDrawerPlaceholderOverlay.classList.add(
				'wc-block-components-drawer__screen-overlay--with-slide-in'
			);
			miniCartDrawerPlaceholderOverlay.classList.remove(
				'wc-block-components-drawer__screen-overlay--is-hidden'
			);

			loadContents();
		};

		const openDrawerWithRefresh = () => {
			openDrawer();
		};

		const loadContentsWithRefresh = () => {
			miniCartBlock.dataset.isInitiallyOpen = 'false';
			loadContents();
		};

		// Load the scripts if a device is touch-enabled. We don't get the mouseover or focus events on touch devices,
		// so the event listeners below won't work.
		if (
			'ontouchstart' in window ||
			navigator.maxTouchPoints > 0 ||
			window.matchMedia( '(pointer:coarse)' ).matches
		) {
			loadScripts();
		} else {
			miniCartButton.addEventListener( 'mouseover', loadScripts );
			miniCartButton.addEventListener( 'focus', loadScripts );
		}

		miniCartButton.addEventListener( 'click', openDrawer );

		const funcOnAddToCart =
			miniCartBlock.dataset.addToCartBehaviour === 'open_drawer'
				? openDrawerWithRefresh
				: loadContentsWithRefresh;

		// There might be more than one Mini-Cart block in the page. Make sure
		// only one opens when adding a product to the cart.
		if ( i === 0 ) {
			document.body.addEventListener(
				'wc-blocks_added_to_cart',
				funcOnAddToCart
			);
			document.body.addEventListener(
				'wc-blocks_removed_from_cart',
				loadContentsWithRefresh
			);
		}
	} );
} );
