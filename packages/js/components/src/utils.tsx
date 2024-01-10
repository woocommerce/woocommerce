/**
 * External dependencies
 */
import { isValidElement, Fragment, ReactElement } from 'react';
import { cloneElement, createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { FillComponentProps, SlotComponentProps } from './types';

type ChildrenProps = {
	order: number;
};

// Type guard function to check if children is a function, while still type narrowing correctly.
export function isCallable(
	children: unknown
): children is ( props: unknown ) => React.ReactNode {
	return typeof children === 'function';
}

/**
 * Returns an object with the children and props that will be used by `cloneElement`. They will change depending on the
 * type of children passed in.
 *
 * @param {Node}   children    - Node children.
 * @param {number} order       - Node order.
 * @param {Array}  props       - Fill props.
 * @param {Object} injectProps - Props to inject.
 * @return {Object} Object with the keys: children and props.
 */
function getChildrenAndProps<
	T = FillComponentProps,
	S = Record< string, unknown >
>( children: React.ReactNode, order: number, props: T, injectProps?: S ) {
	if ( isCallable( children ) ) {
		return {
			children: children( { ...props, order, ...injectProps } ),
			props: { order, ...injectProps },
		};
	} else if ( isValidElement( children ) ) {
		// This checks whether 'children' is a react element or a standard HTML element.
		if ( typeof children?.type === 'function' ) {
			return {
				children,
				props: {
					...props,
					order,
					...injectProps,
				},
			};
		}
		return {
			children: children as React.ReactElement< ChildrenProps >,
			props: { order, ...injectProps },
		};
	}
	throw Error( 'Invalid children type' );
}

/**
 * Ordered fill item.
 *
 * @param {Node}   children    - Node children.
 * @param {number} order       - Node order.
 * @param {Array}  props       - Fill props.
 * @param {Object} injectProps - Props to inject.
 * @return {Node} Node.
 */
function createOrderedChildren<
	T = FillComponentProps,
	S = Record< string, unknown >
>(
	children: React.ReactNode,
	order: number,
	props: T,
	injectProps?: S
): React.ReactElement {
	const { children: childrenToRender, props: propsToRender } =
		getChildrenAndProps( children, order, props, injectProps );
	return cloneElement( childrenToRender as ReactElement, propsToRender );
}
export { createOrderedChildren };

/**
 * Sort fills by order for slot children.
 *
 * @param {Array} fills - slot's `Fill`s.
 * @return {Node} Node.
 */
export const sortFillsByOrder: SlotComponentProps[ 'children' ] = ( fills ) => {
	// Copy fills array here because its type is readonly array that doesn't have .sort method in Typescript definition.
	// @ts-expect-error - TODO: fix this, ReactNode technically can't be iterated over, perhaps fills type is not correct.
	const sortedFills = [ ...fills ].sort( ( a, b ) => {
		return a[ 0 ].props.order - b[ 0 ].props.order;
	} );

	return <Fragment>{ sortedFills }</Fragment>;
};

export const escapeHTML = ( string: string ) => {
	return string
		.replace( /&/g, '&amp;' )
		.replace( />/g, '&gt;' )
		.replace( /</g, '&lt;' );
};
