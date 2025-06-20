/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { recordPaymentsEvent } from '~/settings-payments/utils';

interface SettingsButtonProps {
	/**
	 * ID of the associated gateway.
	 */
	gatewayId: string;

	/**
	 * The settings URL to navigate to when the enable gateway button is clicked.
	 */
	settingsHref: string;
	/**
	 * The text of the button.
	 */
	buttonText?: string;
	/**
	 * Whether the associated provider plugin is being installed.
	 */
	isInstallingPlugin?: boolean;
}

/**
 * A simple button component that navigates to the provided settings URL when clicked.
 * Used for managing settings for a payment gateway.
 */
export const SettingsButton = ( {
	gatewayId,
	settingsHref,
	isInstallingPlugin,
	buttonText = __( 'Manage', 'woocommerce' ),
}: SettingsButtonProps ) => {
	const recordButtonClickEvent = () => {
		recordPaymentsEvent( 'provider_manage_click', {
			provider_id: gatewayId,
		} );
	};

	return (
		<Button
			variant={ 'secondary' }
			href={ settingsHref }
			disabled={ isInstallingPlugin }
			onClick={ recordButtonClickEvent }
		>
			{ buttonText }
		</Button>
	);
};
