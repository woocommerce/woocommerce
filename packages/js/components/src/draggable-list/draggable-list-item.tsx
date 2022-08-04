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
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLDivElement >;
};

export const DraggableListItem = ( {
	id,
	children,
	onDragStart = () => null,
	onDragEnd = () => null,
	onDragOver = () => null,
}: DraggableListItemProps ) => {
	const [ isDragging, setIsDragging ] = useState( false );

	const handleDragStart = ( event: DragEvent< HTMLDivElement > ) => {
		setIsDragging( true );
		onDragStart( event );
	};

	const handleDragEnd = ( event: DragEvent< HTMLDivElement > ) => {
		setIsDragging( false );
		onDragEnd( event );
	};

	return (
		<li
			className={ classnames( 'woocommerce-draggable-list__item', {
				'is-dragging': isDragging,
			} ) }
			id={ `woocommerce-draggable-list__item-${ id }` }
		>
			<div className="woocommerce-draggable-list__item-slot-before" />
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
						onDragOver={ onDragOver }
					>
						{ children }
					</div>
				) }
			</Draggable>
			<div className="woocommerce-draggable-list__item-slot-after" />
		</li>
	);
};
