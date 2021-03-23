/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import apiFetch from '@wordpress/api-fetch';

/**
 * WooCommerce dependencies
 */
import { Card, CardBody } from '@wordpress/components';
import { getHistory, getNewPath } from '@woocommerce/navigation';

/* global addTaskData */
const markTaskComplete = () => {
	apiFetch( {
		path: '/wc-admin/options',
		method: 'POST',
		data: { woocommerce_admin_add_task_example_complete: true },
	} )
		.then( () => {
			// Set the local `isComplete` to `true` so that task appears complete on the list.
			addTaskData.isComplete = true;
			// Redirect back to the root WooCommerce Admin page.
			getHistory().push( getNewPath( {}, '/', {} ) );
		} )
		.catch( ( error ) => {
			// Something went wrong with our update.
			console.log( error );
		} );
};

const markTaskIncomplete = () => {
	apiFetch( {
		path: '/wc-admin/options',
		method: 'POST',
		data: { woocommerce_admin_add_task_example_complete: false },
	} )
		.then( () => {
			addTaskData.isComplete = false;
			getHistory().push( getNewPath( {}, '/', {} ) );
		} )
		.catch( ( error ) => {
			console.log( error );
		} );
};

const Task = () => {
	return (
		<Card className="woocommerce-task-card">
			<CardBody>
				{ __( 'Example task card content.', 'plugin-domain' ) }
				<br />
				<br />
				<div>
					{ addTaskData.isComplete ? (
						<button onClick={ markTaskIncomplete }>
							{ __( 'Mark task incomplete', 'plugin-domain' ) }
						</button>
					) : (
						<button onClick={ markTaskComplete }>
							{ __( 'Mark task complete', 'plugin-domain' ) }
						</button>
					) }
				</div>
			</CardBody>
		</Card>
	);
};

/**
 * Use the 'woocommerce_admin_onboarding_task_list' filter to add a task page.
 */
addFilter(
	'woocommerce_admin_onboarding_task_list',
	'plugin-domain',
	( tasks ) => {
		return [
			...tasks,
			{
				key: 'example',
				title: __( 'Example', 'plugin-domain' ),
				content: __( 'This is an example task.', 'plugin-domain' ),
				container: <Task />,
				completed: addTaskData.isComplete,
				visible: true,
				additionalInfo: __( 'Additional info here', 'woocommerce-admin' ),
				time: __( '2 minutes', 'woocommerce-admin' ),
				isDismissable: true,
				onDismiss: () => console.log( 'The task was dismissed' ),
			},
		];
	}
);
