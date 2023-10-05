/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MobileQRCode from '../illustrations/woo-mobile-download-qrcode.svg';

export const MobileAppInstallationInfo = () => {
	return (
		<div>
			<img
				src={ MobileQRCode }
				alt={ __( 'Download WooCommerce mobile app', 'woocommerce' ) }
				width="150"
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
