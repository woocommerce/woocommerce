/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { TrackedLink } from '~/components/tracked-link/tracked-link';

type ShippingRecommendationsMarketplaceLinkProps = {
	textProps?: {
		as?: string;
		className?: string;
	};
	eventName?: string;
};

export const ShippingRecommendationsMarketplaceLink = ( {
	textProps,
	eventName = 'settings_shipping_recommendation_visit_marketplace_click',
}: ShippingRecommendationsMarketplaceLinkProps ) => (
	<TrackedLink
		textProps={ textProps }
		message={ __(
			// translators: {{Link}} is a placeholder for a html element.
			'Visit {{Link}}the WooCommerce Marketplace{{/Link}} to find more shipping, delivery, and fulfillment solutions.',
			'woocommerce'
		) }
		targetUrl={ getAdminLink(
			'admin.php?page=wc-admin&tab=extensions&path=/extensions&category=shipping-delivery-and-fulfillment'
		) }
		linkType="wc-admin"
		eventName={ eventName }
	/>
);
