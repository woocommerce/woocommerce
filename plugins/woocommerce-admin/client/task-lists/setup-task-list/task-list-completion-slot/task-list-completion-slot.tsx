/**
 * External dependencies
 */
import { useSlot } from '@woocommerce/experimental';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import {
	EXPERIMENTAL_WC_TASK_LIST_COMPLETION_SLOT_NAME,
	WooTaskListCompletion,
	WooTaskListCompletionFillProps,
} from './utils';

export const TaskListCompletionSlot = ( {
	className,
	fillProps,
}: {
	className?: string;
	fillProps: WooTaskListCompletionFillProps;
} ) => {
	const slot = useSlot( EXPERIMENTAL_WC_TASK_LIST_COMPLETION_SLOT_NAME );
	const hasFills = Boolean( slot?.fills?.length );
	if ( ! hasFills ) {
		return null;
	}
	return (
		<div
			className={ classNames(
				'woocommerce-tasklist-completion-slot',
				className
			) }
		>
			<WooTaskListCompletion.Slot fillProps={ fillProps } />
		</div>
	);
};
