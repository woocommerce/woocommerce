/**
 * Store part for cart-referencing Product Collections (e.g., cross-sells).
 *
 * This module extends the main product-collection store with the setup
 * that keeps cart-referencing collections fresh: it watches the cart, and
 * when the contents change it prefetches the current page with a
 * cache-busting query value, then swaps the collection in (via the router)
 * while the Mini-Cart drawer is open.
 *
 * The whole thing is here and scoped to only the consumer module, so we can
 * enqueue this only when a cart-referencing Product Collection is rendered,
 * and the Mini-Cart doesn't need to know any of this.
 */

/**
 * External dependencies
 */
import { store, getConfig } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/cart';
import type { Store as WooCommerce } from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import type { MiniCart } from '../mini-cart/frontend';

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { state: woocommerceState, actions: woocommerceActions } =
	store< WooCommerce >( 'woocommerce', {}, { lock: universalLock } );

const { state: miniCartState } = store< MiniCart >(
	'woocommerce/mini-cart',
	{},
	{ lock: universalLock }
);

/**
 * Signature of the last cart contents a prefetch ran for. Comparing item
 * ids/quantities (instead of the items array identity) collapses the several
 * reference changes a single mutation produces (optimistic write, server
 * commit, reconciliation) into one prefetch per settled state. The keys are
 * sorted so a mere reordering of the same contents never reads as a change.
 */
let lastCartSignature: string | undefined;

/**
 * Cache-busted URL of the freshest prefetched page HTML, waiting to be
 * navigated to while the drawer is open.
 */
let pendingRefreshUrl: string | undefined;

/**
 * URL of the last completed refresh navigation. Deduplicating on the URL
 * avoids repeat navigations when the drawer is reopened with an unchanged
 * cart, and covers either ordering of "drawer opened" vs. "prefetch settled".
 */
let lastNavigatedUrl: string | undefined;

const getCartSignature = (
	items: WooCommerce[ 'state' ][ 'cart' ][ 'items' ] | undefined
): string =>
	( items ?? [] )
		.map( ( item ) => `${ item.id }x${ item.quantity }` )
		.sort()
		.join( '|' );

/**
 * Store part with cart-referencing callbacks.
 */
const cartReferenceStorePart = {
	callbacks: {
		/**
		 * Keeps cart-referencing Product Collections (e.g., cross-sells)
		 * fresh. Collections are SSR'd at page load, so they don't update by
		 * themselves when cart items change.
		 *
		 * This watch reads both the cart items and the drawer state, so it
		 * re-runs on either kind of change: a cart change prefetches fresh
		 * HTML under a unique URL, and an open drawer (whether it opened
		 * before or after the prefetch settled) swaps it in.
		 */
		*refreshCartReference() {
			// When client-side navigation is disabled (an unsupported inner
			// block was detected), navigate() falls back to a full-page load.
			// Decline to refresh instead: stale cross-sells until the next
			// real page load beat a surprise reload of the whole page.
			if ( getConfig( 'core/router' )?.clientNavigationDisabled ) {
				return;
			}

			const signature = getCartSignature( woocommerceState.cart?.items );

			if ( lastCartSignature === undefined ) {
				// First run only records the SSR baseline.
				lastCartSignature = signature;
			} else if ( signature !== lastCartSignature ) {
				// Wait until in-flight cart mutations settle: prefetching
				// while a Store API request is being processed would cache
				// HTML rendered from a not-yet-updated cart.
				yield woocommerceActions.waitForIdle();

				// Re-check against the settled state and record it before
				// the prefetch, so the extra watch runs a single mutation
				// causes dedupe to one prefetch per settled cart state.
				const settledSignature = getCartSignature(
					woocommerceState.cart?.items
				);
				if ( settledSignature !== lastCartSignature ) {
					lastCartSignature = settledSignature;

					// A unique query value bypasses server/edge caches (and
					// the WP 6.9 router style cache) so the prefetched HTML
					// always reflects the current cart. No `force`: the URL
					// is unique, so there is never a stale entry to bypass,
					// and a concurrent navigation to it shares one request.
					const url = new URL( window.location.href );
					url.searchParams.set(
						'wc-cache-bust',
						Math.random().toString( 36 ).slice( 2 )
					);
					pendingRefreshUrl = url.href;

					const { actions: routerActions } = yield import(
						'@wordpress/interactivity-router'
					);
					yield routerActions.prefetch( pendingRefreshUrl );
				}
			}

			const url = pendingRefreshUrl;

			if ( ! miniCartState.isOpen || ! url || url === lastNavigatedUrl ) {
				return;
			}
			// Claim the URL before the first await so a re-run of this
			// callback during the navigation cannot start a second one.
			lastNavigatedUrl = url;

			// The URL carries a cache-busting query value; remember the
			// canonical address to restore after the router swap.
			const restoreUrl = window.location.href;

			try {
				const {
					actions: routerActions,
				}: typeof import('@wordpress/interactivity-router') =
					yield import( '@wordpress/interactivity-router' );
				// This is a background region refresh, not a user-initiated
				// page navigation: suppress the loading animation and the
				// screen-reader page-load announcement.
				yield routerActions.navigate( url, {
					replace: true,
					loadingAnimation: false,
					screenReaderAnnouncement: false,
				} );
			} catch {
				// Release the claim so reopening the drawer can retry.
				lastNavigatedUrl = undefined;
				return;
			}

			// Skip the URL restore if a newer refresh superseded this one
			// while the navigation was in flight; its own cycle restores.
			if ( pendingRefreshUrl !== url ) {
				return;
			}

			// Restore the canonical URL, preserving whatever history state
			// the router recorded for this entry.
			window.history.replaceState( window.history.state, '', restoreUrl );
		},
	},
};

/**
 * Extend the product-collection store with cart-referencing callbacks.
 */
store( 'woocommerce/product-collection', cartReferenceStorePart, {
	lock: universalLock,
} );
