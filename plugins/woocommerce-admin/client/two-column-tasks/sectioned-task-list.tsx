/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import { Panel, PanelBody, PanelRow } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { Icon, check } from '@wordpress/icons';
import { updateQueryString } from '@woocommerce/navigation';
import { Badge } from '@woocommerce/components';
import {
	OPTIONS_STORE_NAME,
	ONBOARDING_STORE_NAME,
	TaskType,
	TaskListSection,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { List, TaskItem, Text } from '@woocommerce/experimental';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import '../tasks/task-list.scss';
import './sectioned-task-list.scss';
import TaskListCompleted from './completed';
import { TaskListProps } from '~/tasks/task-list';
import SectionHeader from './headers/section-header';
import { ProgressHeader } from '~/task-lists/progress-header';

type PanelBodyProps = Omit< PanelBody.Props, 'title' | 'onToggle' > & {
	title: string | React.ReactNode | undefined;
	onToggle?: ( isOpen: boolean ) => void;
};
const PanelBodyWithUpdatedType = PanelBody as React.ComponentType< PanelBodyProps >;

export const SectionedTaskList: React.FC< TaskListProps > = ( {
	query,
	id,
	eventName,
	tasks,
	keepCompletedTaskList,
	isComplete,
	sections,
	displayProgressHeader,
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
	const [ openPanel, setOpenPanel ] = useState< string | null >(
		sections?.find( ( section ) => ! section.isComplete )?.id || null
	);

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

	const hideTasks = () => {
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

	const onTaskSelected = ( task: TaskType ) => {
		goToTask( task );
	};

	const getSectionTasks = ( sectionTaskIds: string[] ) => {
		return visibleTasks.filter( ( task ) =>
			sectionTaskIds.includes( task.id )
		);
	};

	const getPanelTitle = ( section: TaskListSection ) => {
		return openPanel === section.id ? (
			<div className="wooocommerce-task-card__header-container">
				<div className="wooocommerce-task-card__header">
					<SectionHeader { ...section } />
				</div>
			</div>
		) : (
			<>
				<Text variant="title.small" size="20" lineHeight="28px">
					{ section.title }
				</Text>
				<Badge count={ section.tasks.length } />
				{ section.isComplete && (
					<div className="woocommerce-task__icon">
						<Icon icon={ check } />
					</div>
				) }
			</>
		);
	};

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
			{ displayProgressHeader ? (
				<ProgressHeader taskListId={ id } />
			) : null }
			<div
				className={ classnames(
					`woocommerce-task-dashboard__container woocommerce-sectioned-task-list two-column-experiment woocommerce-task-list__${ id }`
				) }
			>
				<Panel>
					{ ( sections || [] ).map( ( section ) => (
						<PanelBodyWithUpdatedType
							key={ section.id }
							title={ getPanelTitle( section ) }
							opened={ openPanel === section.id }
							onToggle={ ( isOpen: boolean ) => {
								if ( ! isOpen && openPanel === section.id ) {
									setOpenPanel( null );
								} else {
									setOpenPanel( section.id );
								}
							} }
							initialOpen={ false }
						>
							<PanelRow>
								<List animation="custom">
									{ getSectionTasks( section.tasks ).map(
										( task ) => {
											const className = classnames(
												'woocommerce-task-list__item',
												{
													complete: task.isComplete,
													'task-disabled':
														task.isDisabled,
												}
											);
											return (
												<TaskItem
													key={ task.id }
													className={ className }
													title={ task.title }
													completed={
														task.isComplete
													}
													expanded={
														! task.isComplete
													}
													content={ task.content }
													onClick={ () => {
														if (
															! task.isDisabled
														) {
															onTaskSelected(
																task
															);
														}
													} }
													onDismiss={
														task.isDismissable
															? () =>
																	onDismissTask(
																		task.id
																	)
															: undefined
													}
													action={ () => {} }
													actionLabel={
														task.actionLabel
													}
												/>
											);
										}
									) }
								</List>
							</PanelRow>
						</PanelBodyWithUpdatedType>
					) ) }
				</Panel>
			</div>
		</>
	);
};

export default SectionedTaskList;
