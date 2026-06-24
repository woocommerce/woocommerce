/**
 * Inserts a contextual Marketplace promo card on the WooCommerce Orders list.
 *
 * The Orders list is a classic (non-SPA) admin page. The orders list table is wrapped in a
 * form, so the card is inserted as a full-width banner immediately before that form — above the
 * filters and table. The promotion is rule-resolved server-side and localized as
 * `window.wcOrdersPromo`; PromoCard handles impression/click/dismiss Tracks.
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
			promotion: Promotion;
			order_count?: number;
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

	const eventProperties: Record< string, unknown > = { surface: 'orders' };
	if ( typeof data.order_count === 'number' ) {
		eventProperties.order_count = data.order_count;
	}

	createRoot( root ).render(
		<PromoCard
			promotion={ data.promotion }
			eventProperties={ eventProperties }
		/>
	);
}
