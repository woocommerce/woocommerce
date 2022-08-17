/**
 * External dependencies
 */
import { DragEvent, DragEventHandler } from 'react';
import classnames from 'classnames';
import { cloneElement, createElement, Fragment } from '@wordpress/element';
import { Draggable } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SortableListChild } from './types';

export type ListItemProps = {
	id: string | number;
	children: SortableListChild;
	isDragging?: boolean;
	isDraggingOver?: boolean;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLLIElement >;
	style?: React.CSSProperties;
};

export const ListItem = ( {
	id,
	children,
	isDragging = false,
	isDraggingOver = false,
	onDragStart = () => null,
	onDragEnd = () => null,
	onDragOver = () => null,
	style,
}: ListItemProps ) => {
	const handleDragStart = ( event: DragEvent< HTMLDivElement > ) => {
		onDragStart( event );
	};

	const handleDragEnd = ( event: DragEvent< HTMLDivElement > ) => {
		onDragEnd( event );
	};

	return (
		<li
			className={ classnames( 'woocommerce-sortable-list__item', {
				'is-dragging': isDragging,
				'is-dragging-over': isDraggingOver,
			} ) }
			id={ `woocommerce-sortable-list__item-${ id }` }
			onDragOver={ onDragOver }
			style={ style }
		>
			<Draggable
				elementId={ `woocommerce-sortable-list__item-${ id }` }
				transferData={ {} }
				onDragStart={ handleDragStart as () => void }
				onDragEnd={ handleDragEnd as () => void }
			>
				{ ( { onDraggableStart, onDraggableEnd } ) => {
					return (
						<>
							{ cloneElement( children, {
								onDragStart: onDraggableStart,
								onDragEnd: onDraggableEnd,
							} ) }
						</>
					);
				} }
			</Draggable>
		</li>
	);
};
