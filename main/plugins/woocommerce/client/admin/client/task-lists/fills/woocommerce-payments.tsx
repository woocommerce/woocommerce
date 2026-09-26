/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTask } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import { PaymentGatewaySuggestions } from './PaymentGatewaySuggestions';

// Shows up at http://host/wp-admin/admin.php?page=wc-admin&task=woocommerce-payments which is the default url for woocommerce-payments task
const WoocommercePaymentsTaskPage = () => (
	<WooOnboardingTask id="woocommerce-payments">
		{ ( { onComplete, query } ) => (
			<PaymentGatewaySuggestions
				onComplete={ onComplete }
				query={ query }
			/>
		) }
	</WooOnboardingTask>
);

registerPlugin( 'woocommerce-admin-task-wcpay-page', {
	scope: 'woocommerce-tasks',
	render: WoocommercePaymentsTaskPage,
} );
