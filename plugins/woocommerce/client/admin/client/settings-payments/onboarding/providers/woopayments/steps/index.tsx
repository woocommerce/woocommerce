/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import { WooPaymentsProviderOnboardingStep } from '~/settings-payments/onboarding/types';
import WordPressComStep from './wpcom-connection';
import BusinessVerificationStep from './business-verification';
import PaymentMethodsSelection from './payment-methods-selection';
import TestAccountStep from './test-account';
import TestOrLiveAccountStep from './test-or-live-account';
import FinishStep from './finish';

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
		id: 'activate_payments',
		order: 3,
		type: 'frontend',
		label: 'Activate Payments',
		subSteps: [
			{
				id: 'test_or_live_account',
				order: 1,
				type: 'frontend',
				label: 'Test or live account',
				dependencies: [ 'wpcom_connection' ],
				content: <TestOrLiveAccountStep />,
			},
			{
				id: 'test_account',
				order: 2,
				type: 'backend',
				label: 'Ready to test payments',
				dependencies: [ 'test_or_live_account' ],
				content: <TestAccountStep />,
			},
			{
				id: 'business_verification',
				order: 3,
				type: 'backend',
				label: 'Activate Payments',
				dependencies: [ 'test_or_live_account' ],
				content: <BusinessVerificationStep />,
			},
		],
	},
	{
		id: 'finish',
		order: 4,
		type: 'frontend',
		label: 'Submit for verification',
		dependencies: [ 'business_verification' ],
		content: <FinishStep />,
	},
];

export const LYSPaymentsSteps: WooPaymentsProviderOnboardingStep[] = [
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
		id: 'business_verification',
		order: 3,
		type: 'backend',
		label: 'Activate Payments',
		dependencies: [ 'wpcom_connection' ],
		content: <BusinessVerificationStep />,
	},
];
