/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import {
	PaymentProviderState,
	PaymentProviderOnboardingState,
	woopaymentsOnboardingStore,
} from '@woocommerce/data';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */

interface CompleteSetupButtonProps {
	/**
	 * The ID of the gateway to activate payments for.
	 */
	gatewayId: string;
	/**
	 * The state of the gateway.
	 */
	gatewayState: PaymentProviderState;
	/**
	 * The onboarding state for this gateway.
	 */
	onboardingState: PaymentProviderOnboardingState;
	/**
	 * The settings URL to navigate to, if we don't have an onboarding URL.
	 */
	settingsHref: string;
	/**
	 * The onboarding URL to navigate to when the gateway needs setup.
	 */
	onboardingHref: string;
	/**
	 * Whether the gateway has a list of recommended payment methods to use during the native onboarding flow.
	 */
	gatewayHasRecommendedPaymentMethods: boolean;
	/**
	 * The text of the button.
	 */
	buttonText?: string;
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
}

/**
 * A button component that guides users through completing the setup for a payment gateway.
 * The button dynamically determines the appropriate action (e.g., redirecting to onboarding
 * or settings) based on the gateway's and onboarding state.
 */
export const CompleteSetupButton = ( {
	gatewayId,
	gatewayState,
	onboardingState,
	settingsHref,
	onboardingHref,
	gatewayHasRecommendedPaymentMethods,
	installingPlugin,
	buttonText = __( 'Complete setup', 'woocommerce' ),
	setOnboardingModalOpen,
	onboardingType,
}: CompleteSetupButtonProps ) => {
	const [ isUpdating, setIsUpdating ] = useState( false );

	// Get the store's `select` function to trigger selector resolution later (in useEffect).
	// We don't need to select data directly here, just the function itself.
	const { select } = useSelect(
		( selectFn ) => ( { select: selectFn } ),
		[]
	);

	const accountConnected = gatewayState.account_connected;
	const onboardingStarted = onboardingState.started;
	const onboardingCompleted = onboardingState.completed;

	useEffect( () => {
		// Prefetch WooPayments onboarding data if conditions are met
		if (
			gatewayId === 'woocommerce_payments' &&
			onboardingType === 'native_in_context' &&
			! onboardingCompleted
		) {
			// Calling the selector triggers the data fetch
			select( woopaymentsOnboardingStore ).getOnboardingData();
		}
	}, [ gatewayId, onboardingType, onboardingCompleted, select ] );

	const completeSetup = () => {
		// Record the click of this button.
		recordEvent( 'settings_payments_provider_complete_setup_click', {
			provider_id: gatewayId,
			onboarding_started: onboardingState.started,
			onboarding_completed: onboardingState.completed,
			onboarding_test_mode: onboardingState.test_mode,
		} );

		setIsUpdating( true );

		if ( onboardingType === 'native_in_context' ) {
			setOnboardingModalOpen( true );
		} else if ( ! accountConnected || ! onboardingStarted ) {
			if ( gatewayHasRecommendedPaymentMethods ) {
				const history = getHistory();
				history.push( getNewPath( {}, '/payment-methods' ) );
			} else {
				// Redirect to the gateway's onboarding URL if it needs setup.
				window.location.href = onboardingHref;
				return;
			}
		} else if (
			accountConnected &&
			onboardingStarted &&
			! onboardingCompleted
		) {
			// Redirect to the gateway's onboarding URL if it needs setup.
			window.location.href = onboardingHref;
			return;
		} else {
			// Redirect to the gateway's settings URL if the account is already connected.
			window.location.href = settingsHref;
			return;
		}

		setIsUpdating( false );
	};

	return (
		<Button
			key={ gatewayId }
			variant={ 'primary' }
			isBusy={ isUpdating }
			disabled={ isUpdating || !! installingPlugin }
			onClick={ completeSetup }
		>
			{ buttonText }
		</Button>
	);
};
