/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import { Button, Card, CardHeader } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { EllipsisMenu } from '@woocommerce/components';
import { updateQueryString } from '@woocommerce/navigation';
import { OPTIONS_STORE_NAME, ONBOARDING_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { List, TaskItem } from '@woocommerce/experimental';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import '../tasks/task-list.scss';
import taskHeaders from './task-headers';
import DismissModal from './dissmiss-modal';
import TaskListCompleted from './completed';

export const TaskList = ( {
	query,
	taskListId,
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
	const [ headerContent, setHeaderContent ] = useState( '' );
	const [ activeTaskId, setActiveTaskId ] = useState( '' );
	const [ showDismissModal, setShowDismissModal ] = useState( false );

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
	}, [ query ] );

	const nowTimestamp = Date.now();
	const visibleTasks = tasks.filter(
		( task ) =>
			! task.isDismissed &&
			( ! task.isSnoozed || task.snoozedUntil < nowTimestamp )
	);

	const incompleteTasks = tasks.filter(
		( task ) => ! task.isComplete && ! task.isDismissed
	);

	const onDismissTask = ( { id, onDismiss } ) => {
		dismissTask( id );
		createNotice( 'success', __( 'Task dismissed' ), {
			actions: [
				{
					label: __( 'Undo', 'woocommerce-admin' ),
					onClick: () => undoDismissTask( id ),
				},
			],
		} );

		if ( onDismiss ) {
			onDismiss();
		}
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

	const hideTasks = () => {
		hideTaskList( taskListId );
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
					label={ __( 'Task List Options', 'woocommerce-admin' ) }
					renderContent={ ( { onToggle } ) => (
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

	if ( ! selectedHeaderCard ) {
		selectedHeaderCard = visibleTasks[ 0 ];
	}

	const onTaskSelected = ( task ) => {
		if ( task !== selectedHeaderCard ) {
			recordEvent( `${ eventName }_click`, {
				task_name: task.id,
			} );

			updateQueryString( { task: task.id } );
		}
		if ( taskHeaders[ task.id ] ) {
			setHeaderContent( taskHeaders[ task.id ]( task ) );
			setActiveTaskId( task.id );
		}
	};

	useEffect( () => {
		if ( selectedHeaderCard ) {
			onTaskSelected( selectedHeaderCard );
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
					'woocommerce-task-dashboard__container two-column-experiment',
					{ 'two-columns': twoColumns !== false }
				) }
			>
				<Card
					size="large"
					className="woocommerce-task-card woocommerce-homescreen-card"
				>
					<CardHeader size="medium">
						<div className="wooocommerce-task-card__header">
							{ headerContent }
						</div>
						{ renderMenu() }
					</CardHeader>
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
									action={ task.action }
									actionLabel={ task.actionLabel }
									additionalInfo={ task.additionalInfo }
									showActionButton={ task.showActionButton }
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
