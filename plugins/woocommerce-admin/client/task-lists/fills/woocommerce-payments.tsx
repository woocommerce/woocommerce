/**
 * External dependencies
 */
import React from 'react';
import { registerPlugin } from '@wordpress/plugins';
import {
	WooOnboardingTaskListItem,
	WooOnboardingTask,
} from '@woocommerce/onboarding';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { installActivateAndConnectWcpay } from './PaymentGatewaySuggestions/components/WCPay';
import { PaymentGatewaySuggestions } from './PaymentGatewaySuggestions';

const WoocommercePaymentsTaskItem = () => {
	const { installAndActivatePlugins } = useDispatch( PLUGINS_STORE_NAME );
	const { createNotice } = useDispatch( 'core/notices' );

	return (
		<WooOnboardingTaskListItem id="woocommerce-payments">
			{ ( {
				defaultTaskItem: DefaultTaskItem,
			}: {
				defaultTaskItem: ( props: {
					onClick: () => void;
				} ) => JSX.Element;
			} ) => (
				<DefaultTaskItem
					// Intercept the click on the task list item so that we don't have to see an intermediate page before installing WooPayments.
					onClick={ () => {
						return new Promise( ( resolve, reject ) => {
							return installActivateAndConnectWcpay(
								reject,
								createNotice,
								installAndActivatePlugins
							);
						} );
					} }
				/>
			) }
		</WooOnboardingTaskListItem>
	);
};

registerPlugin( 'woocommerce-admin-task-wcpay', {
	// @ts-expect-error scope is not defined in the type definition but it is a valid property
	scope: 'woocommerce-tasks',
	render: WoocommercePaymentsTaskItem,
} );

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
