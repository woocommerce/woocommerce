/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { ONBOARDING_STORE_NAME, TaskListType } from '@woocommerce/data';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import './progress-header.scss';
import { TaskListMenu } from '~/tasks/task-list-menu';

type ProgressHeaderProps = {
	taskListId: string;
};

export const ProgressHeader: React.FC< ProgressHeaderProps > = ( {
	taskListId,
} ) => {
	const { loading, tasksCount, completedCount, hasVisitedTasks } = useSelect(
		( select ) => {
			const isResolving = select( ONBOARDING_STORE_NAME ).isResolving(
				'getTaskList'
			);
			const taskList: TaskListType = select(
				ONBOARDING_STORE_NAME
			).getTaskList( taskListId );
			const nowTimestamp = Date.now();
			const visibleTasks = taskList?.tasks.filter(
				( task ) =>
					! task.isDismissed &&
					( ! task.isSnoozed || task.snoozedUntil < nowTimestamp )
			);

			return {
				loading: isResolving,
				tasksCount: visibleTasks?.length,
				completedCount: visibleTasks?.filter(
					( task ) => task.isComplete
				).length,
				hasVisitedTasks:
					visibleTasks?.filter( ( task ) => task.isVisited ).length >
					0,
			};
		}
	);

	const progressTitle = useMemo( () => {
		if ( ! hasVisitedTasks || completedCount === tasksCount ) {
			const siteTitle = getSetting( 'siteTitle' );
			return siteTitle
				? sprintf(
						/* translators: %s = site title */
						__( 'Welcome to %s', 'woocommerce-admin' ),
						siteTitle
				  )
				: __( 'Welcome', 'woocommerce-admin' );
		} else if ( completedCount > 0 && completedCount < 3 ) {
			return __( "Let's get you set up", 'woocommerce-admin' ) + '   🚀';
		} else if ( completedCount > 2 && completedCount < 5 ) {
			return __( 'You are on the right track', 'woocommerce-admin' );
		}
		return __( 'Just a few tasks left', 'woocommerce-admin' );
	}, [ completedCount, hasVisitedTasks ] );

	if ( loading || completedCount === tasksCount ) {
		return null;
	}

	return (
		<div className="woocommerce-task-progress-header">
			<TaskListMenu
				id={ taskListId }
				hideTaskListText={ __(
					'Hide setup list',
					'woocommerce-admin'
				) }
			/>
			<div className="woocommerce-task-progress-header__contents">
				<h1 className="woocommerce-task-progress-header__title">
					{ progressTitle }
				</h1>
				<p>
					{ sprintf(
						/* translators: 1: completed tasks, 2: total tasks */
						__(
							'Follow these steps to start selling quickly. %1$d out of %2$d complete.',
							'woocommerce-admin'
						),
						completedCount,
						tasksCount
					) }
				</p>
				<progress
					className="woocommerce-task-progress-header__progress-bar"
					max={ tasksCount }
					value={ completedCount || 0 }
				/>
			</div>
		</div>
	);
};
