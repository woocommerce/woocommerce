/**
 * External dependencies
 */
import { createElement, cloneElement } from '@wordpress/element';

type NonSortableItemProps = {
	children: JSX.Element;
};

export const NonSortableItem = ( { children }: NonSortableItemProps ) => {
	if ( children === null ) {
		return children;
	}
	return cloneElement( children, {
		className: `${ children.props?.className || '' } non-sortable-item`,
	} );
};
