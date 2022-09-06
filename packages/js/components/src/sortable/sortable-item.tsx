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
	index: number;
	children: SortableChild;
	className: string;
	isDragging?: boolean;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLLIElement >;
};

export const SortableItem = ( {
	id,
	children,
	className,
	isDragging = false,
	onDragStart = () => null,
	onDragEnd = () => null,
	onDragOver = () => null,
}: SortableItemProps ) => {
	const handleDragStart = ( event: DragEvent< HTMLDivElement > ) => {
		onDragStart( event );
	};

	const handleDragEnd = ( event: DragEvent< HTMLDivElement > ) => {
		event.preventDefault();
		onDragEnd( event );
	};

	return (
		<li
			className={ classnames( 'woocommerce-sortable__item', className, {
				'is-dragging': isDragging,
			} ) }
			id={ `woocommerce-sortable__item-${ id }` }
			onDragOver={ onDragOver }
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
