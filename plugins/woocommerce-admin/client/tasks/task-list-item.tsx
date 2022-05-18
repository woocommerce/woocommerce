/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	getHistory,
	getNewPath,
	updateQueryString,
} from '@woocommerce/navigation';
import {
	ONBOARDING_STORE_NAME,
	TaskType,
	useUserPreferences,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { TaskItem, useSlot } from '@woocommerce/experimental';
import { useCallback } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { WooOnboardingTaskListItem } from '@woocommerce/onboarding';
import { History } from 'history';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { isWCAdmin } from '~/dashboard/utils';
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

	const {
		dismissTask,
		snoozeTask,
		undoDismissTask,
		undoSnoozeTask,
		visitedTask,
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
		level,
		additionalInfo,
	} = task;

	const slot = useSlot( `woocommerce_onboarding_task_list_item_${ id }` );
	const hasFills = Boolean( slot?.fills?.length );

	const onDismiss = useCallback( () => {
		dismissTask( id );
		createNotice( 'success', __( 'Task dismissed' ), {
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
	const updateTrackStartedCount = () => {
		const newCount = getTaskStartedCount() + 1;
		const trackedStartedTasks =
			userPreferences.task_list_tracked_started_tasks || {};

		visitedTask( id );
		userPreferences.updateUserPreferences( {
			task_list_tracked_started_tasks: {
				...( trackedStartedTasks || {} ),
				[ id ]: newCount,
			},
		} );
	};

	const trackClick = () => {
		recordEvent( 'tasklist_click', {
			task_name: id,
		} );

		if ( ! isComplete ) {
			updateTrackStartedCount();
		}
	};

	const onClickDefault = useCallback( () => {
		if ( actionUrl ) {
			if ( actionUrl.startsWith( 'http' ) ) {
				window.location.href = actionUrl;
				return;
			}

			if ( ! isWCAdmin( window.location.href ) ) {
				window.location.href = getAdminLink(
					getNewPath( {}, actionUrl, {} )
				);
				return;
			}

			( getHistory() as History ).push( getNewPath( {}, actionUrl, {} ) );

			return;
		}

		const taskPath = getNewPath( { task: id }, '/', {} );

		if ( ! isWCAdmin( window.location.href ) ) {
			window.location.href = getAdminLink( taskPath );
			return;
		}

		window.document.documentElement.scrollTop = 0;
		( getHistory() as History ).push( taskPath );
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
				trackClick();

				if ( props.onClick ) {
					if ( ! isWCAdmin( window.location.href ) ) {
						window.location.href = getAdminLink(
							'admin.php?page=wc-admin'
						);
						return;
					}

					return props.onClick();
				}

				return onClickDefault();
			};
			return (
				<TaskItem
					key={ id }
					title={ title }
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
		[ id, title, content, time, actionLabel, isExpandable, isComplete ]
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
