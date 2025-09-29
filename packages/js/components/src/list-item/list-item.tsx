/**
 * External dependencies
 */
import clsx from 'clsx';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SortableHandle } from '../sortable';
import { SortableItem, SortableItemProps } from '../sortable/sortable-item';

export type ListItemProps = Omit< SortableItemProps, 'index' > & {
	index?: number;
};

export const ListItem = ( {
	children,
	className,
	index = 0,
	onDragStart,
	onDragEnd,
	...props
}: ListItemProps ) => {
	const isDraggable = onDragEnd && onDragStart;

	return (
		<SortableItem
			{ ...props }
			index={ index }
			className={ clsx( 'woocommerce-list-item', className ) }
		>
			{ isDraggable && <SortableHandle /> }
			{ children }
		</SortableItem>
	);
};
