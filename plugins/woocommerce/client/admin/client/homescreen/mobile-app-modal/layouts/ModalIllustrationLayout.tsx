/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Illustration from '../illustrations/intro-devices-desktop.png';

export const ModalIllustrationLayout = ( {
	body,
}: {
	body: React.ReactNode;
} ) => {
	return (
		<div className="mobile-app-modal-layout">
			<div className="mobile-app-modal-content">{ body }</div>
			<div className="mobile-app-modal-illustration">
				<img
					src={ Illustration }
					alt={ __(
						'Screen captures of the WooCommerce mobile app',
						'woocommerce'
					) }
				/>
			</div>
		</div>
	);
};
