/* eslint-disable no-console */
/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';
import {
	createOrderedChildren,
	sortFillsByOrder,
} from '@woocommerce/components';

export const WC_TASKLIST_EXPERIMENTAL_PROGRESS_TITLE_SLOT_NAME =
	'woocommerce_tasklist_experimental_progress_title_item';

export const WooTaskListProgressTitleItem: React.FC< {
	children?: React.ReactNode;
	order?: number;
} > & {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - TODO - fix this type error.
	Slot: React.FC< Slot.Props >;
} = ( { children, order = 1 } ) => {
	return (
		<Fill name={ WC_TASKLIST_EXPERIMENTAL_PROGRESS_TITLE_SLOT_NAME }>
			{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
			{ /* @ts-ignore - TODO - fix this type error. */ }
			{ ( fillProps: Fill.Props ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooTaskListProgressTitleItem.Slot = ( { fillProps } ) => {
	return (
		<Slot
			name={ WC_TASKLIST_EXPERIMENTAL_PROGRESS_TITLE_SLOT_NAME }
			fillProps={ fillProps }
		>
			{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
			{ /* @ts-ignore - TODO - fix this type error. */ }
			{ sortFillsByOrder }
		</Slot>
	);
};
