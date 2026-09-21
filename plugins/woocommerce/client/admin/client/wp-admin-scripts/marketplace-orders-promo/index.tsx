/**
 * Inserts a contextual Marketplace promo card on the WooCommerce Orders list.
 *
 * The Orders list is a classic (non-SPA) admin page. The orders list table is wrapped in a
 * form, so the card is inserted as a full-width banner immediately before that form — above the
 * filters and table. The promotion is rule-resolved server-side and localized as
 * `window.wcOrdersPromo`; PromoCard handles impression/click/dismiss Tracks. Dismissal is
 * persisted server-side (per user, by promo id) so the card stays hidden across URLs and devices.
 */

/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';
import PromoCard from '~/marketplace/components/promo-card/promo-card';
import { Promotion } from '~/marketplace/components/promotions/types';

declare global {
	interface Window {
		wcOrdersPromo?: {
			id?: string;
			promotion: Promotion;
			order_count?: number;
			dismiss_url?: string;
			dismiss_nonce?: string;
		};
	}
}

const data = window.wcOrdersPromo;

// Insert the banner at the top of the list content, above the status filter links
// (`.subsubsub`) and below any admin notices. Fall back to the list form
// (`#wc-orders-filter` on HPOS, `#posts-filter` on the legacy list) if the status links
// are not rendered (e.g. a single status view).
const anchor =
	document.querySelector( '.subsubsub' ) ||
	document.getElementById( 'wc-orders-filter' ) ||
	document.getElementById( 'posts-filter' );

if ( data && data.promotion && anchor && anchor.parentNode ) {
	const root = document.createElement( 'div' );
	root.className = 'woocommerce-marketplace-orders-promo';
	anchor.parentNode.insertBefore( root, anchor );

	const promoId = data.id;

	const eventProperties: Record< string, unknown > = { surface: 'orders' };
	if ( typeof data.order_count === 'number' ) {
		eventProperties.order_count = data.order_count;
	}

	const dismissUrl = data.dismiss_url;
	const dismissNonce = data.dismiss_nonce;

	// Persist the dismissal with a plain fetch to the localized REST URL + nonce. This avoids
	// depending on apiFetch's root/nonce middleware, which is not configured on this classic
	// (non-SPA) admin page. The card is already hidden client-side; a failed POST only means it
	// may reappear on the next load.
	const onDismiss =
		promoId && dismissUrl && dismissNonce
			? () => {
					fetch( dismissUrl, {
						method: 'POST',
						credentials: 'same-origin',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': dismissNonce,
						},
						body: JSON.stringify( { id: promoId } ),
					} ).catch( () => {} );
			  }
			: undefined;

	createRoot( root ).render(
		<PromoCard
			promotion={ data.promotion }
			eventProperties={ eventProperties }
			onDismiss={ onDismiss }
		/>
	);
}
