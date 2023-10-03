/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MobileQRCode from '../illustrations/woo-mobile-download-qrcode.svg';
import { ModalContentLayoutWithTitle } from '../layouts/ModalContentLayoutWithTitle';

export const InstallMobileAppPage = () => {
	return (
		<ModalContentLayoutWithTitle>
			<div>
				<img
					src={ MobileQRCode }
					alt={ __(
						'Download WooCommerce mobile app',
						'woocommerce'
					) }
					width="100"
				/>
				<p>
					{ __(
						'Scan the code above to download the WooCommerce mobile app, or visit woocommerce.com/mobile from your mobile device.',
						'woocommerce'
					) }
				</p>
			</div>
		</ModalContentLayoutWithTitle>
	);
};
