/**
 * External dependencies
 */
import React from 'react';
import { Slot, Fill } from '@wordpress/components';

// TODO: move this to a published JS package once ready.

/**
 * Create a Fill for extensions to add items to the Product edit page.
 *
 * @slotFill WooProductFieldItem
 * @scope woocommerce-admin
 * @example
 * const MyProductDetailsFieldItem = () => (
 * <WooProductFieldItem fieldName="name" location="after">My header item</WooProductFieldItem>
 * );
 *
 * registerPlugin( 'my-extension', {
 * render: MyProductDetailsFieldItem,
 * scope: 'woocommerce-admin',
 * } );
 * @param {Object}  param0
 * @param {Array}   param0.children  - Node children.
 * @param {string}  param0.fieldName - Node children.
 * @param {string}  param0.location  - Node children.
 */
export const WooProductFieldItem: React.FC< {
	fieldName: string;
	location: 'before' | 'after';
} > & {
	Slot: React.FC<
		Slot.Props & { fieldName: string; location: 'before' | 'after' }
	>;
} = ( { children, fieldName, location } ) => {
	const key = fieldName.toLowerCase().replaceAll( ' ', '_' );
	return (
		<Fill name={ `woocommerce_product_${ key }_${ location }` }>
			{ children }
		</Fill>
	);
};

WooProductFieldItem.Slot = ( { fillProps, fieldName, location } ) => {
	const key = fieldName.toLowerCase().replaceAll( ' ', '_' );
	return (
		<Slot
			name={ `woocommerce_product_${ key }_${ location }` }
			fillProps={ fillProps }
		/>
	);
};
