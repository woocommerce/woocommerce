/**
 * External dependencies
 */
import { DragEventHandler } from 'react';
import classnames from 'classnames';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SortableHandle } from '../sortable';

export type ListItemProps = {
	children: JSX.Element | JSX.Element[] | string;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
};

export const ListItem = ( {
	children,
	onDragStart,
	onDragEnd,
}: ListItemProps ) => {
	const isDraggable = onDragEnd && onDragStart;

	return (
		<div className={ classnames( 'woocommerce-list-item' ) }>
			{ isDraggable && (
				<SortableHandle
					onDragEnd={ onDragEnd }
					onDragStart={ onDragStart }
				/>
			) }
			{ children }
		</div>
	);
};
