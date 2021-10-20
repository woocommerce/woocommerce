/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { ONBOARDING_STORE_NAME, OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import '../tasks/tasks.scss';
import './style.scss';
import TaskList from './task-list';
import TaskListPlaceholder from './placeholder';
import { Task } from '../tasks/task';

const taskDashboardSelect = ( select ) => {
	const { getOption, hasFinishedResolution } = select( OPTIONS_STORE_NAME );

	return {
		keepCompletedTaskList: getOption(
			'woocommerce_task_list_keep_completed'
		),
		isResolving: ! hasFinishedResolution( 'getOption', [
			'woocommerce_task_list_keep_completed',
		] ),
	};
};

const TaskDashboard = ( { query, twoColumns } ) => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const {
		keepCompletedTaskList,
		isResolving: isResolvingOptions,
	} = useSelect( taskDashboardSelect );

	useEffect( () => {
		document.body.classList.add( 'woocommerce-onboarding' );
		document.body.classList.add( 'woocommerce-task-dashboard__body' );
	}, [] );

	const { task } = query;

	const { isResolving, taskLists } = useSelect( ( select ) => {
		return {
			taskLists: select( ONBOARDING_STORE_NAME ).getTaskLists(),
			isResolving: select( ONBOARDING_STORE_NAME ).isResolving(
				'getTaskLists'
			),
		};
	} );

	const getCurrentTask = () => {
		if ( ! task ) {
			return null;
		}

		const tasks = taskLists.reduce(
			( acc, taskList ) => [ ...acc, ...taskList.tasks ],
			[]
		);

		const currentTask = tasks.find( ( t ) => t.id === task );

		if ( ! currentTask ) {
			return null;
		}

		return currentTask;
	};

	const currentTask = getCurrentTask();

	if ( task && ! currentTask ) {
		return null;
	}

	if ( isResolving || isResolvingOptions ) {
		return <TaskListPlaceholder />;
	}

	const isSetupTaskListHidden = taskLists[ 0 ].isHidden;
	const setupTasks = taskLists[ 0 ].tasks.filter(
		( setupTask ) => setupTask.id !== 'store_details'
	);
	const completedTasks = setupTasks.filter(
		( setupTask ) => setupTask.isComplete
	);
	const isTaskListComplete = setupTasks.length === completedTasks.length;

	const dismissedTasks = setupTasks.filter(
		( setupTask ) => setupTask.isDismissed
	);

	if ( currentTask ) {
		return (
			<div className="woocommerce-task-dashboard__container">
				<Task query={ query } task={ currentTask } />
			</div>
		);
	}

	if ( ! taskLists[ 0 ].isVisible ) {
		return null;
	}

	return (
		<>
			{ setupTasks && ( ! isSetupTaskListHidden || task ) && (
				<TaskList
					taskListId={ taskLists[ 0 ].id }
					eventName="tasklist"
					twoColumns={ twoColumns }
					keepCompletedTaskList={ keepCompletedTaskList }
					dismissedTasks={ dismissedTasks || [] }
					isComplete={ isTaskListComplete }
					query={ query }
					tasks={ setupTasks }
					title={ __(
						'Get ready to start selling',
						'woocommerce-admin'
					) }
					onComplete={ () =>
						updateOptions( {
							woocommerce_default_homepage_layout: 'two_columns',
						} )
					}
					onHide={ () =>
						updateOptions( {
							woocommerce_default_homepage_layout: 'two_columns',
						} )
					}
				/>
			) }
		</>
	);
};

export default TaskDashboard;
