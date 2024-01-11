/**
 * External dependencies
 */
import React from 'react';
import { Slot, Fill } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import {
	createOrderedChildren,
	sortFillsByOrder,
} from '@woocommerce/components';
import {
	FillComponentProps,
	SlotComponentProps,
} from '@woocommerce/components/build-types/types';

export const WC_HEADER_NAVIGATION_SLOT_NAME =
	'woocommerce_header_navigation_item';

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
export const WooHeaderNavigationItem: React.FC< {
	children?: React.ReactNode;
	order?: number;
} > & {
	Slot: React.FC< Omit< SlotComponentProps, 'name' > >;
} = ( { children, order = 1 } ) => {
	return (
		<Fill name={ WC_HEADER_NAVIGATION_SLOT_NAME }>
			{ ( fillProps: FillComponentProps ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooHeaderNavigationItem.Slot = ( { fillProps } ) => (
	//  @ts-expect-error - I think this issue with slot children type should be fixed upstream.
	<Slot name={ WC_HEADER_NAVIGATION_SLOT_NAME } fillProps={ fillProps }>
		{ sortFillsByOrder }
	</Slot>
);
