/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import {
	ONBOARDING_STORE_NAME,
	TaskType,
	useUserPreferences,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { TaskItem, useSlot } from '@woocommerce/experimental';
import { useCallback, useEffect } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { WooOnboardingTaskListItem } from '@woocommerce/onboarding';
import { useLayoutContext } from '@woocommerce/admin-layout';

/**
 * Internal dependencies
 */
import './task-list.scss';

export type TaskListItemProps = {
	isExpandable: boolean;
	isExpanded: boolean;
	setExpandedTask: ( id: string ) => void;
	task: TaskType & {
		onClick?: () => void;
	};
};

export const TaskListItem: React.FC< TaskListItemProps > = ( {
	isExpandable = false,
	isExpanded = false,
	setExpandedTask,
	task,
} ) => {
	const { createNotice } = useDispatch( 'core/notices' );
	const { layoutString } = useLayoutContext();

	const {
		dismissTask,
		snoozeTask,
		undoDismissTask,
		undoSnoozeTask,
		visitedTask,
		invalidateResolutionForStoreSelector,
	} = useDispatch( ONBOARDING_STORE_NAME );
	const userPreferences = useUserPreferences();

	const {
		actionLabel,
		actionUrl,
		content,
		id,
		isComplete,
		isDismissable,
		isSnoozeable,
		time,
		title,
		badge,
		level,
		additionalInfo,
		recordViewEvent,
	} = task;

	useEffect( () => {
		if ( recordViewEvent ) {
			recordEvent( 'tasklist_item_view', {
				task_name: id,
				is_complete: isComplete,
				context: layoutString,
			} );
		}
		// run the effect only when component mounts
		// eslint-disable-next-line
	}, [] );

	const slot = useSlot( `woocommerce_onboarding_task_list_item_${ id }` );
	const hasFills = Boolean( slot?.fills?.length );

	const onDismiss = useCallback( () => {
		dismissTask( id );
		createNotice( 'success', __( 'Task dismissed', 'woocommerce' ), {
			actions: [
				{
					label: __( 'Undo', 'woocommerce' ),
					onClick: () => undoDismissTask( id ),
				},
			],
		} );
	}, [ id ] );

	const onSnooze = useCallback( () => {
		snoozeTask( id );
		createNotice(
			'success',
			__( 'Task postponed until tomorrow', 'woocommerce' ),
			{
				actions: [
					{
						label: __( 'Undo', 'woocommerce' ),
						onClick: () => undoSnoozeTask( id ),
					},
				],
			}
		);
	}, [ id ] );

	const getTaskStartedCount = () => {
		const trackedStartedTasks =
			userPreferences.task_list_tracked_started_tasks;
		if ( ! trackedStartedTasks || ! trackedStartedTasks[ id ] ) {
			return 0;
		}
		return trackedStartedTasks[ id ];
	};

	// @todo This would be better as a task endpoint that handles updating the count.
	const updateTrackStartedCount = async () => {
		const newCount = getTaskStartedCount() + 1;
		const trackedStartedTasks =
			userPreferences.task_list_tracked_started_tasks || {};

		visitedTask( id );
		await userPreferences.updateUserPreferences( {
			task_list_tracked_started_tasks: {
				...( trackedStartedTasks || {} ),
				[ id ]: newCount,
			},
		} );
	};

	const trackClick = async () => {
		recordEvent( 'tasklist_click', {
			task_name: id,
			context: layoutString,
		} );

		if ( ! isComplete ) {
			await updateTrackStartedCount();
		}
	};

	const onClickDefault = useCallback( () => {
		if ( actionUrl ) {
			navigateTo( {
				url: actionUrl,
			} );
			return;
		}

		navigateTo( { url: getNewPath( { task: id }, '/', {} ) } );
	}, [ id, isComplete, actionUrl ] );

	const taskItemProps = {
		expandable: isExpandable,
		expanded: isExpandable && isExpanded,
		completed: isComplete,
		onSnooze: isSnoozeable && onSnooze,
		onDismiss: isDismissable && onDismiss,
	};

	const DefaultTaskItem = useCallback(
		( props ) => {
			const onClickActions = () => {
				trackClick().then( () => {
					if ( ! isComplete ) {
						// Invalidate the task list selector cache to force a re-fetch.
						// This ensures the task completion status is up-to-date after visiting a task.
						invalidateResolutionForStoreSelector( 'getTaskLists' );
					}
				} );

				if ( props.onClick ) {
					return props.onClick();
				}

				return onClickDefault();
			};
			return (
				<TaskItem
					key={ id }
					title={ title }
					badge={ badge }
					content={ content }
					additionalInfo={ additionalInfo }
					time={ time }
					action={ onClickActions }
					level={ level }
					actionLabel={ actionLabel }
					{ ...taskItemProps }
					{ ...props }
					onClick={
						! isExpandable || isComplete
							? onClickActions
							: () => setExpandedTask( id )
					}
				/>
			);
		},
		[
			id,
			title,
			badge,
			content,
			time,
			actionLabel,
			isExpandable,
			isComplete,
		]
	);

	return hasFills ? (
		<WooOnboardingTaskListItem.Slot
			id={ id }
			fillProps={ {
				defaultTaskItem: DefaultTaskItem,
				isComplete,
				...taskItemProps,
			} }
		/>
	) : (
		<DefaultTaskItem onClick={ task.onClick } />
	);
};
