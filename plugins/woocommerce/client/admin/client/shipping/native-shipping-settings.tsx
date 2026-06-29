/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { TrackedLink } from '~/components/tracked-link/tracked-link';
import { ShippingNativeInlineSetup } from '../task-lists/fills/experimental-shipping-recommendation/native-inline-setup';
import './native-shipping-settings.scss';

const NativeShippingSettings = () => {
	useEffect( () => {
		document.body.classList.add(
			'woocommerce-shipping-native-settings-page'
		);

		return () => {
			document.body.classList.remove(
				'woocommerce-shipping-native-settings-page'
			);
		};
	}, [] );

	return (
		<div className="woocommerce-shipping-native-settings">
			<ShippingNativeInlineSetup />
			<TrackedLink
				textProps={ {
					as: 'div',
					className:
						'woocommerce-task-dashboard__container woocommerce-task-marketplace-link',
				} }
				message={ __(
					// translators: {{Link}} is a placeholder for a html element.
					'Visit {{Link}}the WooCommerce Marketplace{{/Link}} to find more shipping, delivery, and fulfillment solutions.',
					'woocommerce'
				) }
				eventName="settings_shipping_recommendation_visit_marketplace_click"
				targetUrl={ getAdminLink(
					'admin.php?page=wc-admin&tab=extensions&path=/extensions&category=shipping-delivery-and-fulfillment'
				) }
				linkType="wc-admin"
			/>
		</div>
	);
};

export default NativeShippingSettings;
