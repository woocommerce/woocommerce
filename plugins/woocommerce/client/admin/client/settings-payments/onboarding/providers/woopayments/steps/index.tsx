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
import FinishStep from './finish';

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
		id: 'test_account',
		order: 3,
		type: 'backend',
		label: __( 'Ready to test payments', 'woocommerce' ),
		dependencies: [ 'wpcom_connection' ],
		content: <TestAccountStep />,
	},
	{
		id: 'business_verification',
		order: 4,
		type: 'backend',
		label: __( 'Activate Payments', 'woocommerce' ),
		dependencies: [ 'test_account' ],
		content: <BusinessVerificationStep />,
	},
	{
		id: 'finish',
		order: 5,
		type: 'frontend',
		label: __( 'Submit for verification', 'woocommerce' ),
		dependencies: [ 'business_verification' ],
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
		id: 'business_verification',
		order: 3,
		type: 'backend',
		label: __( 'Activate Payments', 'woocommerce' ),
		dependencies: [ 'wpcom_connection' ],
		content: <BusinessVerificationStep />,
	},
];
