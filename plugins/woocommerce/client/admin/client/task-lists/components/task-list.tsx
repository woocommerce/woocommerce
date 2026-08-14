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
import { Text, List, CollapsibleList } from '@woocommerce/experimental';
import { useLayoutContext } from '@woocommerce/admin-layout';

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
	const { undoDismissTask } = useDispatch( onboardingStore );
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
	const visibleTasks = getVisibleTasks( tasks ).filter( ( task ) => {
		if ( dismissedTasks[ task.id ] ) {
			return false;
		}

		return id !== 'extended' || ! task.isComplete;
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
		id === 'extended' && ! visibleTasks.length && ! dismissedTaskCount;
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

	const onTaskDismissed = ( task: TaskType ) => {
		addDismissedTask( task );
	};

	const onTaskDismissFailed = ( task: TaskType ) => {
		removeDismissedTask( task.id );
	};

	const onUndoDismiss = ( task: DismissedTask ) => {
		removeDismissedTask( task.id );
		void Promise.resolve( undoDismissTask( task.id ) ).catch( () => {
			addDismissedTask( task );
			createNotice(
				'error',
				__(
					'There was a problem restoring this task. Please try again.',
					'woocommerce'
				)
			);
		} );
	};

	const taskListItems = displayTasks.map( ( task ) => {
		if ( dismissedTasks[ task.id ] ) {
			return (
				<div
					key={ task.id }
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
							variant="link"
							onClick={ (
								event: React.MouseEvent | React.KeyboardEvent
							) => {
								event.preventDefault();
								event.stopPropagation();
								onUndoDismiss( dismissedTasks[ task.id ] );
							} }
						>
							{ __( 'Undo', 'woocommerce' ) }
						</Button>
					</div>
				</div>
			);
		}

		return (
			<TaskListItem
				key={ task.id }
				isExpanded={ expandedTask === task.id }
				isExpandable={ isExpandable }
				task={ task }
				setExpandedTask={ setExpandedTask }
				showSkipAction={ id === 'extended' }
				onTaskDismissed={ onTaskDismissed }
				onTaskDismissFailed={ onTaskDismissFailed }
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
						"There's nothing else to do right now. Watch this space for more recommendations.",
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
				className={
					'woocommerce-task-dashboard__container woocommerce-task-list__' +
					id
				}
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
