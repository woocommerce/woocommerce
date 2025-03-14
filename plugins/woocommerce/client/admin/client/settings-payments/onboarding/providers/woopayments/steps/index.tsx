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
import WordPressComStep from './wpcom';

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
		id: 'welcome',
		order: 1,
		type: 'backend',
		label: 'Welcome to WooPayments',
		content: <WelcomeStep />,
	},
	{
		id: 'jetpack',
		order: 2,
		type: 'backend',
		label: 'Connect to WordPress.com',
		content: <WordPressComStep />,
	},
	{
		id: 'congratulations',
		order: 3,
		type: 'frontend',
		label: 'Congratulations',
		path: '/woopayments/onboarding/congratulations',
		dependencies: [ 'jetpack', 'welcome' ],
		content: <FrontendStep />,
	},
	{
		id: 'final',
		order: 4,
		type: 'backend',
		label: 'Payment methods',
		dependencies: [ 'congratulations' ],
		content: <OtherStep />,
	},
];
