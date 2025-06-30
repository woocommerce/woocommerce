/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import {
	OfflinePaymentMethodProvider,
	PaymentGatewayProvider,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { recordPaymentsProviderEvent } from '~/settings-payments/utils';

interface SettingsButtonProps {
	/**
	 * The details of the payment gateway to enable.
	 */
	gatewayProvider: PaymentGatewayProvider | OfflinePaymentMethodProvider;

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
	gatewayProvider,
	settingsHref,
	isInstallingPlugin,
	buttonText = __( 'Manage', 'woocommerce' ),
}: SettingsButtonProps ) => {
	const recordButtonClickEvent = () => {
		recordPaymentsProviderEvent( 'provider_manage_click', gatewayProvider );
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
