/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import {
	createOrderedChildren,
	sortFillsByOrder,
} from '@woocommerce/components';

export const WC_PRODUCT_MORE_MENU_SLOT_NAME = 'WooProductMenuMenuItem';

export const WooProductMoreMenuItem: React.FC< {
	children?: React.ReactNode;
	order?: number;
} > & {
	Slot: React.FC< Slot.Props >;
} = ( { children, order = 1 } ) => {
	return (
		<Fill name={ WC_PRODUCT_MORE_MENU_SLOT_NAME }>
			{ ( fillProps: Fill.Props ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooProductMoreMenuItem.Slot = ( { fillProps } ) => (
	<Slot name={ WC_PRODUCT_MORE_MENU_SLOT_NAME } fillProps={ fillProps }>
		{ sortFillsByOrder }
	</Slot>
);
