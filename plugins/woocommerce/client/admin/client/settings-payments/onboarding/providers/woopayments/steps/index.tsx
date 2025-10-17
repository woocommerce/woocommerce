/**
 * External dependencies
 */
import React from 'react';
import { __, sprintf } from '@wordpress/i18n';

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

export const TESTING_ACCOUNT_STEP_ID = 'test_account';
export const LIVE_ACCOUNT_STEP_ID = 'business_verification';

export const steps: WooPaymentsProviderOnboardingStep[] = [
	{
		id: 'payment_methods',
		order: 1,
		type: 'backend',
		label: __( 'Choose your payment methods', 'woocommerce' ),
		content: <PaymentMethodsSelection />,
	},
	{
		id: 'wpcom_connection',
		order: 2,
		type: 'backend',
		label: sprintf(
			/* translators: %s: WordPress.com */
			__( 'Connect with %s', 'woocommerce' ),
			'WordPress.com'
		),
		content: <WordPressComStep />,
		dependencies: [ 'payment_methods' ],
	},
	{
		id: 'activate_payments',
		order: 3,
		type: 'frontend',
		label: __( 'Activate payments', 'woocommerce' ),
		subSteps: [
			{
				id: 'test_or_live_account',
				order: 1,
				type: 'frontend',
				label: __( 'Test or live account', 'woocommerce' ),
				dependencies: [ 'wpcom_connection' ],
				content: <TestOrLiveAccountStep />,
			},
			{
				id: TESTING_ACCOUNT_STEP_ID,
				order: 2,
				type: 'backend',
				label: __( 'Ready to test payments', 'woocommerce' ),
				dependencies: [ 'test_or_live_account' ],
				content: <TestAccountStep />,
			},
			{
				id: LIVE_ACCOUNT_STEP_ID,
				order: 3,
				type: 'backend',
				label: __( 'Activate payments', 'woocommerce' ),
				dependencies: [ 'test_or_live_account' ],
				content: <BusinessVerificationStep />,
			},
		],
	},
	{
		id: 'finish',
		order: 4,
		type: 'frontend',
		label: __( 'Submit for verification', 'woocommerce' ),
		dependencies: [ LIVE_ACCOUNT_STEP_ID ],
		content: <FinishStep />,
	},
];

export const LYSPaymentsSteps: WooPaymentsProviderOnboardingStep[] = [
	{
		id: 'payment_methods',
		order: 1,
		type: 'backend',
		label: __( 'Choose your payment methods', 'woocommerce' ),
		content: <PaymentMethodsSelection />,
	},
	{
		id: 'wpcom_connection',
		order: 2,
		type: 'backend',
		label: sprintf(
			/* translators: %s: WordPress.com */
			__( 'Connect with %s', 'woocommerce' ),
			'WordPress.com'
		),
		content: <WordPressComStep />,
		dependencies: [ 'payment_methods' ],
	},
	{
		id: LIVE_ACCOUNT_STEP_ID,
		order: 3,
		type: 'backend',
		label: __( 'Activate payments', 'woocommerce' ),
		dependencies: [ 'wpcom_connection' ],
		content: <BusinessVerificationStep />,
	},
];
