/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { dispatch, useDispatch } from '@wordpress/data';
import {
	EnableGatewayResponse,
	paymentSettingsStore,
	PaymentIncentive,
	PaymentProviderState,
} from '@woocommerce/data';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

interface EnableGatewayButtonProps {
	/**
	 * The ID of the gateway to enable.
	 */
	gatewayId: string;
	/**
	 * The state of the gateway.
	 */
	gatewayState: PaymentProviderState;
	/**
	 * The settings URL to navigate to when the enable gateway button is clicked.
	 */
	settingsHref: string;
	/**
	 * The onboarding URL to navigate to when the gateway needs setup.
	 */
	onboardingHref: string;
	/**
	 * Whether this is an offline payment gateway.
	 */
	isOffline: boolean;
	/**
	 * Callback used when an incentive is accepted.
	 *
	 * @param id Incentive ID.
	 */
	acceptIncentive?: ( id: string ) => void;
	/**
	 * Whether the gateway has a list of recommended payment methods to use during the native onboarding flow.
	 */
	gatewayHasRecommendedPaymentMethods: boolean;
	/**
	 * ID of the plugin that is being installed.
	 */
	installingPlugin?: string | null;
	/**
	 * The text of the button.
	 */
	buttonText?: string;
	/**
	 * Incentive data. If provided, the incentive will be accepted when the button is clicked.
	 */
	incentive?: PaymentIncentive | null;
}

/**
 * A button component that allows users to enable a payment gateway.
 * Depending on the gateway's state, it redirects to settings, onboarding, or recommended payment methods pages.
 * If incentive data is provided, it will trigger the `acceptIncentive` callback with the incentive ID.
 */
export const EnableGatewayButton = ( {
	gatewayId,
	gatewayState,
	settingsHref,
	onboardingHref,
	isOffline,
	acceptIncentive = () => {},
	gatewayHasRecommendedPaymentMethods,
	installingPlugin,
	buttonText = __( 'Enable', 'woocommerce' ),
	incentive = null,
}: EnableGatewayButtonProps ) => {
	const [ isUpdating, setIsUpdating ] = useState( false );
	const { createErrorNotice } = dispatch( 'core/notices' );
	const { togglePaymentGateway, invalidateResolutionForStoreSelector } =
		useDispatch( paymentSettingsStore );

	const throwError = () => {
		createErrorNotice(
			__(
				'An error occurred. You will be redirected to the settings page, try enabling the payment gateway there.',
				'woocommerce'
			),
			{
				type: 'snackbar',
				explicitDismiss: true,
			}
		);
	};

	const enableGateway = ( e: React.MouseEvent ) => {
		e.preventDefault();

		// Since this logic can toggle the gateway state on and off, we make sure we don't accidentally disable the gateway.
		if ( gatewayState.enabled ) {
			return;
		}

		// Record the event when user clicks on a gateway's enable button.
		recordEvent( 'settings_payments_provider_enable_click', {
			provider_id: gatewayId,
		} );

		const gatewayToggleNonce =
			window.woocommerce_admin.nonces?.gateway_toggle || '';

		if ( ! gatewayToggleNonce ) {
			throwError();
			window.location.href = settingsHref;
			return;
		}

		setIsUpdating( true );

		if ( incentive ) {
			acceptIncentive( incentive.promo_id );
		}

		togglePaymentGateway(
			gatewayId,
			window.woocommerce_admin.ajax_url,
			gatewayToggleNonce
		)
			.then( ( response: EnableGatewayResponse ) => {
				if ( response.data === 'needs_setup' ) {
					// We only need to perform additional logic/redirects if no account connected.
					if ( ! gatewayState.account_connected ) {
						// Record the event when user successfully enables a gateway.
						recordEvent( 'settings_payments_provider_enable', {
							provider_id: gatewayId,
						} );

						// Redirect to the recommended payment methods page if available, or the onboarding URL.
						if ( gatewayHasRecommendedPaymentMethods ) {
							const history = getHistory();
							history.push(
								getNewPath( {}, '/payment-methods' )
							);
						} else {
							// Redirect to the gateway's onboarding URL if it needs setup.
							window.location.href = onboardingHref;
							return;
						}
					} else {
						createErrorNotice(
							__(
								'The provider could not be enabled. Check the Manage page for details.',
								'woocommerce'
							),
							{
								type: 'snackbar',
								explicitDismiss: true,
								actions: [
									{
										label: __( 'Manage', 'woocommerce' ),
										url: settingsHref,
									},
								],
							}
						);
					}
				}
				// If no redirect occurred, the data needs to be refreshed.
				invalidateResolutionForStoreSelector(
					isOffline
						? 'getOfflinePaymentGateways'
						: 'getPaymentProviders'
				);
				setIsUpdating( false );
			} )
			.catch( () => {
				// In case of errors, redirect to the gateway settings page.
				setIsUpdating( false );
				throwError();
				window.location.href = settingsHref;
			} );
	};

	return (
		<Button
			variant={ 'primary' }
			isBusy={ isUpdating }
			disabled={ isUpdating || !! installingPlugin }
			onClick={ enableGateway }
			href={ settingsHref }
		>
			{ buttonText }
		</Button>
	);
};
