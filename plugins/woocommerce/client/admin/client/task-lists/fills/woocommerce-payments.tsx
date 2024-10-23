/**
 * External dependencies
 */
import React from 'react';
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTask } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import { PaymentGatewaySuggestions } from './PaymentGatewaySuggestions';

// Shows up at http://host/wp-admin/admin.php?page=wc-admin&task=woocommerce-payments which is the default url for woocommerce-payments task
const WoocommercePaymentsTaskPage = () => (
	<WooOnboardingTask id="woocommerce-payments">
		{ ( {
			onComplete,
			query,
		}: {
			onComplete: () => void;
			query: { id: string };
		} ) => (
			<PaymentGatewaySuggestions
				onComplete={ onComplete }
				query={ query }
			/>
		) }
	</WooOnboardingTask>
);

registerPlugin( 'woocommerce-admin-task-wcpay-page', {
	// @ts-expect-error scope is not defined in the type definition but it is a valid property
	scope: 'woocommerce-tasks',
	render: WoocommercePaymentsTaskPage,
} );
