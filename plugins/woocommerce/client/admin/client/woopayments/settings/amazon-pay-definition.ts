/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { WooPaymentsPaymentMethodDefinition } from './payment-method-definitions';

export const AMAZON_PAY_DEFINITION: WooPaymentsPaymentMethodDefinition = {
	id: 'amazon_pay',
	label: __( 'Amazon Pay', 'woocommerce' ),
	description: __(
		'Allow customers to make payments using Amazon Pay.',
		'woocommerce'
	),
	iconUrl: '',
	stripeKey: 'amazon_pay_payments',
	currencies: [],
	allowsManualCapture: false,
	allowsPayLater: false,
};
