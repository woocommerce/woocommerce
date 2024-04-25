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

/**
 * Internal dependencies
 */
import './style.scss';

export type ProductCollectionStoreContext = {
	isPrefetchNextOrPreviousLink: boolean;
	animation: 'start' | 'finish';
	accessibilityMessage: string;
	accessibilityLoadingMessage: string;
	accessibilityLoadedMessage: string;
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

const forcePageReload = ( href: string ) => {
	window.location.assign( href );
	// It's function called in generator expecting asyncFunc return.
	// eslint-disable-next-line @typescript-eslint/no-empty-function
	return new Promise( () => {} );
};

/**
 * Ensures the visibility of the first product in the collection.
 * Scrolls the page to the first product if it's not in the viewport.
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
	state: {
		get startAnimation() {
			return (
				getContext< ProductCollectionStoreContext >().animation ===
				'start'
			);
		},
		get finishAnimation() {
			return (
				getContext< ProductCollectionStoreContext >().animation ===
				'finish'
			);
		},
	},
	actions: {
		*navigate( event: MouseEvent ) {
			const ctx = getContext< ProductCollectionStoreContext >();
			const { ref } = getElement();
			const wcNavigationId = (
				ref?.closest( '[data-wc-navigation-id]' ) as HTMLDivElement
			 )?.dataset?.wcNavigationId;
			const isDisabled = (
				ref?.closest( '[data-wc-navigation-id]' ) as HTMLDivElement
			 )?.dataset.wcNavigationDisabled;

			if ( isDisabled ) {
				yield forcePageReload( ref.href );
			}

			if ( isValidLink( ref ) && isValidEvent( event ) ) {
				event.preventDefault();

				// Don't start animation if it doesn't take long to navigate.
				const timeout = setTimeout( () => {
					ctx.accessibilityMessage = ctx.accessibilityLoadingMessage;
					ctx.animation = 'start';
				}, 400 );

				yield navigate( ref.href );

				// Clear the timeout if the navigation is fast.
				clearTimeout( timeout );

				// Announce that the page has been loaded. If the message is the
				// same, we use a no-break space similar to the @wordpress/a11y
				// package: https://github.com/WordPress/gutenberg/blob/c395242b8e6ee20f8b06c199e4fc2920d7018af1/packages/a11y/src/filter-message.js#L20-L26
				ctx.accessibilityMessage =
					ctx.accessibilityLoadedMessage +
					( ctx.accessibilityMessage ===
					ctx.accessibilityLoadedMessage
						? '\u00A0'
						: '' );

				ctx.animation = 'finish';
				ctx.isPrefetchNextOrPreviousLink = !! ref.href;

				scrollToFirstProductIfNotVisible( wcNavigationId );
			}
		},
		/**
		 * We prefetch the next or previous button page on hover.
		 * Optimizes user experience by preloading content for faster access.
		 */
		*prefetchOnHover() {
			const { ref } = getElement();

			const isDisabled = (
				ref?.closest( '[data-wc-navigation-id]' ) as HTMLDivElement
			 )?.dataset.wcNavigationDisabled;

			if ( isDisabled ) {
				return;
			}

			if ( isValidLink( ref ) ) {
				yield prefetch( ref.href );
			}
		},
	},
	callbacks: {
		/**
		 * Prefetches content for next or previous links after initial user interaction.
		 * Reduces perceived load times for subsequent page navigations.
		 */
		*prefetch() {
			const { ref } = getElement();
			const isDisabled = (
				ref?.closest( '[data-wc-navigation-id]' ) as HTMLDivElement
			 )?.dataset.wcNavigationDisabled;

			if ( isDisabled ) {
				return;
			}

			const context = getContext< ProductCollectionStoreContext >();

			if ( context?.isPrefetchNextOrPreviousLink && isValidLink( ref ) ) {
				yield prefetch( ref.href );
			}
		},
	},
};

store( 'woocommerce/product-collection', productCollectionStore );
