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
import { DraggableListChild } from './types';

export type DraggableListItemProps = {
	id: string | number;
	children: DraggableListChild;
	isDragging: boolean;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLLIElement >;
};

export const DraggableListItem = ( {
	id,
	children,
	isDragging = false,
	onDragStart = () => null,
	onDragEnd = () => null,
	onDragOver = () => null,
}: DraggableListItemProps ) => {
	const handleDragStart = ( event: DragEvent< HTMLDivElement > ) => {
		onDragStart( event );
	};

	const handleDragEnd = ( event: DragEvent< HTMLDivElement > ) => {
		onDragEnd( event );
	};

	return (
		<li
			className={ classnames( 'woocommerce-draggable-list__item', {
				'is-dragging': isDragging,
			} ) }
			id={ `woocommerce-draggable-list__item-${ id }` }
			onDragOver={ onDragOver }
		>
			<Draggable
				elementId={ `woocommerce-draggable-list__item-${ id }` }
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
