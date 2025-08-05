/**
 * External dependencies
 */
import React, { useState } from 'react';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useStepperContext } from '../components/stepper';
import { recordPaymentsOnboardingEvent } from '~/settings-payments/utils';
import strings from '../strings';
import { useOnboardingContext } from '~/settings-payments/onboarding/providers/woopayments/data/onboarding-context';

const ActivatePayments: React.FC = () => {
	const { currentStep, sessionEntryPoint } = useOnboardingContext();
	const { nextStep } = useStepperContext();
	const [ isContinueButtonLoading, setIsContinueButtonLoading ] =
		useState( false );

	const handleContinue = () => {
		recordPaymentsOnboardingEvent( 'woopayments_onboarding_modal_click', {
			step: currentStep?.id || 'unknown',
			sub_step_id: 'activate',
			action: 'activate_payments',
			source: sessionEntryPoint,
		} );

		if ( ! currentStep?.actions?.test_account_disable?.href ) {
			// If there is no test account disable URL, we can proceed to the next step directly.
			return nextStep();
		}

		setIsContinueButtonLoading( true );

		// Disable test account and proceed to live KYC.
		apiFetch( {
			url: currentStep?.actions?.test_account_disable?.href,
			method: 'POST',
			data: {
				from: 'step_' + ( currentStep?.id || 'unknown' ),
				source: sessionEntryPoint,
			},
		} )
			.then( () => {
				setIsContinueButtonLoading( false );
				// Navigate to the live account setup.
				return nextStep();
			} )
			.catch( () => {
				// Handle any errors that occur during the process.
				setIsContinueButtonLoading( false );
				// Error tracking is handled on the backend, so we don't need to do anything here.
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
