/**
 * External dependencies
 */
import type React from 'react';

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
					alt="Screen captures of the WooCommerce mobile app"
				/>
			</div>
		</div>
	);
};
