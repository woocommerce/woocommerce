/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import { Button, Card, CardBody, CardHeader } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { EllipsisMenu, Badge } from '@woocommerce/components';
import { updateQueryString } from '@woocommerce/navigation';
import { OPTIONS_STORE_NAME, ONBOARDING_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import {
	Text,
	List,
	CollapsibleList,
	TaskItem,
} from '@woocommerce/experimental';

export const TaskList = ( {
	query,
	name,
	eventName,
	isComplete,
	dismissedTasks,
	tasks,
	trackedCompletedTasks: totalTrackedCompletedTasks,
	title: listTitle,
	collapsible = false,
	onComplete,
	onHide,
	expandingItems = false,
} ) => {
	const { createNotice } = useDispatch( 'core/notices' );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { profileItems } = useSelect( ( select ) => {
		const { getProfileItems } = select( ONBOARDING_STORE_NAME );

		return {
			profileItems: getProfileItems(),
		};
	} );

	const prevQueryRef = useRef( query );
	useEffect( () => {
		recordTaskListView();
	}, [] );

	useEffect( () => {
		const { task: prevTask } = prevQueryRef.current;
		const { task } = query;

		if ( prevTask !== task ) {
			window.document.documentElement.scrollTop = 0;
			prevQueryRef.current = query;
		}

		possiblyCompleteTaskList();
		possiblyTrackCompletedTasks();
	}, [ query ] );

	const visibleTasks = tasks.filter(
		( task ) => task.visible && ! dismissedTasks.includes( task.key )
	);

	const completedTaskKeys = visibleTasks
		.filter( ( task ) => task.completed )
		.map( ( task ) => task.key );

	const incompleteTasks = tasks.filter(
		( task ) =>
			task.visible &&
			! task.completed &&
			! dismissedTasks.includes( task.key )
	);

	const [ currentTask, setCurrentTask ] = useState(
		incompleteTasks[ 0 ]?.key
	);

	const possiblyCompleteTaskList = () => {
		const taskListVariableName = `woocommerce_${ name }_complete`;
		const taskListToComplete = isComplete
			? { [ taskListVariableName ]: 'no' }
			: { [ taskListVariableName ]: 'yes' };

		if (
			( ! incompleteTasks.length && ! isComplete ) ||
			( incompleteTasks.length && isComplete )
		) {
			updateOptions( {
				...taskListToComplete,
			} );

			if ( typeof onComplete === 'function' ) {
				onComplete();
			}
		}
	};

	const getTrackedIncompletedTasks = (
		partialCompletedTasks,
		allTrackedTask
	) => {
		return visibleTasks
			.filter(
				( task ) =>
					allTrackedTask.includes( task.key ) &&
					! partialCompletedTasks.includes( task.key )
			)
			.map( ( task ) => task.key );
	};

	const possiblyTrackCompletedTasks = () => {
		const trackedCompletedTasks = getTrackedCompletedTasks(
			completedTaskKeys,
			totalTrackedCompletedTasks
		);

		const trackedIncompleteTasks = getTrackedIncompletedTasks(
			trackedCompletedTasks,
			totalTrackedCompletedTasks
		);

		if (
			shouldUpdateCompletedTasks(
				trackedCompletedTasks,
				trackedIncompleteTasks,
				completedTaskKeys
			)
		) {
			updateOptions( {
				woocommerce_task_list_tracked_completed_tasks: getTasksForUpdate(
					completedTaskKeys,
					totalTrackedCompletedTasks,
					trackedIncompleteTasks
				),
			} );
		}
	};

	const dismissTask = ( { key, onDismiss } ) => {
		createNotice( 'success', __( 'Task dismissed' ), {
			actions: [
				{
					label: __( 'Undo', 'woocommerce-admin' ),
					onClick: () => undoDismissTask( key ),
				},
			],
		} );

		recordEvent( 'tasklist_dismiss_task', { task_name: key } );

		updateOptions( {
			woocommerce_task_list_dismissed_tasks: [ ...dismissedTasks, key ],
		} );
		if ( onDismiss ) {
			onDismiss();
		}
	};

	const undoDismissTask = ( key ) => {
		const updatedDismissedTasks = dismissedTasks.filter(
			( task ) => task !== key
		);

		updateOptions( {
			woocommerce_task_list_dismissed_tasks: updatedDismissedTasks,
		} );
	};

	const recordTaskListView = () => {
		if ( query.task ) {
			return;
		}

		recordEvent( `${ eventName }_view`, {
			number_tasks: visibleTasks.length,
			store_connected: profileItems.wccom_connected,
		} );
	};

	const hideTaskCard = ( action ) => {
		const updateOptionsParams = {
			[ `woocommerce_${ name }_hidden` ]: 'yes',
		};

		recordEvent( `${ eventName }_completed`, {
			action,
			completed_task_count: completedTaskKeys.length,
			incomplete_task_count: incompleteTasks.length,
		} );
		updateOptions( {
			...updateOptionsParams,
		} );

		if ( typeof onHide === 'function' ) {
			onHide();
		}
	};

	const renderMenu = () => {
		return (
			<div className="woocommerce-card__menu woocommerce-card__header-item">
				<EllipsisMenu
					label={ __( 'Task List Options', 'woocommerce-admin' ) }
					renderContent={ () => (
						<div className="woocommerce-task-card__section-controls">
							<Button
								onClick={ () => hideTaskCard( 'remove_card' ) }
							>
								{ __( 'Hide this', 'woocommerce-admin' ) }
							</Button>
						</div>
					) }
				/>
			</div>
		);
	};

	const listTasks = visibleTasks.map( ( task ) => {
		if ( ! task.onClick ) {
			task.onClick = ( e ) => {
				if ( e.target.nodeName === 'A' ) {
					// This is a nested link, so don't activate this task.
					return false;
				}

				updateQueryString( { task: task.key } );
			};
		}

		return task;
	} );

	if ( ! listTasks.length ) {
		return <div className="woocommerce-task-dashboard__container"></div>;
	}

	const expandLabel = sprintf(
		/* translators: %i = number of hidden tasks */
		_n(
			'Show %i more task.',
			'Show %i more tasks.',
			listTasks.length - 2,
			'woocommerce-admin'
		),
		listTasks.length - 2
	);
	const collapseLabel = __( 'Show less', 'woocommerce-admin' );
	const ListComp = collapsible ? CollapsibleList : List;

	const listProps = collapsible
		? {
				collapseLabel,
				expandLabel,
				show: 2,
				onCollapse: () => recordEvent( `${ eventName }_collapse` ),
				onExpand: () => recordEvent( `${ eventName }_expand` ),
		  }
		: {};

	return (
		<>
			<div className="woocommerce-task-dashboard__container">
				<Card
					size="large"
					className="woocommerce-task-card woocommerce-homescreen-card"
				>
					<CardHeader size="medium">
						<div className="wooocommerce-task-card__header">
							<Text variant="title.small">{ listTitle }</Text>
							<Badge count={ incompleteTasks.length } />
						</div>
						{ renderMenu() }
					</CardHeader>
					<CardBody>
						<ListComp animation="slide-right" { ...listProps }>
							{ listTasks.map( ( task ) => (
								<TaskItem
									key={ task.key }
									title={ task.title }
									completed={ task.completed }
									content={ task.content }
									onClick={
										! expandingItems || task.completed
											? task.onClick
											: () => setCurrentTask( task.key )
									}
									expanded={
										expandingItems &&
										currentTask === task.key
									}
									isDismissable={ task.isDismissable }
									onDismiss={ () => dismissTask( task ) }
									time={ task.time }
									level={ task.level }
									action={ task.onClick }
									actionLabel={ task.action }
									additionalInfo={ task.additionalInfo }
								/>
							) ) }
						</ListComp>
					</CardBody>
				</Card>
			</div>
		</>
	);
};

function shouldUpdateCompletedTasks( tasks, untrackedTasks, completedTasks ) {
	if ( untrackedTasks.length > 0 ) {
		return true;
	}
	if ( completedTasks.length === 0 ) {
		return false;
	}
	return ! completedTasks.every(
		( taskName ) => tasks.indexOf( taskName ) >= 0
	);
}

function getTrackedCompletedTasks( completedTasks, trackedTasks ) {
	if ( ! trackedTasks ) {
		return [];
	}
	return completedTasks.filter( ( taskName ) =>
		trackedTasks.includes( taskName )
	);
}

function getTasksForUpdate(
	completedTaskKeys,
	totalTrackedCompletedTasks,
	trackedIncompleteTasks
) {
	const mergedLists = [
		...new Set( [ ...completedTaskKeys, ...totalTrackedCompletedTasks ] ),
	];
	return mergedLists.filter(
		( taskName ) => ! trackedIncompleteTasks.includes( taskName )
	);
}

export default TaskList;
