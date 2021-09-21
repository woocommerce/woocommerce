/**
 * External dependencies
 */
import React from 'react';
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTaskListItem } from '@woocommerce/onboarding';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { installActivateAndConnectWcpay } from './PaymentGatewaySuggestions/components/WCPay';

const WoocommercePaymentsTaskItem = () => {
	const { installAndActivatePlugins } = useDispatch( PLUGINS_STORE_NAME );
	const { createNotice } = useDispatch( 'core/notices' );

	return (
		<WooOnboardingTaskListItem id="woocommerce-payments">
			{ ( { defaultTaskItem: DefaultTaskItem } ) => (
				<DefaultTaskItem
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
	scope: 'woocommerce-tasks',
	render: WoocommercePaymentsTaskItem,
} );
