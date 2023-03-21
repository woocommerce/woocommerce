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

export const WC_HEADER_SLOT_NAME = 'woocommerce_header_item';

/**
 * Get the slot fill name for the generic header slot or a specific header if provided.
 *
 * @param  name Name of the specific header.
 * @return string
 */
const getSlotFillName = ( name?: string ) => {
	if ( ! name || ! name.length ) {
		return WC_HEADER_SLOT_NAME;
	}

	return `${ WC_HEADER_SLOT_NAME }/${ name }`;
};

/**
 * Create a Fill for extensions to add items to the WooCommerce Admin header.
 *
 * @slotFill WooHeaderItem
 * @scope woocommerce-admin
 * @example
 * const MyHeaderItem = () => (
 * <WooHeaderItem>My header item</WooHeaderItem>
 * );
 *
 * registerPlugin( 'my-extension', {
 * render: MyHeaderItem,
 * scope: 'woocommerce-admin',
 * } );
 * @param {Object} param0
 * @param {Array}  param0.name     - Header name.
 * @param {Array}  param0.children - Node children.
 * @param {Array}  param0.order    - Node order.
 */
export const WooHeaderItem: React.FC< {
	name?: string;
	children?: React.ReactNode;
	order?: number;
} > & {
	Slot: React.FC< Slot.Props & { name?: string } >;
} = ( { children, order = 1, name = '' } ) => {
	return (
		<Fill name={ getSlotFillName( name ) }>
			{ ( fillProps: Fill.Props ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooHeaderItem.Slot = ( { fillProps, name = '' } ) => (
	<Slot name={ getSlotFillName( name ) } fillProps={ fillProps }>
		{ sortFillsByOrder }
	</Slot>
);
