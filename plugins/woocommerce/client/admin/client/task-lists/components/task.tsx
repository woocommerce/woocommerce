/**
 * External dependencies
 */
import {
	WooHeaderNavigationItem,
	WooHeaderPageTitle,
} from '@woocommerce/admin-layout';
import { WooOnboardingTask } from '@woocommerce/onboarding';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { onboardingStore, TaskType } from '@woocommerce/data';
import { useCallback } from '@wordpress/element';
import { useDispatch, resolveSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */
import { BackButton } from './back-button';

export type TaskProps = {
	query: { task?: string };
	task: TaskType;
};

export const Task: React.FC< TaskProps > = ( { query, task } ) => {
	const id = query.task || '';
	if ( ! id ) {
		// eslint-disable-next-line no-console
		console.warn( 'No task id provided' );
		// eslint-enable-next-line no-console
	}

	const { invalidateResolutionForStoreSelector, optimisticallyCompleteTask } =
		useDispatch( onboardingStore );

	const updateBadge = useCallback( async () => {
		const badgeElements = document.querySelectorAll(
			'#adminmenu .woocommerce-task-list-remaining-tasks-badge'
		);

		if ( ! badgeElements?.length ) {
			return;
		}

		const setupTaskList = await resolveSelect(
			onboardingStore
		).getTaskList( 'setup' );
		if ( ! setupTaskList ) {
			return;
		}

		const remainingTasksCount = setupTaskList.tasks.filter(
			( _task: TaskType ) => ! _task.isComplete
		).length;

		badgeElements.forEach( ( badge ) => {
			badge.textContent = remainingTasksCount.toString();
		} );
	}, [] );

	const onComplete = useCallback(
		( options: Record< string, unknown > ) => {
			optimisticallyCompleteTask( id );
			getHistory().push(
				options && options.redirectPath
					? options.redirectPath
					: getNewPath( {}, '/', {} )
			);
			invalidateResolutionForStoreSelector( 'getTaskLists' );
			updateBadge();
		},
		[
			id,
			invalidateResolutionForStoreSelector,
			optimisticallyCompleteTask,
			updateBadge,
		]
	);

	return (
		<>
			<WooHeaderNavigationItem>
				<BackButton title={ task.title } />
			</WooHeaderNavigationItem>
			<WooHeaderPageTitle>{ task.title }</WooHeaderPageTitle>
			<WooOnboardingTask.Slot
				id={ id }
				fillProps={ { onComplete, query, task } }
			/>
		</>
	);
};
