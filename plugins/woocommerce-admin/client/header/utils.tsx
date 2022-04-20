/**
 * External dependencies
 */
import { isValidElement } from 'react';
import { Slot, Fill } from '@wordpress/components';
import { cloneElement } from '@wordpress/element';

/**
 * Ordered header item.
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
	if ( isValidElement( sortedFills ) ) {
		return sortedFills;
	}
	return null;
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
 * @param {Array}  param0.children - Node children.
 * @param {Array}  param0.order    - Node order.
 */
export const WooHeaderItem: React.FC< { order?: number } > & {
	Slot: React.FC< Slot.Props >;
} = ( { children, order = 1 } ) => {
	return (
		<Fill name={ 'woocommerce_header_item' }>
			{ ( fillProps: Fill.Props ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooHeaderItem.Slot = ( { fillProps } ) => (
	<Slot name={ 'woocommerce_header_item' } fillProps={ fillProps }>
		{ sortFillsByOrder }
	</Slot>
);

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
export const WooHeaderNavigationItem: React.FC< { order?: number } > & {
	Slot: React.FC< Slot.Props >;
} = ( { children, order = 1 } ) => {
	return (
		<Fill name={ 'woocommerce_header_navigation_item' }>
			{ ( fillProps: Fill.Props ) => {
				return createOrderedChildren( children, order, fillProps );
			} }
		</Fill>
	);
};

WooHeaderNavigationItem.Slot = ( { fillProps }: Slot.Props ) => (
	<Slot name={ 'woocommerce_header_navigation_item' } fillProps={ fillProps }>
		{ sortFillsByOrder }
	</Slot>
);

/**
 * Create a Fill for extensions to add custom page titles.
 *
 * @slotFill WooHeaderPageTitle
 * @scope woocommerce-admin
 * @example
 * const MyPageTitle = () => (
 * 	<WooHeaderPageTitle>My page title</WooHeaderPageTitle>
 * );
 *
 * registerPlugin( 'my-page-title', {
 * 	render: MyPageTitle,
 * 	scope: 'woocommerce-admin',
 * } );
 * @param {Object} param0
 * @param {Array}  param0.children - Node children.
 */
export const WooHeaderPageTitle: React.FC & {
	Slot: React.FC< Slot.Props >;
} = ( { children } ) => {
	return <Fill name={ 'woocommerce_header_page_title' }>{ children }</Fill>;
};

WooHeaderPageTitle.Slot = ( { fillProps } ) => (
	<Slot name={ 'woocommerce_header_page_title' } fillProps={ fillProps }>
		{ ( fills ) => {
			const last = [ [ ...fills ].pop() ];
			if ( isValidElement( last ) ) {
				return last;
			}
			return null;
		} }
	</Slot>
);
