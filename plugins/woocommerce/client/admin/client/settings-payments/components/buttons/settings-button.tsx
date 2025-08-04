/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import {
	OfflinePaymentMethodProvider,
	PaymentGatewayProvider,
	paymentGatewaysStore,
	PaymentsProviderType,
} from '@woocommerce/data';
import { useNavigate } from 'react-router-dom';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	recordPaymentsProviderEvent,
	removeOriginFromURL,
} from '~/settings-payments/utils';

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
	const isOffline = gatewayProvider._type === PaymentsProviderType.OfflinePm;
	const navigate = useNavigate();
	const { invalidateResolutionForStoreSelector } =
		useDispatch( paymentGatewaysStore );
	const recordButtonClickEvent = () => {
		recordPaymentsProviderEvent( 'provider_manage_click', gatewayProvider );
	};

	return (
		<Button
			variant={ 'secondary' }
			href={ ! isOffline ? settingsHref : undefined }
			disabled={ isInstallingPlugin }
			onClick={ () => {
				recordButtonClickEvent();
				if ( isOffline ) {
					invalidateResolutionForStoreSelector( 'getPaymentGateway' );
					navigate( removeOriginFromURL( settingsHref ) );
				}
			} }
		>
			{ buttonText }
		</Button>
	);
};
