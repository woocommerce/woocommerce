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
import PaymentMethodsSelection from './payment-methods-selection';

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

export const JetpackStep = () => {
	const { navigateToNextStep, refreshOnboardingSteps } =
		useOnboardingContext();
	return (
		<>
			<WooPaymentsStepHeader onClose={ () => {} } />
			<div className="settings-payments-onboarding-modal__step--content">
				<div>
					Jetpack Step Content{ ' ' }
					<button onClick={ () => navigateToNextStep() }>
						Next (Front-end only)
					</button>
					<button onClick={ () => refreshOnboardingSteps() }>
						Refresh redux store
					</button>
				</div>
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
		id: 'payment-methods-selection',
		order: 1,
		type: 'backend',
		label: 'Choose payment methods',
		content: <PaymentMethodsSelection />,
	},
	{
		id: 'welcome',
		order: 2,
		type: 'backend',
		label: 'Welcome to WooPayments',
		content: <WelcomeStep />,
		dependencies: [ 'payment-methods-selection' ],
	},
	{
		id: 'jetpack',
		order: 3,
		type: 'backend',
		label: 'Connect with Jetpack',
		content: <JetpackStep />,
		dependencies: [ 'welcome' ],
	},
	{
		id: 'congratulations',
		order: 4,
		type: 'frontend',
		label: 'Congratulations',
		path: '/woopayments/onboarding/congratulations',
		dependencies: [ 'jetpack' ],
		content: <FrontendStep />,
	},
	{
		id: 'final',
		order: 5,
		type: 'backend',
		label: 'Payment methods',
		dependencies: [ 'congratulations' ],
		content: <OtherStep />,
	},
];
