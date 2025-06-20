/**
 * External dependencies
 */
import React, { useState } from 'react';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useStepperContext } from '../components/stepper';
import {
	disableWooPaymentsTestMode,
	recordPaymentsOnboardingEvent,
} from '~/settings-payments/utils';
import strings from '../strings';
import { useOnboardingContext } from '~/settings-payments/onboarding/providers/woopayments/data/onboarding-context';

const ActivatePayments: React.FC = () => {
	const { currentStep } = useOnboardingContext();
	const { nextStep } = useStepperContext();
	const [ isContinueButtonLoading, setIsContinueButtonLoading ] =
		useState( false );

	const handleContinue = () => {
		recordPaymentsOnboardingEvent( 'woopayments_onboarding_modal_click', {
			step: currentStep?.id || 'unknown',
			sub_step_id: 'activate',
			action: 'activate_payments',
		} );

		// Set the continue button loading state to true.
		setIsContinueButtonLoading( true );

		// Disable test mode and redirect to the live account setup link.
		disableWooPaymentsTestMode()
			.then( () => {
				// Set the continue button loading state to false.
				setIsContinueButtonLoading( false );
				// Navigate to the live account setup.
				return nextStep();
			} )
			.catch( () => {
				// Handle any errors that occur during the process.
				setIsContinueButtonLoading( false );
			} );
	};

	return (
		<>
			<h1 className="stepper__heading">
				{ strings.steps.activate.heading }
			</h1>
			<p className="stepper__subheading">
				{ strings.steps.activate.subheading }
			</p>
			<div className="stepper__content">
				<Button
					variant="primary"
					className="stepper__cta"
					onClick={ handleContinue }
					isBusy={ isContinueButtonLoading }
					disabled={ isContinueButtonLoading }
				>
					{ strings.steps.activate.cta }
				</Button>
			</div>
		</>
	);
};

export default ActivatePayments;
