/* eslint-disable no-console */
/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';
import {
	createOrderedChildren,
	sortFillsByOrder,
} from '@woocommerce/components';
import { SlotComponentProps } from '@woocommerce/components/build-types/types';

export const WC_TASKLIST_EXPERIMENTAL_PROGRESS_TITLE_SLOT_NAME =
	'woocommerce_tasklist_experimental_progress_title_item';

export const WooTaskListProgressTitleItem: React.FC< {
	children?: React.ReactNode;
	order?: number;
} > & {
	Slot: React.FC< Omit< SlotComponentProps, 'name' > >;
} = ( { children, order = 1 } ) => {
	return (
		<Fill name={ WC_TASKLIST_EXPERIMENTAL_PROGRESS_TITLE_SLOT_NAME }>
			{ ( fillProps: SlotComponentProps ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooTaskListProgressTitleItem.Slot = ( { fillProps } ) => {
	return (
		// @ts-expect-error - The Slot component is not properly typed.
		<Slot
			name={ WC_TASKLIST_EXPERIMENTAL_PROGRESS_TITLE_SLOT_NAME }
			fillProps={ fillProps }
		>
			{ sortFillsByOrder }
		</Slot>
	);
};
