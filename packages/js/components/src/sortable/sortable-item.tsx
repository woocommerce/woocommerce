/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { DragEvent, DragEventHandler, KeyboardEvent } from 'react';
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
	onKeyDown?: ( event: KeyboardEvent< HTMLLIElement > ) => void;
	isDragging?: boolean;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLLIElement >;
};

export const SortableItem = ( {
	id,
	children,
	className,
	onKeyDown,
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
			role="option"
			// eslint-disable-next-line jsx-a11y/aria-props
			aria-description={ __(
				'Press spacebar to reorder',
				'woocommerce'
			) }
			onKeyDown={ onKeyDown }
			tabIndex={ 0 }
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
