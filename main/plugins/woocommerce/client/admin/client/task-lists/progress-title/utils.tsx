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

export const WooTaskListProgressTitleItem = ( {
	children,
	order = 1,
}: {
	children?: React.ComponentProps< typeof Fill >[ 'children' ];
	order?: number;
} ) => {
	return (
		<Fill name={ WC_TASKLIST_EXPERIMENTAL_PROGRESS_TITLE_SLOT_NAME }>
			{ ( fillProps ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooTaskListProgressTitleItem.Slot = ( {
	fillProps,
}: {
	fillProps?: React.ComponentProps< typeof Slot >[ 'fillProps' ];
} ) => {
	return (
		<Slot
			name={ WC_TASKLIST_EXPERIMENTAL_PROGRESS_TITLE_SLOT_NAME }
			fillProps={ fillProps }
		>
			{ sortFillsByOrder }
		</Slot>
	);
};
