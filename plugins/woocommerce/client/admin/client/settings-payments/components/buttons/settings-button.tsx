/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import {
	OfflinePaymentMethodProvider,
	PaymentGatewayProvider,
	paymentGatewaysStore,
} from '@woocommerce/data';
import { useNavigate } from 'react-router-dom';
import { useDispatch } from '@wordpress/data';
import { getQueryArg } from '@wordpress/url';
import { MouseEvent } from 'react';

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
	// Determine if the settingsHref is for a Reactified page.
	// A Reactified page will have a 'path' query parameter.
	const isReactifiedPage = !! getQueryArg( settingsHref, 'path' );
	const navigate = useNavigate();
	const { invalidateResolutionForStoreSelector } =
		useDispatch( paymentGatewaysStore );
	const recordButtonClickEvent = () => {
		recordPaymentsProviderEvent( 'provider_manage_click', gatewayProvider );
	};

	return (
		<Button
			variant={ 'secondary' }
			disabled={ isInstallingPlugin }
			onClick={ ( event: MouseEvent ) => {
				recordButtonClickEvent();

				// Allow modified clicks (new tab/window, etc.) to proceed with default behavior.
				const isModifiedClick =
					event.metaKey ||
					event.ctrlKey ||
					event.shiftKey ||
					event.altKey ||
					event.button === 1;

				// If it's a modified click, open in new tab/window.
				if ( isModifiedClick ) {
					window.open( settingsHref, '_blank' );
					return;
				}

				// If it's a Reactified page, we invalidate the resolution for the store selector
				// to ensure the latest data is fetched when navigating.
				// Then we navigate to the settings URL.
				// This is necessary to ensure that the page updates correctly with the latest data.
				// If it's not a Reactified page, we just navigate to the settingsHref directly.
				if ( isReactifiedPage ) {
					invalidateResolutionForStoreSelector( 'getPaymentGateway' );
					navigate( removeOriginFromURL( settingsHref ) );
				} else {
					window.location.href = settingsHref;
				}
			} }
		>
			{ buttonText }
		</Button>
	);
};
