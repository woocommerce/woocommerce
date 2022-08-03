/**
 * External dependencies
 */
import React from 'react';
import { Slot, Fill } from '@wordpress/components';
import { cloneElement, isValidElement } from '@wordpress/element';
import { snakeCase } from 'lodash';

// TODO: move this to a published JS package once ready.

/**
 * Ordered product field item.
 *
 * @param {Node}   children - Node children.
 * @param {number} order    - Node order.
 * @param {Array}  props    - Fill props.
 * @return {Node} Node.
 */
const createOrderedChildren = (
	children: React.ReactNode,
	order: number,
	props: Fill.Props
) => {
	if ( typeof children === 'function' ) {
		return cloneElement( children( props ), { order } );
	} else if ( isValidElement( children ) ) {
		return cloneElement( children, { ...props, order } );
	}
	throw Error( 'Invalid children type' );
};

/**
 * Sort fills by order for slot children.
 *
 * @param {Array} fills - slot's `Fill`s.
 * @return {Node} Node.
 */
const sortFillsByOrder: Slot.Props[ 'children' ] = ( fills ) => {
	// Copy fills array here because its type is readonly array that doesn't have .sort method in Typescript definition.
	const sortedFills = [ ...fills ].sort( ( a, b ) => {
		return a[ 0 ].props.order - b[ 0 ].props.order;
	} );
	return <>{ sortedFills }</>;
};

/**
 * Create a Fill for extensions to add items to the Product edit page.
 *
 * @slotFill WooProductFieldItem
 * @scope woocommerce-admin
 * @example
 * const MyProductDetailsFieldItem = () => (
 * <WooProductFieldItem fieldName="name" categoryName="Product details" location="after">My header item</WooProductFieldItem>
 * );
 *
 * registerPlugin( 'my-extension', {
 * render: MyProductDetailsFieldItem,
 * scope: 'woocommerce-admin',
 * } );
 * @param {Object}  param0
 * @param {Array}   param0.children  - Node children.
 * @param {string}  param0.fieldName - Field name.
 * @param {string}  param0.categoryName - Category name.
 * @param {number}  param0.order - Order of Fill component.
 * @param {string}  param0.location  - Location before or after.
 */
export const WooProductFieldItem: React.FC< {
	fieldName: string;
	categoryName: string;
	order?: number;
	location: 'before' | 'after';
} > & {
	Slot: React.FC<
		Slot.Props & {
			fieldName: string;
			categoryName: string;
			location: 'before' | 'after';
		}
	>;
} = ( { children, fieldName, categoryName, location, order = 1 } ) => {
	const categoryKey = snakeCase( categoryName );
	const fieldKey = snakeCase( fieldName );
	return (
		<Fill
			name={ `woocommerce_product_${ categoryKey }_${ fieldKey }_${ location }` }
		>
			{ ( fillProps: Fill.Props ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooProductFieldItem.Slot = ( {
	fillProps,
	fieldName,
	categoryName,
	location,
} ) => {
	const categoryKey = snakeCase( categoryName );
	const fieldKey = snakeCase( fieldName );
	return (
		<Slot
			name={ `woocommerce_product_${ categoryKey }_${ fieldKey }_${ location }` }
			fillProps={ fillProps }
		>
			{ sortFillsByOrder }
		</Slot>
	);
};
