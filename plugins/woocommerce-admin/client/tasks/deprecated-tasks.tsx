/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTask } from '@woocommerce/onboarding';
import { useSelect } from '@wordpress/data';
import {
	ONBOARDING_STORE_NAME,
	TaskType,
	WCDataSelector,
} from '@woocommerce/data';
import { useEffect, useState } from '@wordpress/element';

type DeprecatedTask = TaskType & {
	container: React.ReactNode;
};

const DeprecatedWooOnboardingTaskFills = () => {
	const [ deprecatedTasks, setDeprecatedTasks ] = useState<
		DeprecatedTask[]
	>( [] );
	const { isResolving, taskLists } = useSelect(
		( select: WCDataSelector ) => {
			return {
				isResolving: select( ONBOARDING_STORE_NAME ).isResolving(
					'getTaskLists'
				),
				taskLists: select( ONBOARDING_STORE_NAME ).getTaskLists(),
			};
		}
	);

	useEffect( () => {
		if ( taskLists && taskLists.length > 0 ) {
			const deprecatedTasksWithContainer = [];
			for ( const tasklist of taskLists ) {
				for ( const task of tasklist.tasks ) {
					if ( task.isDeprecated && task.container ) {
						deprecatedTasksWithContainer.push( task );
					}
				}
			}
			setDeprecatedTasks( deprecatedTasksWithContainer );
		}
	}, [ taskLists ] );

	if ( isResolving ) {
		return null;
	}
	return (
		<>
			{ deprecatedTasks.map( ( task ) => (
				// @ts-expect-error WooOnboardingTask is still a pure JS file
				<WooOnboardingTask id={ task.id } key={ task.id }>
					{ () => task.container }
				</WooOnboardingTask>
			) ) }
		</>
	);
};

registerPlugin( 'wc-admin-deprecated-task-container', {
	// @ts-expect-error @types/wordpress__plugins need to be updated
	scope: 'woocommerce-tasks',
	render: () => <DeprecatedWooOnboardingTaskFills />,
} );
