/**
 * External dependencies
 */
import React, { useEffect } from 'react';
import { registerPlugin } from '@wordpress/plugins';
import {
	WooOnboardingTaskListItem,
	WooOnboardingTask,
} from '@woocommerce/onboarding';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';
import { Spinner } from '@woocommerce/components';

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
					// intercept the click on the task list item so that we don't have to see a intermediate page before installing woocommerce payments
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

const ReadyWcPay = () => {
	const { installAndActivatePlugins } = useDispatch( PLUGINS_STORE_NAME );
	const { createNotice } = useDispatch( 'core/notices' );

	useEffect( () => {
		new Promise( ( resolve, reject ) => {
			return installActivateAndConnectWcpay(
				reject,
				createNotice,
				installAndActivatePlugins
			);
		} );
	}, [ createNotice, installAndActivatePlugins ] );

	return (
		<div
			style={ {
				height: '70vh',
				display: 'flex',
				flexDirection: 'column',
				justifyContent: 'center',
				alignItems: 'center',
			} }
		>
			<Spinner />
			<div style={ { marginTop: '1rem' } }>
				Preparing payment settings...
			</div>
		</div>
	);
};

// shows up at http://host/wp-admin/admin.php?page=wc-admin&task=woocommerce-payments which is the default url for woocommerce-payments task
const WoocommercePaymentsTaskPage = () => (
	<WooOnboardingTask id="woocommerce-payments">
		<ReadyWcPay />
	</WooOnboardingTask>
);

registerPlugin( 'woocommerce-admin-task-wcpay-page', {
	scope: 'woocommerce-tasks',
	render: WoocommercePaymentsTaskPage,
} );
