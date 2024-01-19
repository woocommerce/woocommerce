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
	// TODO Add animation CSS
	animation: 'start' | 'finish' | undefined;
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

			const isDisabled = ref.closest( '[data-wc-navigation-id]' )?.dataset
				.wcNavigationDisabled;

			if ( isValidLink( ref ) && isValidEvent( event ) && ! isDisabled ) {
				event.preventDefault();

				const id = ref.closest( '[data-wc-navigation-id]' ).dataset
					.wcNavigationId;

				// Don't announce the navigation immediately, wait 400 ms.
				const timeout = setTimeout( () => {
					ctx.message = ctx.loadingText;
					ctx.animation = 'start';
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

				ctx.animation = 'finish';
				ctx.url = ref.href;

				/**
				 * Focus the first anchor or button block of product collection block.
				 * This way:
				 * 1. We maintain accessibility.
				 * 2. Scroll to the focused element.
				 */
				const selectorPrefix = `[data-wc-navigation-id=${ id }] .wc-block-product-template`;
				const firstAnchorOrButton = `${ selectorPrefix } a[href], ${ selectorPrefix } button`;
				document.querySelector( firstAnchorOrButton )?.focus();
			}
		},
		*prefetch() {
			const { ref } = getElement();
			const isDisabled = ref.closest( '[data-wc-navigation-id]' )?.dataset
				.wcNavigationDisabled;
			if ( isValidLink( ref ) && ! isDisabled ) {
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
