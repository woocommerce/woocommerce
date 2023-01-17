/* eslint-disable no-console */
/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { createOrderedChildren, sortFillsByOrder } from '../../utils';

export const WC_TASKLIST_PROGRESS_HEADER_SLOT_NAME =
	'woocommerce_tasklist_progress_header_item';

export const WooTaskListProgressHeaderItem: React.FC< {
	order?: number;
} > & {
	Slot: React.FC< Slot.Props >;
} = ( { children, order = 1 } ) => {
	return (
		<Fill name={ WC_TASKLIST_PROGRESS_HEADER_SLOT_NAME }>
			{ ( fillProps: Fill.Props ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooTaskListProgressHeaderItem.Slot = ( { fillProps } ) => {
	return (
		<Slot
			name={ WC_TASKLIST_PROGRESS_HEADER_SLOT_NAME }
			fillProps={ fillProps }
		>
			{ sortFillsByOrder }
		</Slot>
	);
};
