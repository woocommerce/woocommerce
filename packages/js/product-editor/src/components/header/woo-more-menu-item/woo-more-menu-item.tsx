/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import {
	createOrderedChildren,
	sortFillsByOrder,
} from '@woocommerce/components';
import { SlotComponentProps } from '@woocommerce/components/build-types/types';

export const WC_PRODUCT_MORE_MENU_SLOT_NAME = 'WooProductMenuMenuItem';

export const WooProductMoreMenuItem: React.FC< {
	children?: React.ReactNode;
	order?: number;
} > & {
	Slot: React.FC< SlotComponentProps >;
} = ( { children, order = 1 } ) => {
	return (
		<Fill name={ WC_PRODUCT_MORE_MENU_SLOT_NAME }>
			{ ( fillProps: SlotComponentProps ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooProductMoreMenuItem.Slot = ( { fillProps } ) => (
	// @ts-expect-error - TODO: fix SlotComponentProps, it inherits alot of conflicting prop types from HTML.
	<Slot name={ WC_PRODUCT_MORE_MENU_SLOT_NAME } fillProps={ fillProps }>
		{ sortFillsByOrder }
	</Slot>
);
