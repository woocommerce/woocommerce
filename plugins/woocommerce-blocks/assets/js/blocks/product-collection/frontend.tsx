/**
 * External dependencies
 */
import {
	store,
	navigate,
	prefetch,
	getElement,
	getContext,
} from '@woocommerce/interactivity';

export type ProductCollectionStoreContext = {
	message: string;
	url: string;
	// Accessibility texts.
	loadingText: string;
	loadedText: string;
};

const isValidLink = ( ref: HTMLAnchorElement ) =>
	ref &&
	ref instanceof window.HTMLAnchorElement &&
	ref.href &&
	( ! ref.target || ref.target === '_self' ) &&
	ref.origin === window.location.origin;

const isValidEvent = ( event: MouseEvent ) =>
	event.button === 0 && // Left clicks only.
	! event.metaKey && // Open in new tab (Mac).
	! event.ctrlKey && // Open in new tab (Windows).
	! event.altKey && // Download.
	! event.shiftKey &&
	! event.defaultPrevented;

/**
 * Scrolls to the first product in Product Collection if it's not visible.
 *
 * @param ref - The reference to the element.
 */
function scrollToFirstProductIfNotVisible( ref: HTMLAnchorElement ) {
	// data-wc-navigation-id is added to each Product Collection block
	const id = ( ref?.closest( '[data-wc-navigation-id]' ) as HTMLDivElement )
		?.dataset?.wcNavigationId;

	const productSelector = `[data-wc-navigation-id=${ id }] .wc-block-product-template .wc-block-product`;
	const product = document.querySelector( productSelector );
	if ( product ) {
		const rect = product.getBoundingClientRect();
		const isVisible =
			rect.top >= 0 &&
			rect.left >= 0 &&
			rect.bottom <=
				( window.innerHeight ||
					document.documentElement.clientHeight ) &&
			rect.right <=
				( window.innerWidth || document.documentElement.clientWidth );

		// If the product is not visible, scroll to it.
		if ( ! isVisible ) {
			product.scrollIntoView( {
				behavior: 'smooth',
				block: 'start',
			} );
		}
	}
}

const productCollectionStore = {
	actions: {
		*navigate( event: MouseEvent ) {
			const ctx = getContext< ProductCollectionStoreContext >();
			const { ref } = getElement();

			if ( isValidLink( ref ) && isValidEvent( event ) ) {
				event.preventDefault();

				// Don't announce the navigation immediately, wait 400 ms.
				const timeout = setTimeout( () => {
					ctx.message = ctx.loadingText;
				}, 400 );

				yield navigate( ref.href );

				// Dismiss loading message if it hasn't been added yet.
				clearTimeout( timeout );

				// Announce that the page has been loaded. If the message is the
				// same, we use a no-break space similar to the @wordpress/a11y
				// package: https://github.com/WordPress/gutenberg/blob/c395242b8e6ee20f8b06c199e4fc2920d7018af1/packages/a11y/src/filter-message.js#L20-L26
				ctx.message =
					ctx.loadedText +
					( ctx.message === ctx.loadedText ? '\u00A0' : '' );

				ctx.url = ref.href;

				scrollToFirstProductIfNotVisible( ref );
			}
		},
		*prefetch() {
			const { ref } = getElement();
			if ( isValidLink( ref ) ) {
				yield prefetch( ref.href );
			}
		},
	},
	callbacks: {
		*prefetch() {
			const { url } = getContext< ProductCollectionStoreContext >();
			const { ref } = getElement();
			if ( url && isValidLink( ref ) ) {
				yield prefetch( ref.href );
			}
		},
	},
};

store( 'woocommerce/product-collection', productCollectionStore );
