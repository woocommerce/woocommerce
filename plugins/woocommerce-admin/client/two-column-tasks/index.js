/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { ONBOARDING_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import '../tasks/tasks.scss';
import './style.scss';
import TaskList from './task-list';
import TaskListPlaceholder from './placeholder';
import { Task } from '../tasks/task';

const TaskDashboard = ( { query, twoColumns } ) => {
	const { task } = query;

	const { isResolving, taskLists } = useSelect( ( select ) => {
		return {
			taskLists: select( ONBOARDING_STORE_NAME ).getTaskListsByIds( [
				'setup_two_column',
				'extended_two_column',
			] ),
			isResolving: select( ONBOARDING_STORE_NAME ).isResolving(
				'getTaskListsByIds'
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

	if ( isResolving || ! taskLists[ 0 ] ) {
		return <TaskListPlaceholder twoColumns={ twoColumns } />;
	}

	if ( currentTask ) {
		return (
			<div className="woocommerce-task-dashboard__container">
				<Task query={ query } task={ currentTask } />
			</div>
		);
	}
	// List of task items to be shown on the main task list.
	// Any other remaining tasks will be moved to the extended task list.
	const taskList = taskLists[ 0 ];
	const setupTasks = taskList.tasks;

	const completedTasks = setupTasks.filter(
		( setupTask ) => setupTask.isComplete
	);
	const isTaskListComplete = setupTasks.length === completedTasks.length;

	const dismissedTasks = setupTasks.filter(
		( setupTask ) => setupTask.isDismissed
	);

	return (
		<>
			{ setupTasks && ( taskList.isVisible || task ) && (
				<TaskList
					id={ taskList.id }
					eventName="tasklist"
					twoColumns={ twoColumns }
					keepCompletedTaskList={ taskList.keepCompletedTaskList }
					dismissedTasks={ dismissedTasks || [] }
					isComplete={ isTaskListComplete }
					query={ query }
					cesHeader={ false }
					tasks={ setupTasks }
					title={ __( 'Get ready to start selling', 'woocommerce' ) }
				/>
			) }
		</>
	);
};

export default TaskDashboard;
