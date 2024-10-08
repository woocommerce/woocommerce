/**
 * External dependencies
 */
import React from 'react';
import { registerPlugin } from '@wordpress/plugins';
import {
	WooOnboardingTaskListItem,
	WooOnboardingTask,
} from '@woocommerce/onboarding';
import { updateQueryString } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { PaymentGatewaySuggestions } from './PaymentGatewaySuggestions';
import { getPluginTrackKey } from '~/utils';

const WoocommercePaymentsTaskItem = () => {
	const task = 'woocommerce-payments';
	const gatewayId = 'woocommerce_payments';

	return (
		<WooOnboardingTaskListItem id={ task }>
			{ ( {
				defaultTaskItem: DefaultTaskItem,
			}: {
				defaultTaskItem: ( props: {
					onClick: () => void;
				} ) => JSX.Element;
			} ) => (
				<DefaultTaskItem
					// Intercept the click on the task list item so that we don't have to see
					// the task page before installing WooPayments.
					onClick={ () => {
						// Fire both the WooPayments install event (backward compatibility)
						// and the tasklist_payment_setup event.
						recordEvent( 'woocommerce_payments_install', {
							context: 'tasklist',
						} );
						recordEvent( 'tasklist_payment_setup', {
							selected: getPluginTrackKey( gatewayId ),
						} );

						// Just updating the query string to show the task page and target a specific recommendation
						// will trigger the plugin installation.
						updateQueryString( {
							task,
							id: gatewayId,
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
