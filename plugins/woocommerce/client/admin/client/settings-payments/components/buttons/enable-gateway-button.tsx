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
	PaymentsProviderIncentive,
	PaymentGatewayProvider,
	OfflinePaymentMethodProvider,
} from '@woocommerce/data';
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import {
	recordPaymentsOnboardingEvent,
	recordPaymentsProviderEvent,
} from '~/settings-payments/utils';

interface EnableGatewayButtonProps {
	/**
	 * The details of the payment gateway to enable.
	 */
	gatewayProvider: PaymentGatewayProvider | OfflinePaymentMethodProvider;
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
	incentive?: PaymentsProviderIncentive | null;
	/**
	 * Function to set the onboarding modal open.
	 */
	setOnboardingModalOpen?: ( isOnboardingModalOpen: boolean ) => void;
	/**
	 * The onboarding type for the gateway.
	 */
	onboardingType?: string;
}

/**
 * A button component that allows users to enable a payment gateway.
 * Depending on the gateway's state, it redirects to settings, onboarding, or recommended payment methods pages.
 * If incentive data is provided, it will trigger the `acceptIncentive` callback with the incentive ID.
 */
export const EnableGatewayButton = ( {
	gatewayProvider,
	settingsHref,
	onboardingHref,
	isOffline,
	acceptIncentive = () => {},
	gatewayHasRecommendedPaymentMethods,
	installingPlugin,
	buttonText = __( 'Enable', 'woocommerce' ),
	incentive = null,
	setOnboardingModalOpen,
	onboardingType,
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
		if ( gatewayProvider.state.enabled ) {
			return;
		}

		// Record the event when user clicks on a gateway's enable button.
		recordPaymentsProviderEvent( 'enable_click', gatewayProvider );

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
			gatewayProvider.id,
			window.woocommerce_admin.ajax_url,
			gatewayToggleNonce
		)
			.then( ( response: EnableGatewayResponse ) => {
				// The backend will return 'needs_setup' if the gateway needs additional setup and could not be enabled.
				if ( response.data === 'needs_setup' ) {
					// We only need to perform additional logic/redirects if no account is connected.
					if ( ! gatewayProvider.state.account_connected ) {
						if (
							onboardingType === 'native_in_context' &&
							setOnboardingModalOpen
						) {
							recordPaymentsOnboardingEvent(
								'woopayments_onboarding_modal_opened'
							);
							setOnboardingModalOpen( true );
						} else if ( gatewayHasRecommendedPaymentMethods ) {
							// Redirect to the recommended payment methods page if available, or the onboarding URL.
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

						// Record the event when the gateway could not be enabled.
						recordPaymentsProviderEvent(
							'enable_failed',
							gatewayProvider,
							{
								reason: 'needs_setup',
							}
						);
					}
				}

				// If no redirect occurred, the data needs to be refreshed.
				invalidateResolutionForStoreSelector( 'getPaymentProviders' );

				if ( isOffline ) {
					// We need to invalidate both selectors since they share the same data source and resolver chain.
					invalidateResolutionForStoreSelector(
						'getOfflinePaymentGateways'
					);
				}

				setIsUpdating( false );
			} )
			.catch( () => {
				// Record the event when the gateway could not be enabled.
				recordPaymentsProviderEvent( 'enable_failed', gatewayProvider, {
					reason: 'error',
				} );

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
