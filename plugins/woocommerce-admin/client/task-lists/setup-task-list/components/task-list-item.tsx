/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ONBOARDING_STORE_NAME, TaskType } from '@woocommerce/data';
import { TaskItem, useSlot } from '@woocommerce/experimental';
import { useCallback } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

import { WooOnboardingTaskListItem } from '@woocommerce/onboarding';
import classnames from 'classnames';

export type TaskListItemProps = {
	task: TaskType;
	activeTaskId: string;
	taskIndex: number;
	goToTask: () => void;
	trackClick: () => void;
};

export const TaskListItem: React.FC< TaskListItemProps > = ( {
	task,
	activeTaskId,
	taskIndex,
	goToTask,
	trackClick,
} ) => {
	const { createNotice } = useDispatch( 'core/notices' );
	const { dismissTask, undoDismissTask } = useDispatch(
		ONBOARDING_STORE_NAME
	);

	const {
		id: taskId,
		title,
		badge,
		content,
		time,
		actionLabel,
		isComplete,
		additionalInfo,
		isDismissable,
	} = task;

	const slot = useSlot( `woocommerce_onboarding_task_list_item_${ taskId }` );
	const hasFills = Boolean( slot?.fills?.length );

	const onDismissTask = ( onDismiss?: () => void ) => {
		dismissTask( taskId );
		createNotice( 'success', __( 'Task dismissed', 'woocommerce' ), {
			actions: [
				{
					label: __( 'Undo', 'woocommerce' ),
					onClick: () => undoDismissTask( taskId ),
				},
			],
		} );

		if ( onDismiss ) {
			onDismiss();
		}
	};

	const DefaultTaskItem = useCallback(
		( props: { onClick?: () => void; isClickable?: boolean } ) => {
			const className = classnames(
				'woocommerce-task-list__item index-' + taskIndex,
				{
					complete: isComplete,
					'is-active': taskId === activeTaskId,
				}
			);

			const onClick = ( e: React.MouseEvent< HTMLButtonElement > ) => {
				if ( ( e.target as HTMLElement ).tagName === 'A' ) {
					return;
				}
				if ( props.onClick ) {
					trackClick();
					return props.onClick();
				}
				goToTask();
			};

			return (
				<TaskItem
					key={ taskId }
					className={ className }
					title={ title }
					badge={ badge }
					completed={ isComplete }
					additionalInfo={ additionalInfo }
					content={ content }
					onClick={
						props.isClickable === false ? undefined : onClick
					}
					onDismiss={
						isDismissable ? () => onDismissTask() : undefined
					}
					action={ () => {} }
					actionLabel={ actionLabel }
				/>
			);
		},
		[
			taskId,
			title,
			badge,
			content,
			time,
			actionLabel,
			isComplete,
			activeTaskId,
		]
	);

	return hasFills ? (
		<WooOnboardingTaskListItem.Slot
			id={ taskId }
			fillProps={ {
				defaultTaskItem: DefaultTaskItem,
				isComplete,
			} }
		/>
	) : (
		<DefaultTaskItem />
	);
};
