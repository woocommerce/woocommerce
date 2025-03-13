/**
 * External dependencies
 */
import { Slot, Fill } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import {
	createOrderedChildren,
	sortFillsByOrder,
} from '@woocommerce/components';

export const WC_HEADER_NAVIGATION_SLOT_NAME =
	'woocommerce_header_navigation_item';

type WooHeaderNavigationItemProps = {
	order?: number;
} & Omit< React.ComponentProps< typeof Fill >, 'name' >;

/**
 * Create a Fill for extensions to add items to the WooCommerce Admin
 * navigation area left of the page title.
 *
 * @slotFill WooHeaderNavigationItem
 * @scope woocommerce-admin
 * @example
 * const MyNavigationItem = () => (
 * <WooHeaderNavigationItem>My nav item</WooHeaderNavigationItem>
 * );
 *
 * registerPlugin( 'my-extension', {
 * render: MyNavigationItem,
 * scope: 'woocommerce-admin',
 * } );
 * @param {Object} param0
 * @param {Array}  param0.children - Node children.
 * @param {Array}  param0.order    - Node order.
 */
export const WooHeaderNavigationItem = ( {
	children,
	order = 1,
}: WooHeaderNavigationItemProps ) => {
	return (
		<Fill name={ WC_HEADER_NAVIGATION_SLOT_NAME }>
			{ ( fillProps ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooHeaderNavigationItem.Slot = ( {
	fillProps,
}: WooHeaderNavigationItemProps & {
	fillProps?: React.ComponentProps< typeof Slot >[ 'fillProps' ];
} ) => (
	<Slot name={ WC_HEADER_NAVIGATION_SLOT_NAME } fillProps={ fillProps }>
		{ sortFillsByOrder }
	</Slot>
);
