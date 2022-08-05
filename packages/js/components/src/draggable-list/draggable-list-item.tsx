/**
 * External dependencies
 */
import { DragEvent, DragEventHandler } from 'react';
import classnames from 'classnames';
import { createElement, useState } from '@wordpress/element';
import { Draggable } from '@wordpress/components';

export type DraggableListItemProps = {
	id: string | number;
	children: JSX.Element;
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
				{ ( { onDraggableStart, onDraggableEnd } ) => (
					<div
						className="example-drag-handle"
						draggable
						onDragStart={ onDraggableStart }
						onDragEnd={ onDraggableEnd }
					>
						{ children }
					</div>
				) }
			</Draggable>
		</li>
	);
};
