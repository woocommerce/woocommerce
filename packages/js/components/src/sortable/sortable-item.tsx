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
import { SortableChild } from './types';

export type SortableItemProps = {
	id: string | number;
	children: SortableChild;
	isDragging?: boolean;
	isDraggingOver?: boolean;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLLIElement >;
	style?: React.CSSProperties;
};

export const SortableItem = ( {
	id,
	children,
	isDragging = false,
	isDraggingOver = false,
	onDragStart = () => null,
	onDragEnd = () => null,
	onDragOver = () => null,
	style,
}: SortableItemProps ) => {
	const handleDragStart = ( event: DragEvent< HTMLDivElement > ) => {
		onDragStart( event );
	};

	const handleDragEnd = ( event: DragEvent< HTMLDivElement > ) => {
		onDragEnd( event );
	};

	return (
		<li
			className={ classnames( 'woocommerce-sortable__item', {
				'is-dragging': isDragging,
				'is-dragging-over': isDraggingOver,
			} ) }
			id={ `woocommerce-sortable__item-${ id }` }
			onDragOver={ onDragOver }
			style={ style }
		>
			<Draggable
				elementId={ `woocommerce-sortable__item-${ id }` }
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
