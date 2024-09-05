/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState, createElement } from '@wordpress/element';
import { Button, Card } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { EllipsisMenu } from '@woocommerce/components';
import { navigateTo, getNewPath } from '@woocommerce/navigation';
import { WooOnboardingTaskListHeader } from '@woocommerce/onboarding';
import {
	ONBOARDING_STORE_NAME,
	TaskType,
	useUserPreferences,
	getVisibleTasks,
	TaskListType,
	WCDataSelector,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { List, useSlot } from '@woocommerce/experimental';
import clsx from 'clsx';
import { useLayoutContext } from '@woocommerce/admin-layout';

/**
 * Internal dependencies
 */
import { taskHeaders } from './components/task-headers';
import DismissModal from './components/dismiss-modal';
import TaskListCompleted from './components/task-list-completed';
import { ProgressHeader } from '~/task-lists/components/progress-header';
import { TaskListItem } from './components/task-list-item';
import { TaskListCompletedHeader } from './components/task-list-completed-header';

import { ExperimentalWooTaskListFooter } from './footer-slot';
import {
	TaskListCompletionSlot,
	EXPERIMENTAL_WC_TASK_LIST_COMPLETION_SLOT_NAME,
} from './task-list-completion-slot';

export type TaskListProps = TaskListType & {
	eventName?: string;
	query: {
		task?: string;
	};
	cesHeader?: boolean;
};

export const SetupTaskList: React.FC< TaskListProps > = ( {
	query,
	id,
	eventName,
	eventPrefix,
	tasks,
	keepCompletedTaskList,
	isComplete,
	displayProgressHeader,
	cesHeader = true,
} ) => {
	const listEventPrefix = eventName ? eventName + '_' : eventPrefix;
	const { profileItems } = useSelect( ( select: WCDataSelector ) => {
		const { getProfileItems } = select( ONBOARDING_STORE_NAME );
		return {
			profileItems: getProfileItems(),
		};
	} );
	const {
		hideTaskList,
		visitedTask,
		keepCompletedTaskList: keepCompletedTasks,
	} = useDispatch( ONBOARDING_STORE_NAME );
	const userPreferences = useUserPreferences();
	const [ headerData, setHeaderData ] = useState< {
		task?: TaskType;
		goToTask?: () => void;
		trackClick?: () => void;
	} >( {} );
	const [ activeTaskId, setActiveTaskId ] = useState( '' );
	const [ showDismissModal, setShowDismissModal ] = useState( false );
	const { layoutString } = useLayoutContext();

	const prevQueryRef = useRef( query );

	const visibleTasks = getVisibleTasks( tasks );
	const recordTaskListView = () => {
		if ( query.task ) {
			return;
		}

		recordEvent( `${ listEventPrefix }view`, {
			number_tasks: visibleTasks.length,
			store_connected: profileItems.wccom_connected,
			context: layoutString,
		} );
	};

	useEffect( () => {
		recordTaskListView();
	}, [] );

	const taskListCompletionSlot = useSlot(
		EXPERIMENTAL_WC_TASK_LIST_COMPLETION_SLOT_NAME
	);

	useEffect( () => {
		const { task: prevTask } = prevQueryRef.current;
		const { task } = query;

		if ( prevTask !== task ) {
			window.document.documentElement.scrollTop = 0;
			prevQueryRef.current = query;
		}
	}, [ query ] );

	const incompleteTasks = tasks.filter(
		( task ) => ! task.isComplete && ! task.isDismissed
	);

	const hideTasks = () => {
		hideTaskList( id );
	};

	const keepTasks = () => {
		keepCompletedTasks( id );
	};

	const renderMenu = () => {
		return (
			<div className="woocommerce-card__menu woocommerce-card__header-item">
				<EllipsisMenu
					className={ id }
					label={ __( 'Task List Options', 'woocommerce' ) }
					renderContent={ ( {
						onToggle,
					}: {
						onToggle: () => void;
					} ) => (
						<div className="woocommerce-task-card__section-controls">
							<Button
								onClick={ () => {
									if ( incompleteTasks.length > 0 ) {
										setShowDismissModal( true );
										onToggle();
									} else {
										hideTasks();
									}
								} }
							>
								{ __( 'Hide this', 'woocommerce' ) }
							</Button>
						</div>
					) }
				/>
			</div>
		);
	};

	let selectedHeaderCard = visibleTasks.find(
		( listTask ) => listTask.isComplete === false
	);

	// If nothing is selected, default to the last task since everything is completed.
	if ( ! selectedHeaderCard ) {
		selectedHeaderCard = visibleTasks[ visibleTasks.length - 1 ];
	}

	const taskListHeaderSlot = useSlot(
		`woocommerce_onboarding_task_list_header_${
			headerData?.task?.id ?? selectedHeaderCard?.id
		}`
	);
	const hasTaskListHeaderSlotFills = Boolean(
		taskListHeaderSlot?.fills?.length
	);

	const getTaskStartedCount = ( taskId: string ) => {
		const trackedStartedTasks =
			userPreferences.task_list_tracked_started_tasks;
		if ( ! trackedStartedTasks || ! trackedStartedTasks[ taskId ] ) {
			return 0;
		}
		return trackedStartedTasks[ taskId ];
	};

	// @todo This would be better as a task endpoint that handles updating the count.
	const updateTrackStartedCount = ( taskId: string ) => {
		const newCount = getTaskStartedCount( taskId ) + 1;
		const trackedStartedTasks =
			userPreferences.task_list_tracked_started_tasks || {};

		visitedTask( taskId );
		userPreferences.updateUserPreferences( {
			task_list_tracked_started_tasks: {
				...( trackedStartedTasks || {} ),
				[ taskId ]: newCount,
			},
		} );
	};

	const trackClick = ( task: TaskType ) => {
		recordEvent( `${ listEventPrefix }click`, {
			task_name: task.id,
			context: layoutString,
		} );
	};

	const goToTask = ( task: TaskType ) => {
		trackClick( task );
		if ( ! task.isComplete ) {
			updateTrackStartedCount( task.id );
		}

		if ( task.actionUrl ) {
			navigateTo( {
				url: task.actionUrl,
			} );
			return;
		}

		navigateTo( { url: getNewPath( { task: task.id }, '/', {} ) } );
	};

	const showTaskHeader = ( task: TaskType ) => {
		if ( taskHeaders[ task.id ] || hasTaskListHeaderSlotFills ) {
			setHeaderData( {
				task,
				goToTask: () => goToTask( task ),
				trackClick: () => trackClick( task ),
			} );
			setActiveTaskId( task.id );
		}
	};

	useEffect( () => {
		if ( selectedHeaderCard ) {
			showTaskHeader( selectedHeaderCard );
		}
	}, [ selectedHeaderCard ] );

	if ( ! visibleTasks.length ) {
		return <div className="woocommerce-task-dashboard__container"></div>;
	}

	const hasTaskListCompletionSlotFills = Boolean(
		taskListCompletionSlot?.fills?.length
	);

	if ( isComplete && keepCompletedTaskList !== 'yes' ) {
		if ( hasTaskListCompletionSlotFills ) {
			return (
				<TaskListCompletionSlot
					fillProps={ {
						hideTasks,
						keepTasks,
						customerEffortScore: cesHeader,
					} }
				/>
			);
		}
		return (
			<>
				{ cesHeader ? (
					<TaskListCompletedHeader
						hideTasks={ hideTasks }
						keepTasks={ keepTasks }
						customerEffortScore={ true }
					/>
				) : (
					<TaskListCompleted
						hideTasks={ hideTasks }
						keepTasks={ keepTasks }
					/>
				) }
			</>
		);
	}

	return (
		<>
			{ showDismissModal && (
				<DismissModal
					showDismissModal={ showDismissModal }
					setShowDismissModal={ setShowDismissModal }
					hideTasks={ hideTasks }
				/>
			) }
			{ displayProgressHeader ? (
				<ProgressHeader taskListId={ id } />
			) : null }
			<div
				className={ clsx(
					`woocommerce-task-dashboard__container woocommerce-task-list__${ id } setup-task-list`
				) }
			>
				<Card
					size="large"
					className="woocommerce-task-card woocommerce-homescreen-card"
				>
					<div className="woocommerce-task-card__header-container">
						<div className="woocommerce-task-card__header">
							{ hasTaskListHeaderSlotFills ? (
								<WooOnboardingTaskListHeader.Slot
									id={ selectedHeaderCard?.id }
									fillProps={ headerData }
								/>
							) : (
								headerData?.task &&
								createElement(
									taskHeaders[ headerData.task.id ],
									headerData
								)
							) }
						</div>
						{ ! displayProgressHeader && renderMenu() }
					</div>
					<List animation="custom">
						{ visibleTasks.map( ( task, index ) => {
							return (
								<TaskListItem
									key={ task.id }
									taskIndex={ ++index }
									activeTaskId={ activeTaskId }
									task={ task }
									goToTask={ () => goToTask( task ) }
									trackClick={ () => trackClick( task ) }
								/>
							);
						} ) }
					</List>
					<ExperimentalWooTaskListFooter />
				</Card>
			</div>
		</>
	);
};

export default SetupTaskList;
