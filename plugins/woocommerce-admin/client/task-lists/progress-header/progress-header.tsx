/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import {
	getVisibleTasks,
	ONBOARDING_STORE_NAME,
	WCDataSelector,
} from '@woocommerce/data';
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
	const {
		loading,
		tasksCount,
		completedCount,
		hasVisitedTasks,
		disabledCompletedCount,
	} = useSelect( ( select: WCDataSelector ) => {
		const taskList = select( ONBOARDING_STORE_NAME ).getTaskList(
			taskListId
		);
		const finishedResolution = select(
			ONBOARDING_STORE_NAME
		).hasFinishedResolution( 'getTaskList', [ taskListId ] );
		const visibleTasks = getVisibleTasks( taskList?.tasks || [] );

		return {
			loading: ! finishedResolution,
			tasksCount: visibleTasks?.length,
			completedCount: visibleTasks?.filter( ( task ) => task.isComplete )
				.length,
			disabledCompletedCount: visibleTasks?.filter(
				( task ) => task.isComplete && task.isDisabled
			).length,
			hasVisitedTasks:
				visibleTasks?.filter( ( task ) => task.isVisited ).length > 0,
		};
	} );

	const progressTitle = useMemo( () => {
		if (
			( ! hasVisitedTasks &&
				completedCount < 2 + disabledCompletedCount ) ||
			completedCount === tasksCount
		) {
			const siteTitle = getSetting( 'siteTitle' );
			return siteTitle
				? sprintf(
						/* translators: %s = site title */
						__( 'Welcome to %s', 'woocommerce' ),
						siteTitle
				  )
				: __( 'Welcome', 'woocommerce' );
		}
		if ( completedCount > 0 && completedCount < 4 ) {
			return __( "Let's get you started", 'woocommerce' ) + '   🚀';
		}
		if ( completedCount > 3 && completedCount < 6 ) {
			return __( 'You are on the right track', 'woocommerce' );
		}
		return __( 'You are almost there', 'woocommerce' );
	}, [ completedCount, hasVisitedTasks ] );

	if ( loading ) {
		return null;
	}

	return (
		<div className="woocommerce-task-progress-header">
			<TaskListMenu
				id={ taskListId }
				hideTaskListText={ __( 'Hide setup list', 'woocommerce' ) }
			/>
			<div className="woocommerce-task-progress-header__contents">
				<h1 className="woocommerce-task-progress-header__title">
					{ progressTitle }
				</h1>
				{ completedCount !== tasksCount ? (
					<>
						<p>
							{ sprintf(
								/* translators: 1: completed tasks, 2: total tasks */
								__(
									'Follow these steps to start selling quickly. %1$d out of %2$d complete.',
									'woocommerce'
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
					</>
				) : null }
			</div>
		</div>
	);
};
