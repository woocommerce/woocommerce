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
import { TaskItem } from '@woocommerce/experimental';
import { useCallback } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import './task-list.scss';

export type TaskListItemProps = {
	isExpandable: boolean;
	isExpanded: boolean;
	setExpandedTask: ( id: string ) => void;
	task: TaskType;
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
	} = useDispatch( ONBOARDING_STORE_NAME );
	const userPreferences = useUserPreferences();

	const {
		actionLabel,
		actionUrl,
		content,
		id,
		isComplete,
		isDismissable,
		isSnoozable,
		time,
		title,
	} = task;

	const onDismiss = useCallback( () => {
		dismissTask();
		createNotice( 'success', __( 'Task dismissed' ), {
			actions: [
				{
					label: __( 'Undo', 'woocommerce-admin' ),
					onClick: () => undoDismissTask( id ),
				},
			],
		} );
	}, [ id ] );

	const onSnooze = useCallback( () => {
		snoozeTask();
		createNotice(
			'success',
			__( 'Task postponed until tomorrow', 'woocommerce-admin' ),
			{
				actions: [
					{
						label: __( 'Undo', 'woocommerce-admin' ),
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

		userPreferences.updateUserPreferences( {
			task_list_tracked_started_tasks: {
				...( trackedStartedTasks || {} ),
				[ id ]: newCount,
			},
		} );
	};

	const onClick = useCallback( () => {
		recordEvent( 'tasklist_click', {
			task_name: id,
		} );

		if ( ! isComplete ) {
			updateTrackStartedCount();
		}

		if ( actionUrl ) {
			if ( actionUrl.startsWith( 'http' ) ) {
				window.location.href = actionUrl;
			} else {
				getHistory().push( getNewPath( {}, actionUrl, {} ) );
			}
			return;
		}

		window.document.documentElement.scrollTop = 0;
		updateQueryString( { task: id } );
	}, [ id, isComplete, actionUrl ] );

	return (
		<TaskItem
			key={ id }
			title={ title }
			completed={ isComplete }
			content={ content }
			onClick={
				! isExpandable || isComplete
					? onClick
					: () => setExpandedTask( id )
			}
			expandable={ isExpandable }
			expanded={ isExpandable && isExpanded }
			onDismiss={ isDismissable && onDismiss }
			remindMeLater={ isSnoozable && onSnooze }
			time={ time }
			action={ onClick }
			actionLabel={ actionLabel }
		/>
	);
};
