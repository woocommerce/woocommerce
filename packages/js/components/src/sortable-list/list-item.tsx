/**
 * External dependencies
 */
import { DragEvent, DragEventHandler, ReactNode } from 'react';
import classnames from 'classnames';
import { cloneElement, createElement, Fragment } from '@wordpress/element';
import { Draggable } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Handle } from './handle';
import { SortableListChild } from './types';

export type ListItemProps = {
	id: string | number;
	children: SortableListChild;
	isDragging: boolean;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLLIElement >;
};

export const ListItem = ( {
	id,
	children,
	isDragging = false,
	onDragStart = () => null,
	onDragEnd = () => null,
	onDragOver = () => null,
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
			} ) }
			id={ `woocommerce-sortable-list__item-${ id }` }
			onDragOver={ onDragOver }
		>
			<Draggable
				elementId={ `woocommerce-sortable-list__item-${ id }` }
				transferData={ {} }
				onDragStart={ handleDragStart as () => void }
				onDragEnd={ handleDragEnd as () => void }
			>
				{ ( { onDraggableStart, onDraggableEnd } ) => {
					if ( typeof children === 'function' ) {
						return children( { onDraggableStart, onDraggableEnd } );
					}

					return (
						<>
							<Handle
								onDragEnd={ onDraggableEnd }
								onDragStart={ onDraggableStart }
							/>
							{ children }
						</>
					);
				} }
			</Draggable>
		</li>
	);
};
