/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import preloadScript from '@woocommerce/base-utils/preload-script';
import lazyLoadScript from '@woocommerce/base-utils/lazy-load-script';
import { translateJQueryEventToNative } from '@woocommerce/base-utils/legacy-events';

interface dependencyData {
	src: string;
	version?: string;
	after?: string;
	before?: string;
	translations?: string;
}

// eslint-disable-next-line @wordpress/no-global-event-listener
window.onload = () => {
	const miniCartBlocks = document.querySelectorAll( '.wc-block-mini-cart' );
	let wasLoadScriptsCalled = false;

	if ( miniCartBlocks.length === 0 ) {
		return;
	}

	const dependencies = getSetting(
		'mini_cart_block_frontend_dependencies',
		{}
	) as Record< string, dependencyData >;

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

		const showContents = () => {
			if ( ! wasLoadScriptsCalled ) {
				loadScripts();
			}
			document.body.removeEventListener(
				'wc-blocks_added_to_cart',
				// eslint-disable-next-line @typescript-eslint/no-use-before-define
				showContentsAndUpdate
			);
			miniCartBlock.dataset.isPlaceholderOpen = 'true';
			miniCartDrawerPlaceholderOverlay.classList.add(
				'wc-block-components-drawer__screen-overlay--with-slide-in'
			);
			miniCartDrawerPlaceholderOverlay.classList.remove(
				'wc-block-components-drawer__screen-overlay--is-hidden'
			);
			removeJQueryAddedToCartEvent();
		};

		const showContentsAndUpdate = () => {
			miniCartBlock.dataset.isDataOutdated = 'true';
			showContents();
		};

		miniCartButton.addEventListener( 'mouseover', loadScripts );
		miniCartButton.addEventListener( 'focus', loadScripts );
		miniCartButton.addEventListener( 'click', showContents );

		// There might be more than one Mini Cart block in the page. Make sure
		// only one opens when adding a product to the cart.
		if ( i === 0 ) {
			document.body.addEventListener(
				'wc-blocks_added_to_cart',
				showContentsAndUpdate
			);
		}
	} );
};
