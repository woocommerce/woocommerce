/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import { useOnboardingContext } from '../data/onboarding-context';
import { WooPaymentsProviderOnboardingStep } from '~/settings-payments/onboarding/types';
import WooPaymentsStepHeader from '../components/header';
import WordPressComStep from './wpcom-connection';
import BusinessVerificationStep from './business-verification';
import PaymentMethodsSelection from './payment-methods-selection';
import TestAccountStep from './test-account';
import FinishStep from './finish';

/**
 * Step Components
 */
export const WelcomeStep = () => {
	const { navigateToNextStep, refreshOnboardingSteps } =
		useOnboardingContext();
	return (
		<>
			<WooPaymentsStepHeader onClose={ () => {} } />
			<div className="settings-payments-onboarding-modal__step--content">
				Welcome Step Content
				<button onClick={ () => navigateToNextStep() }>
					Next (Front-end only)
				</button>
				<button onClick={ () => refreshOnboardingSteps() }>
					Refresh redux store
				</button>
			</div>
		</>
	);
};

export const OtherStep = () => {
	const { navigateToNextStep, refreshOnboardingSteps } =
		useOnboardingContext();
	return (
		<>
			<WooPaymentsStepHeader onClose={ () => {} } />
			<div className="settings-payments-onboarding-modal__step--content">
				Other Step Content
				<button onClick={ () => navigateToNextStep() }>
					Next (Front-end only)
				</button>
				<button onClick={ () => refreshOnboardingSteps() }>
					Refresh redux store
				</button>
			</div>
		</>
	);
};

export const FrontendStep = () => {
	const { navigateToNextStep, refreshOnboardingSteps } =
		useOnboardingContext();
	return (
		<>
			<WooPaymentsStepHeader onClose={ () => {} } />
			<div className="settings-payments-onboarding-modal__step--content">
				Frontend Step Content
				<button onClick={ () => navigateToNextStep() }>
					Next (Front-end only)
				</button>
				<button onClick={ () => refreshOnboardingSteps() }>
					Refresh redux store
				</button>
			</div>
		</>
	);
};

export const steps: WooPaymentsProviderOnboardingStep[] = [
	{
		id: 'payment_methods',
		order: 1,
		type: 'backend',
		label: 'Choose your payment methods',
		content: <PaymentMethodsSelection />,
	},
	{
		id: 'wpcom_connection',
		order: 2,
		type: 'backend',
		label: 'Connect with WordPress.com',
		content: <WordPressComStep />,
		dependencies: [ 'payment_methods' ],
	},
	{
		id: 'test_account',
		order: 3,
		type: 'backend',
		label: 'Ready to test payments',
		dependencies: [ 'wpcom_connection' ],
		content: <TestAccountStep />,
	},
	{
		id: 'business_verification',
		order: 4,
		type: 'backend',
		label: 'Activate Payments',
		dependencies: [ 'test_account' ],
		content: <BusinessVerificationStep />,
	},
	{
		id: 'finish',
		order: 5,
		type: 'backend',
		label: 'Submit for verification',
		dependencies: [ 'business_verification' ],
		content: <FinishStep />,
	},
];
