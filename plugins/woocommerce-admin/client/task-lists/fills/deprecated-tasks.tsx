/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTask } from '@woocommerce/onboarding';
import { useSelect } from '@wordpress/data';
import {
	ONBOARDING_STORE_NAME,
	TaskType,
	DeprecatedTaskType,
} from '@woocommerce/data';
import { useEffect, useState } from '@wordpress/element';

type MergedTask = TaskType & DeprecatedTaskType;

const DeprecatedWooOnboardingTaskFills = () => {
	const [ deprecatedTasks, setDeprecatedTasks ] = useState< MergedTask[] >(
		[]
	);
	const { isResolving, taskLists } = useSelect( ( select ) => {
		return {
			isResolving: select( ONBOARDING_STORE_NAME ).isResolving(
				'getTaskLists'
			),
			taskLists: select( ONBOARDING_STORE_NAME ).getTaskLists(),
		};
	} );

	useEffect( () => {
		if ( taskLists && taskLists.length > 0 ) {
			const deprecatedTasksWithContainer: MergedTask[] = [];
			for ( const tasklist of taskLists ) {
				for ( const task of tasklist.tasks ) {
					if (
						( task as MergedTask ).isDeprecated &&
						( task as MergedTask ).container
					) {
						deprecatedTasksWithContainer.push( task as MergedTask );
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
