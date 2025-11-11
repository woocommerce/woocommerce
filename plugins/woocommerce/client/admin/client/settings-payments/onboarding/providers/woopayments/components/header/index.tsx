/**
 * External dependencies
 */
import { Button, Icon } from '@wordpress/components';
import { close } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { WC_ASSET_URL } from '~/utils/admin-settings';
import './style.scss';

interface WooPaymentsStepHeaderProps {
	onClose: () => void;
}

/**
 * WooPaymentsStepHeader component for WooPayments onboarding
 */
export default function WooPaymentsStepHeader( {
	onClose,
}: WooPaymentsStepHeaderProps ): React.ReactNode {
	return (
		<div className="settings-payments-onboarding-modal__header">
			<img
				src={ `${ WC_ASSET_URL }images/woo-logo.svg` }
				alt=""
				role="presentation"
				className="settings-payments-onboarding-modal__header--logo"
			/>
			<Button
				className="settings-payments-onboarding-modal__header--close"
				onClick={ onClose }
			>
				<Icon icon={ close } />
			</Button>
		</div>
	);
}
