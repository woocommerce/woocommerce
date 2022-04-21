/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	getHistory,
	getNewPath,
	updateQueryString,
} from '@woocommerce/navigation';
import { OPTIONS_STORE_NAME, TaskType } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { TaskItem, useSlot } from '@woocommerce/experimental';
import { useCallback } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { WooOnboardingTaskListItem } from '@woocommerce/onboarding';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './task-list.scss';

export type TaskListItemProps = {
	task: TaskType;
	eventPrefix?: string;
	itemIndex: number;
	activeTaskId?: string;
};

export const TaskListItem: React.FC< TaskListItemProps > = ( {
	task,
	eventPrefix,
	activeTaskId,
	itemIndex,
} ) => {
	const { createNotice } = useDispatch( 'core/notices' );
	const { dismissTask, undoDismissTask } = useDispatch( OPTIONS_STORE_NAME );

	const slot = useSlot(
		`woocommerce_onboarding_task_list_item_${ task.id }`
	);
	const hasFills = Boolean( slot?.fills?.length );

	const trackClick = () => {
		recordEvent( `${ eventPrefix }_click`, {
			task_name: task.id,
		} );
	};

	const onTaskSelected = () => {
		trackClick();

		if ( task.actionUrl ) {
			if ( task.actionUrl.startsWith( 'http' ) ) {
				window.location.href = task.actionUrl;
			} else {
				getHistory().push( getNewPath( {}, task.actionUrl, {} ) );
			}
			return;
		}

		window.document.documentElement.scrollTop = 0;
		updateQueryString( { task: task.id } );
	};

	const onDismissTask = ( taskId: string ) => {
		dismissTask( taskId );
		createNotice( 'success', __( 'Task dismissed' ), {
			actions: [
				{
					label: __( 'Undo', 'woocommerce-admin' ),
					onClick: () => undoDismissTask( taskId ),
				},
			],
		} );
	};

	const className = classnames(
		'woocommerce-task-list__item index-' + itemIndex,
		{
			'is-complete': task.isComplete,
			'is-disabled': task.isDisabled,
			'is-active': task.id === activeTaskId,
		}
	);

	const taskItemProps = {
		completed: task.isComplete,
		onSnooze: task.isSnoozeable && task.onSnooze,
		onDismiss: task.isDismissable && task.onDismiss,
	};

	const DefaultTaskItem = useCallback(
		( props ) => {
			const onClickActions = () => {
				trackClick();

				if ( props.onClick ) {
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
					content={ task.content }
					onClick={ () => {
						if ( task.isDisabled ) {
							return;
						}

						onClickActions();
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
