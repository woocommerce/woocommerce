/**
 * External dependencies
 */
import { useEffect } from 'react';

/**
 * Internal dependencies
 */
import WooPaymentsOnboarding from '~/settings-payments/onboarding/providers/woopayments/components/onboarding';
import './payments-content.scss';

export const PaymentsContent = ( {} ) => {
	// Clean up any existing history when this component mounts
	useEffect( () => {
		return () => {
			// When component unmounts, replace any history entries with the original URL
			// This ensures we don't have leftover history entries from the payment flow
			const cleanUrl =
				'/wp-admin/admin.php?page=wc-admin&path=%2Flaunch-your-store';
			window.history.replaceState( null, '', cleanUrl );
		};
	}, [] );

	return (
		<div className="launch-your-store-payments-content">
			<div className="launch-your-store-payments-content__canvas">
				<WooPaymentsOnboarding includeSidebar={ false } />
			</div>
		</div>
	);
};
