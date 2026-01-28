/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { chevronLeft } from '@wordpress/icons';
import { __, sprintf } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import type { SidebarComponentProps } from '../xstate';
import { recordPaymentsOnboardingEvent } from '~/settings-payments/utils';
import { wooPaymentsOnboardingSessionEntryLYS } from '~/settings-payments/constants';
import { useSetUpPaymentsContext } from '~/launch-your-store/data/setup-payments-context';
import { useOnboardingContext } from '~/settings-payments/onboarding/providers/woopayments/data/onboarding-context';

export const PaymentsMobileHeader = ( props: SidebarComponentProps ) => {
	const { wooPaymentsRecentlyActivated } = useSetUpPaymentsContext();

	const { steps: allSteps, currentStep } = useOnboardingContext();

	// Current step index is determined by finding the index of the current step in all steps.
	// If there are no steps, we default to 1 to avoid division by zero.
	let currentStepIndex =
		allSteps.findIndex( ( step ) => step.id === currentStep?.id ) + 1 || 1;

	// Total steps is the length of all steps.
	// If there are no steps, we default to 1 to avoid division by zero.
	let totalSteps = allSteps.length || 1;

	// If WooPayments was recently activated, we increment the step index and total steps.
	// This is to account for the initial setup step that is not part of the onboarding steps.
	if ( wooPaymentsRecentlyActivated ) {
		currentStepIndex++;
		totalSteps++;
	}

	const handleBackClick = () => {
		recordEvent( 'launch_your_store_payments_back_to_hub_click' );

		// Record the "modal" being closed to keep consistency with the Payments Settings flow.
		recordPaymentsOnboardingEvent( 'woopayments_onboarding_modal_closed', {
			from: 'lys_mobile_header_back_to_hub',
			source: wooPaymentsOnboardingSessionEntryLYS,
		} );

		// Clear session flag to prevent redirect back to payments setup
		// after exiting the flow and returning to the WC Admin home.
		window.sessionStorage.setItem( 'lysWaiting', 'no' );

		props.sendEventToSidebar( {
			type: 'RETURN_FROM_PAYMENTS',
		} );
	};

	return (
		<div className="mobile-header payments-mobile-header">
			<Button
				className="mobile-header__back-button"
				onClick={ handleBackClick }
				icon={ chevronLeft }
				iconSize={ 20 }
				aria-label={ __( 'Go back', 'woocommerce' ) }
			/>
			<h1 className="mobile-header__title">
				{
					/* translators: %s: Payment provider name (e.g., WooPayments) */
					sprintf( __( 'Set up %s', 'woocommerce' ), 'WooPayments' )
				}
			</h1>
			<div className="mobile-header__steps">
				{ /* translators: %1$s: current step number, %2$s: total number of steps */ }
				{ sprintf(
					/* translators: %1$s: current step number, %2$s: total number of steps */
					__( 'Step %1$s of %2$s', 'woocommerce' ),
					currentStepIndex,
					totalSteps
				) }
			</div>
		</div>
	);
};
