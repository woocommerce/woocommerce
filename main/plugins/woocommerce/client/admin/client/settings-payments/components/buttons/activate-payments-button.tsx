/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { PaymentsProviderIncentive } from '@woocommerce/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	getWooPaymentsSetupLiveAccountLink,
	recordPaymentsEvent,
	recordPaymentsOnboardingEvent,
} from '~/settings-payments/utils';
import {
	wooPaymentsExtensionSlug,
	wooPaymentsOnboardingSessionEntrySettings,
	wooPaymentsProviderId,
	wooPaymentsSuggestionId,
} from '~/settings-payments/constants';

interface ActivatePaymentsButtonProps {
	/**
	 * Callback used when an incentive is accepted.
	 *
	 * @param id Incentive ID.
	 */
	acceptIncentive: ( id: string ) => void;
	/**
	 * The text of the button.
	 */
	buttonText?: string;
	/**
	 * Incentive data. If provided, the incentive will be accepted when the button is clicked.
	 */
	incentive?: PaymentsProviderIncentive | null;
	/**
	 * ID of the plugin that is being installed.
	 */
	installingPlugin: string | null;
	/**
	 * Function to set the onboarding modal open.
	 */
	setOnboardingModalOpen: ( isOnboardingModalOpen: boolean ) => void;
	/**
	 * The onboarding type for the gateway.
	 */
	onboardingType?: string;
	/**
	 * URL to disable the test account before proceeding to live account setup.
	 */
	disableTestAccountUrl?: string;
}

/**
 * A button component that initiates the payment activation process.
 * If incentive data is provided, it will trigger the `acceptIncentive` callback with the incentive ID before
 * moving to the live account setup.
 */
export const ActivatePaymentsButton = ( {
	acceptIncentive,
	installingPlugin,
	buttonText = __( 'Activate payments', 'woocommerce' ),
	incentive = null,
	setOnboardingModalOpen,
	onboardingType,
	disableTestAccountUrl,
}: ActivatePaymentsButtonProps ) => {
	const [ isUpdating, setIsUpdating ] = useState( false );

	const activatePayments = () => {
		setIsUpdating( true );

		recordPaymentsEvent( 'activate_payments_button_click', {
			provider_id: wooPaymentsProviderId,
			suggestion_id: wooPaymentsSuggestionId,
			incentive_id: incentive ? incentive.promo_id : 'none',
			onboarding_type: onboardingType || 'unknown',
			provider_extension_slug: wooPaymentsExtensionSlug,
		} );

		// If no URL to disable the test account is provided, we just point the user to the live account setup.
		if ( ! disableTestAccountUrl ) {
			if ( incentive ) {
				acceptIncentive( incentive.promo_id );
			}

			setIsUpdating( false );
			if ( onboardingType === 'native_in_context' ) {
				// Open the onboarding modal.
				recordPaymentsOnboardingEvent(
					'woopayments_onboarding_modal_opened',
					{
						from: 'activate_payments_button',
						source: wooPaymentsOnboardingSessionEntrySettings,
					}
				);
				setOnboardingModalOpen( true );
			} else {
				window.location.href = getWooPaymentsSetupLiveAccountLink();
			}

			return;
		}

		// Disable test account and redirect to the live account setup link.
		apiFetch( {
			url: disableTestAccountUrl,
			method: 'POST',
		} )
			.then( () => {
				if ( incentive ) {
					acceptIncentive( incentive.promo_id );
				}

				setIsUpdating( false );

				if ( onboardingType === 'native_in_context' ) {
					// Open the onboarding modal.
					recordPaymentsOnboardingEvent(
						'woopayments_onboarding_modal_opened',
						{
							from: 'activate_payments_button',
							source: wooPaymentsOnboardingSessionEntrySettings,
						}
					);
					setOnboardingModalOpen( true );
				} else {
					window.location.href = getWooPaymentsSetupLiveAccountLink();
				}
			} )
			.catch( () => {
				// Handle any errors that occur during the process.
				setIsUpdating( false );
				// Error tracking is handled on the backend, so we don't need to do anything here.
			} );
	};

	return (
		<Button
			variant={ 'primary' }
			isBusy={ isUpdating }
			disabled={ isUpdating || !! installingPlugin }
			onClick={ activatePayments }
		>
			{ buttonText }
		</Button>
	);
};
