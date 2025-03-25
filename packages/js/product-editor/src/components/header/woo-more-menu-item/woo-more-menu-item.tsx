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

type FillProps = React.ComponentProps< typeof Fill >;
export const WooProductMoreMenuItem: React.FC< {
	children?: FillProps[ 'children' ];
	order?: number;
} > & {
	Slot: React.FC< Omit< React.ComponentProps< typeof Slot >, 'name' > >;
} = ( { children, order = 1 } ) => {
	return (
		<Fill name={ WC_PRODUCT_MORE_MENU_SLOT_NAME }>
			{ ( fillProps ) => {
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
