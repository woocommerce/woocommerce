/* eslint-disable no-console */
/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';
import {
	createOrderedChildren,
	sortFillsByOrder,
} from '@woocommerce/components';
import {
	FillProps,
	SlotComponentProps,
} from '@woocommerce/components/build-types/types';

export const WC_TASKLIST_EXPERIMENTAL_PROGRESS_HEADER_SLOT_NAME =
	'woocommerce_tasklist_experimental_progress_header_item';

export const WooTaskListProgressHeaderItem: React.FC< {
	children?: React.ReactNode;
	order?: number;
} > & {
	Slot: React.FC< Omit< SlotComponentProps, 'name' > >;
} = ( { children, order = 1 } ) => {
	return (
		<Fill name={ WC_TASKLIST_EXPERIMENTAL_PROGRESS_HEADER_SLOT_NAME }>
			{ ( fillProps: FillProps ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooTaskListProgressHeaderItem.Slot = ( { fillProps } ) => {
	return (
		// @ts-expect-error - Slot is not properly typed for some reason.
		<Slot
			name={ WC_TASKLIST_EXPERIMENTAL_PROGRESS_HEADER_SLOT_NAME }
			fillProps={ fillProps }
		>
			{ sortFillsByOrder }
		</Slot>
	);
};
