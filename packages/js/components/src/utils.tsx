/**
 * External dependencies
 */
import { isValidElement, Fragment, Children } from 'react';
import { Slot, Fill } from '@wordpress/components';
import { cloneElement, createElement } from '@wordpress/element';

type ChildrenProps = {
	order: number;
};

type FillProps = React.ComponentProps< typeof Fill >;
type SlotProps = React.ComponentProps< typeof Slot >;

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
function getChildrenAndProps< T = FillProps, S = Record< string, unknown > >(
	children:
		| React.ReactNode
		| ( ( props: T & { order: number } ) => React.ReactNode ),
	order: number,
	props: T,
	injectProps?: S
) {
	if ( typeof children === 'function' ) {
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
function createOrderedChildren< T = FillProps, S = Record< string, unknown > >(
	children:
		| React.ReactNode
		| ( ( props: T & { order: number } ) => React.ReactNode ),
	order: number,
	props: T,
	injectProps?: S
): React.ReactNode {
	const { children: childrenToRender, props: propsToRender } =
		getChildrenAndProps( children, order, props, injectProps );
	if ( ! childrenToRender || typeof childrenToRender === 'string' ) {
		return childrenToRender;
	}
	return cloneElement(
		childrenToRender as React.ReactElement,
		propsToRender
	);
}
export { createOrderedChildren };

/**
 * Sort fills by order for slot children.
 *
 * @param {Array} fills - slot's `Fill`s.
 * @return {Node} Node.
 */
export const sortFillsByOrder: SlotProps[ 'children' ] = ( fills ) => {
	// Copy fills array here because its type is readonly array that doesn't have .sort method in Typescript definition.
	const sortedFills = Children.toArray( fills ).sort( ( a, b ) => {
		if (
			typeof a === 'object' &&
			'key' in a &&
			typeof b === 'object' &&
			'key' in b
		) {
			return a.props.order - b.props.order;
		}
		return 0;
	} );

	return <Fragment>{ sortedFills }</Fragment>;
};

export const escapeHTML = ( string: string ) => {
	return string
		.replace( /&/g, '&amp;' )
		.replace( />/g, '&gt;' )
		.replace( /</g, '&lt;' );
};
