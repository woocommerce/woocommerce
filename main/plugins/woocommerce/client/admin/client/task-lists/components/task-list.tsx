/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import { Button, Card, CardHeader } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { Badge, H } from '@woocommerce/components';
import {
	getVisibleTasks,
	onboardingStore,
	TaskType,
	TaskListType,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import {
	Text,
	List,
	ListItem,
	CollapsibleList,
} from '@woocommerce/experimental';
import { useLayoutContext } from '@woocommerce/admin-layout';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { TaskListItem } from './task-list-item';
import { TaskListMenu } from './task-list-menu';
import './task-list.scss';
import ChecklistImage from './checklist.svg';
import { ProgressHeader } from '~/task-lists/components/progress-header';

export type TaskListProps = TaskListType & {
	query: {
		task?: string;
	};
	eventName?: string;
	keepCompletedTaskList?: 'yes' | 'no';
	cesHeader?: boolean;
};

type DismissedTask = Pick< TaskType, 'id' | 'title' >;

// TaskLists::maybe_add_extended_tasks() treats any list ID beginning with
// `extended` as an extended list, so the client has to match the same way.
const isExtendedTaskListId = ( taskListId: string ) =>
	taskListId.startsWith( 'extended' );

export const TaskList = ( {
	id,
	eventPrefix,
	tasks,
	title: listTitle,
	isCollapsible = false,
	isExpandable = false,
	displayProgressHeader = false,
	query,
}: TaskListProps ) => {
	const { dismissTask, undoDismissTask } = useDispatch( onboardingStore );
	const { createNotice } = useDispatch( 'core/notices' );
	const { profileItems } = useSelect( ( select ) => {
		const { getProfileItems } = select( onboardingStore );

		return {
			profileItems: getProfileItems(),
		};
	}, [] );
	const prevQueryRef = useRef( query );
	const [ dismissedTasks, setDismissedTasks ] = useState<
		Record< string, DismissedTask >
	>( {} );
	const pendingTaskRequestsRef = useRef( new Set< string >() );
	const [ pendingTaskRequests, setPendingTaskRequests ] = useState<
		Record< string, boolean >
	>( {} );
	const isExtendedTaskList = isExtendedTaskListId( id );
	const visibleTasks = getVisibleTasks( tasks ).filter( ( task ) => {
		if ( dismissedTasks[ task.id ] ) {
			return false;
		}

		return ! isExtendedTaskList || ! task.isComplete;
	} );
	const taskIdsToRender = new Set( [
		...visibleTasks.map( ( task ) => task.id ),
		...Object.keys( dismissedTasks ),
	] );
	const displayTasks = tasks.filter( ( task ) =>
		taskIdsToRender.has( task.id )
	);
	const dismissedTaskCount = Object.keys( dismissedTasks ).length;
	const shouldShowEmptyState =
		isExtendedTaskList && ! visibleTasks.length && ! dismissedTaskCount;
	const { layoutString } = useLayoutContext();

	const incompleteTasks = tasks.filter(
		( task ) =>
			! task.isComplete &&
			! task.isDismissed &&
			! dismissedTasks[ task.id ]
	);

	const [ expandedTask, setExpandedTask ] = useState(
		incompleteTasks[ 0 ]?.id
	);

	const recordTaskListView = () => {
		recordEvent( eventPrefix + 'view', {
			number_tasks: visibleTasks.length,
			store_connected: profileItems.wccom_connected,
			context: layoutString,
		} );
	};

	const trackClick = ( task: TaskListProps[ 'tasks' ][ number ] ) => {
		recordEvent( eventPrefix + 'task_click', {
			task_name: task.id,
			task_complete: task.isComplete,
			task_dismissed: task.isDismissed,
			context: layoutString,
		} );
	};

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
	}, [ query ] );

	if ( ! displayTasks.length && ! shouldShowEmptyState ) {
		return <div className="woocommerce-task-dashboard__container"></div>;
	}

	const expandLabel = sprintf(
		/* translators: %d = number of hidden tasks */
		_n(
			'Show %d more task.',
			'Show %d more tasks.',
			visibleTasks.length - 2,
			'woocommerce'
		),
		visibleTasks.length - 2
	);
	const collapseLabel = __( 'Show less', 'woocommerce' );

	const addDismissedTask = ( task: DismissedTask ) => {
		setDismissedTasks( ( currentDismissedTasks ) => ( {
			...currentDismissedTasks,
			[ task.id ]: {
				id: task.id,
				title: task.title,
			},
		} ) );
	};

	const removeDismissedTask = ( taskId: string ) => {
		setDismissedTasks( ( currentDismissedTasks ) => {
			const updatedDismissedTasks = { ...currentDismissedTasks };
			delete updatedDismissedTasks[ taskId ];
			return updatedDismissedTasks;
		} );
	};

	const beginTaskRequest = ( taskId: string ) => {
		if ( pendingTaskRequestsRef.current.has( taskId ) ) {
			return false;
		}

		pendingTaskRequestsRef.current.add( taskId );
		setPendingTaskRequests( ( currentRequests ) => ( {
			...currentRequests,
			[ taskId ]: true,
		} ) );
		return true;
	};

	const endTaskRequest = ( taskId: string ) => {
		pendingTaskRequestsRef.current.delete( taskId );
		setPendingTaskRequests( ( currentRequests ) => {
			const updatedRequests = { ...currentRequests };
			delete updatedRequests[ taskId ];
			return updatedRequests;
		} );
	};

	const onTaskSkip = async ( task: TaskType ) => {
		if ( ! beginTaskRequest( task.id ) ) {
			return;
		}

		addDismissedTask( task );
		try {
			await dismissTask( task.id, id );
		} catch {
			removeDismissedTask( task.id );
			createNotice(
				'error',
				__(
					'There was a problem skipping this task. Please try again.',
					'woocommerce'
				)
			);
		} finally {
			endTaskRequest( task.id );
		}
	};

	const onUndoDismiss = async ( task: DismissedTask ) => {
		if ( ! beginTaskRequest( task.id ) ) {
			return;
		}

		removeDismissedTask( task.id );
		try {
			await undoDismissTask( task.id, id );
		} catch {
			addDismissedTask( task );
			createNotice(
				'error',
				__(
					'There was a problem restoring this task. Please try again.',
					'woocommerce'
				)
			);
		} finally {
			endTaskRequest( task.id );
		}
	};

	const taskListItems = displayTasks.map( ( task ) => {
		if ( dismissedTasks[ task.id ] ) {
			return (
				<ListItem
					key={ task.id }
					disableGutters
					className="woocommerce-task-list__item woocommerce-task-list__item--dismissed"
				>
					<div className="woocommerce-task-list__item-before">
						<div className="woocommerce-task__icon"></div>
					</div>
					<div className="woocommerce-task-list__item-text">
						<Text
							as="div"
							size="14"
							lineHeight="20px"
							variant="body.small"
							className="woocommerce-task-list__item-removed-message"
						>
							{ __(
								"This suggestion has been removed and won't be shown again.",
								'woocommerce'
							) }
						</Text>
					</div>
					<div className="woocommerce-task-list__item-after">
						<Button
							className="woocommerce-task-list__item-undo"
							disabled={ pendingTaskRequests[ task.id ] }
							variant="link"
							onClick={ (
								event: React.MouseEvent | React.KeyboardEvent
							) => {
								event.preventDefault();
								event.stopPropagation();
								void onUndoDismiss( dismissedTasks[ task.id ] );
							} }
							onKeyDown={ ( event: React.KeyboardEvent ) =>
								event.stopPropagation()
							}
						>
							{ __( 'Undo', 'woocommerce' ) }
						</Button>
					</div>
				</ListItem>
			);
		}

		return (
			<TaskListItem
				key={ task.id }
				isExpanded={ expandedTask === task.id }
				isExpandable={ isExpandable }
				task={ task }
				setExpandedTask={ setExpandedTask }
				isSkipDisabled={ pendingTaskRequests[ task.id ] }
				showSkipAction={ isExtendedTaskList }
				onTaskSkip={ onTaskSkip }
				trackClick={ () => trackClick( task ) }
			/>
		);
	} );

	let taskListContent = <List animation="custom">{ taskListItems }</List>;

	if ( shouldShowEmptyState ) {
		taskListContent = (
			<div className="woocommerce-task-list__empty-state">
				<img
					className="woocommerce-task-list__empty-state-image"
					src={ ChecklistImage }
					alt=""
				/>
				<H>{ __( "You're all caught up", 'woocommerce' ) }</H>
				<p>
					{ __(
						"You've completed all the things to do next. Watch this space for more recommendations.",
						'woocommerce'
					) }
				</p>
			</div>
		);
	} else if ( isCollapsible ) {
		taskListContent = (
			<CollapsibleList
				animation="custom"
				collapseLabel={ collapseLabel }
				expandLabel={ expandLabel }
				show={ 2 }
				onCollapse={ () => recordEvent( eventPrefix + 'collapse', {} ) }
				onExpand={ () => recordEvent( eventPrefix + 'expand', {} ) }
			>
				{ taskListItems }
			</CollapsibleList>
		);
	}

	return (
		<>
			<div
				className={ clsx(
					'woocommerce-task-dashboard__container',
					`woocommerce-task-list__${ id }`,
					{
						'woocommerce-task-list--extended': isExtendedTaskList,
					}
				) }
			>
				{ displayProgressHeader ? (
					<ProgressHeader taskListId={ id } />
				) : null }
				<Card
					size="large"
					className="woocommerce-task-card woocommerce-homescreen-card"
				>
					<CardHeader size="medium">
						<div className="woocommerce-task-card__header">
							<Text
								size="20"
								lineHeight="28px"
								variant="title.small"
							>
								{ listTitle }
							</Text>
							<Badge count={ incompleteTasks.length } />
						</div>
						<TaskListMenu id={ id } />
					</CardHeader>
					{ taskListContent }
				</Card>
			</div>
		</>
	);
};

export default TaskList;
