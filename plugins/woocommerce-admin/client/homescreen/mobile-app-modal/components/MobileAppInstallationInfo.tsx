/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { QRCodeSVG } from 'qrcode.react';

export const MobileAppInstallationInfo = () => {
	return (
		<div>
			<QRCodeSVG
				value={
					'https://woocommerce.com/mobile/?utm_source=wc_onboarding_mobile_task'
				}
				size={ 140 }
			/>
			<p>
				{ __(
					'Scan the code above to download the WooCommerce mobile app, or visit woocommerce.com/mobile from your mobile device.',
					'woocommerce'
				) }
			</p>
		</div>
	);
};
