/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import WooPaymentsOnboarding from '~/settings-payments/onboarding/providers/woopayments/components/onboarding';
import './payments-content.scss';

export const PaymentsContent = ( {} ) => {
	return (
		<div className="launch-your-store-payments-content">
			<div className="launch-your-store-payments-content__canvas">
				<WooPaymentsOnboarding includeSidebar={ false } />
			</div>
		</div>
	);
};
