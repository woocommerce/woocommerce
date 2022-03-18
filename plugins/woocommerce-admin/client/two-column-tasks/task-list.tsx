/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState, createElement } from '@wordpress/element';
import { Button, Card } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { EllipsisMenu } from '@woocommerce/components';
import { updateQueryString } from '@woocommerce/navigation';
import {
	OPTIONS_STORE_NAME,
	ONBOARDING_STORE_NAME,
	TaskType,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { List, TaskItem } from '@woocommerce/experimental';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import '../tasks/task-list.scss';
import taskHeaders from './task-headers';
import DismissModal from './dismiss-modal';
import TaskListCompleted from './completed';
import { TaskListProps } from '~/tasks/task-list';

export const TaskList: React.FC< TaskListProps > = ( {
	query,
	id,
	eventName,
	tasks,
	twoColumns,
	keepCompletedTaskList,
	isComplete,
} ) => {
	const { createNotice } = useDispatch( 'core/notices' );
	const { updateOptions, dismissTask, undoDismissTask } = useDispatch(
		OPTIONS_STORE_NAME
	);
	const { profileItems } = useSelect( ( select ) => {
		const { getProfileItems } = select( ONBOARDING_STORE_NAME );
		return {
			profileItems: getProfileItems(),
		};
	} );
	const { hideTaskList } = useDispatch( ONBOARDING_STORE_NAME );
	const [ headerData, setHeaderData ] = useState< {
		task?: TaskType;
		goToTask?: () => void;
		trackClick?: () => void;
	} >( {} );
	const [ activeTaskId, setActiveTaskId ] = useState( '' );
	const [ showDismissModal, setShowDismissModal ] = useState( false );

	const prevQueryRef = useRef( query );

	const nowTimestamp = Date.now();
	const visibleTasks = tasks.filter(
		( task ) =>
			! task.isDismissed &&
			( ! task.isSnoozed || task.snoozedUntil < nowTimestamp )
	);

	const recordTaskListView = () => {
		if ( query.task ) {
			return;
		}

		recordEvent( `${ eventName }_view`, {
			number_tasks: visibleTasks.length,
			store_connected: profileItems.wccom_connected,
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

	const incompleteTasks = tasks.filter(
		( task ) => ! task.isComplete && ! task.isDismissed
	);

	const onDismissTask = ( taskId: string, onDismiss?: () => void ) => {
		dismissTask( taskId );
		createNotice( 'success', __( 'Task dismissed' ), {
			actions: [
				{
					label: __( 'Undo', 'woocommerce-admin' ),
					onClick: () => undoDismissTask( taskId ),
				},
			],
		} );

		if ( onDismiss ) {
			onDismiss();
		}
	};

	const hideTasks = ( event: string ) => {
		hideTaskList( id );
	};

	const keepTasks = () => {
		const updateOptionsParams = {
			woocommerce_task_list_keep_completed: 'yes',
		};

		updateOptions( {
			...updateOptionsParams,
		} );
	};

	const renderMenu = () => {
		return (
			<div className="woocommerce-card__menu woocommerce-card__header-item">
				<EllipsisMenu
					className={ id }
					label={ __( 'Task List Options', 'woocommerce-admin' ) }
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
										hideTasks( 'remove_card' );
									}
								} }
							>
								{ __( 'Hide this', 'woocommerce-admin' ) }
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

	const trackClick = ( task: TaskType ) => {
		recordEvent( `${ eventName }_click`, {
			task_name: task.id,
		} );
	};

	const goToTask = ( task: TaskType ) => {
		trackClick( task );
		updateQueryString( { task: task.id } );
	};

	const showTaskHeader = ( task: TaskType ) => {
		if ( taskHeaders[ task.id ] ) {
			setHeaderData( {
				task,
				goToTask: () => goToTask( task ),
				trackClick: () => trackClick( task ),
			} );
			setActiveTaskId( task.id );
		}
	};

	const onTaskSelected = ( task: TaskType ) => {
		if ( task.id === 'woocommerce-payments' ) {
			// With WCPay, we have to show the header content for user to read t&c first.
			showTaskHeader( task );
		} else {
			goToTask( task );
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

	if ( isComplete && ! keepCompletedTaskList ) {
		return (
			<>
				<TaskListCompleted
					hideTasks={ hideTasks }
					keepTasks={ keepTasks }
					twoColumns={ false }
				/>
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
			<div
				className={ classnames(
					`woocommerce-task-dashboard__container two-column-experiment woocommerce-task-list__${ id }`,
					{ 'two-columns': twoColumns !== false }
				) }
			>
				<Card
					size="large"
					className="woocommerce-task-card woocommerce-homescreen-card"
				>
					<div className="wooocommerce-task-card__header-container">
						<div className="wooocommerce-task-card__header">
							{ headerData?.task &&
								createElement(
									taskHeaders[ headerData.task.id ],
									headerData
								) }
						</div>
						{ renderMenu() }
					</div>
					<List animation="custom">
						{ visibleTasks.map( ( task, index ) => {
							++index;
							const className = classnames(
								'woocommerce-task-list__item index-' + index,
								{
									complete: task.isComplete,
									'is-active': task.id === activeTaskId,
								}
							);
							return (
								<TaskItem
									key={ task.id }
									className={ className }
									title={ task.title }
									completed={ task.isComplete }
									content={ task.content }
									onClick={ () => {
										onTaskSelected( task );
									} }
									onDismiss={
										task.isDismissable
											? () => onDismissTask( task.id )
											: undefined
									}
									action={ () => {} }
									actionLabel={ task.actionLabel }
								/>
							);
						} ) }
					</List>
				</Card>
			</div>
		</>
	);
};

export default TaskList;
