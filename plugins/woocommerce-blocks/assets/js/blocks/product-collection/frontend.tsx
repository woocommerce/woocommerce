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
	isPrefetchNextOrPreviousLink: boolean;
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
 * @param {string} wcNavigationId Unique ID for each Product Collection block on page/post.
 */
function scrollToFirstProductIfNotVisible( wcNavigationId?: string ) {
	if ( ! wcNavigationId ) {
		return;
	}

	const productSelector = `[data-wc-navigation-id=${ wcNavigationId }] .wc-block-product-template .wc-block-product`;
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
			const wcNavigationId = (
				ref?.closest( '[data-wc-navigation-id]' ) as HTMLDivElement
			 )?.dataset?.wcNavigationId;

			if ( isValidLink( ref ) && isValidEvent( event ) ) {
				event.preventDefault();
				yield navigate( ref.href );

				ctx.isPrefetchNextOrPreviousLink = !! ref.href;

				scrollToFirstProductIfNotVisible( wcNavigationId );
			}
		},
		/**
		 * We prefetch the next or previous button page on hover.
		 */
		*prefetchOnHover() {
			const { ref } = getElement();
			if ( isValidLink( ref ) ) {
				yield prefetch( ref.href );
			}
		},
	},
	callbacks: {
		/**
		 * - We don't prefetch the next or previous button link on initial page load.
		 * - If the user has clicked a pagination link, then user is likely to keep paginating. Therefore,
		 *   once the user has clicked a pagination link, we start prefetching the next or previous button page.
		 */
		*prefetch() {
			const context = getContext< ProductCollectionStoreContext >();
			const { ref } = getElement();
			if ( context?.isPrefetchNextOrPreviousLink && isValidLink( ref ) ) {
				yield prefetch( ref.href );
			}
		},
	},
};

store( 'woocommerce/product-collection', productCollectionStore );
