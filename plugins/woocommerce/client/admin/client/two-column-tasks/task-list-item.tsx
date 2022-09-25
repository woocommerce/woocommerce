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
import { useCallback, useContext } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { WooOnboardingTaskListItem } from '@woocommerce/onboarding';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { LayoutContext } from '~/layout';

export type TaskListItemProps = {
	task: TaskType;
	eventPrefix?: string;
};

export const TaskListItem: React.FC< TaskListItemProps > = ( {
	task,
	eventPrefix,
} ) => {
	const { createNotice } = useDispatch( 'core/notices' );

	const {
		visitedTask,
		dismissTask,
		undoDismissTask,
		snoozeTask,
		undoSnoozeTask,
	} = useDispatch( ONBOARDING_STORE_NAME );

	const layoutContext = useContext( LayoutContext );

	const slot = useSlot(
		`woocommerce_onboarding_task_list_item_${ task.id }`
	);
	const hasFills = Boolean( slot?.fills?.length );

	const userPreferences = useUserPreferences();

	const getTaskStartedCount = () => {
		const trackedStartedTasks =
			userPreferences.task_list_tracked_started_tasks;
		if ( ! trackedStartedTasks || ! trackedStartedTasks[ task.id ] ) {
			return 0;
		}
		return trackedStartedTasks[ task.id ];
	};

	const updateTrackStartedCount = () => {
		const newCount = getTaskStartedCount() + 1;
		const trackedStartedTasks =
			userPreferences.task_list_tracked_started_tasks || {};

		visitedTask( task.id );
		userPreferences.updateUserPreferences( {
			task_list_tracked_started_tasks: {
				...( trackedStartedTasks || {} ),
				[ task.id ]: newCount,
			},
		} );
	};

	const trackClick = () => {
		recordEvent( `${ eventPrefix }click`, {
			task_name: task.id,
			context: layoutContext.toString(),
		} );

		if ( ! task.isComplete ) {
			updateTrackStartedCount();
		}
	};

	const onTaskSelected = () => {
		trackClick();

		if ( task.actionUrl ) {
			navigateTo( {
				url: task.actionUrl,
			} );
			return;
		}

		navigateTo( { url: getNewPath( { task: task.id }, '/', {} ) } );
	};

	const onDismiss = useCallback( () => {
		dismissTask( task.id );
		createNotice( 'success', __( 'Task dismissed', 'woocommerce' ), {
			actions: [
				{
					label: __( 'Undo', 'woocommerce' ),
					onClick: () => undoDismissTask( task.id ),
				},
			],
		} );
	}, [ task.id ] );

	const onSnooze = useCallback( () => {
		snoozeTask( task.id );
		createNotice(
			'success',
			__( 'Task postponed until tomorrow', 'woocommerce' ),
			{
				actions: [
					{
						label: __( 'Undo', 'woocommerce' ),
						onClick: () => undoSnoozeTask( task.id ),
					},
				],
			}
		);
	}, [ task.id ] );

	const className = classnames( 'woocommerce-task-list__item', {
		complete: task.isComplete,
		'is-disabled': task.isDisabled,
	} );

	const taskItemProps = {
		completed: task.isComplete,
		onSnooze: task.isSnoozeable && onSnooze,
		onDismiss: task.isDismissable && onDismiss,
	};

	const DefaultTaskItem = useCallback(
		( props ) => {
			const onClickActions = () => {
				if ( props.onClick ) {
					trackClick();
					return props.onClick();
				}
				return onTaskSelected();
			};
			return (
				<TaskItem
					key={ task.id }
					className={ className }
					title={ task.title }
					completed={ task.isComplete }
					expanded={ ! task.isComplete }
					additionalInfo={ task.additionalInfo }
					onDismiss={ task.isDismissable && onDismiss }
					action={ () => {} }
					actionLabel={ task.actionLabel }
					{ ...props }
					onClick={ ( e: React.ChangeEvent ) => {
						if ( task.isDisabled || e.target.tagName === 'A' ) {
							return;
						}
						onClickActions();
					} }
				/>
			);
		},
		[
			task.id,
			task.title,
			task.content,
			task.time,
			task.actionLabel,
			task.isComplete,
		]
	);

	return hasFills ? (
		<WooOnboardingTaskListItem.Slot
			id={ task.id }
			fillProps={ {
				defaultTaskItem: DefaultTaskItem,
				isComplete: task.isComplete,
				...taskItemProps,
			} }
		/>
	) : (
		<DefaultTaskItem />
	);
};
