/**
 * Mounts a contextual Marketplace promo card on the WooCommerce Orders list.
 *
 * The Orders list is a classic (non-SPA) admin page, so the card is mounted into a
 * server-rendered container (printed by WC_Admin_Marketplace_Promotions) rather than
 * the WooCommerce Admin app. The promotion is already rule-resolved server-side; this
 * script only renders it and lets PromoCard handle impression/click/dismiss Tracks.
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

const root = document.getElementById( 'woocommerce-marketplace-orders-promo' );

if ( root && root.dataset.promotion ) {
	try {
		const data = JSON.parse( root.dataset.promotion ) as {
			promotion: Promotion;
			order_count?: number;
		};

		if ( data && data.promotion ) {
			const eventProperties: Record< string, unknown > = {
				surface: 'orders',
			};

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
	} catch ( e ) {
		// Malformed payload — render nothing rather than break the Orders screen.
	}
}
