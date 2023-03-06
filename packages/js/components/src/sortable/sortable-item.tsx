/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { DragEvent, DragEventHandler, KeyboardEvent, useEffect } from 'react';
import classnames from 'classnames';
import {
	cloneElement,
	createElement,
	useRef,
	useContext,
} from '@wordpress/element';
import { Draggable } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SortableChild } from './types';
import { SortableContext } from './sortable';

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
	isSelected?: boolean;
};

export const SortableItem = ( {
	id,
	children,
	className,
	onKeyDown,
	isDragging = false,
	isSelected = false,
	onDragStart = () => null,
	onDragEnd = () => null,
	onDragOver = () => null,
}: SortableItemProps ) => {
	const ref = useRef< HTMLLIElement >( null );
	const sortableContext = useContext( SortableContext );

	const handleDragStart = ( event: DragEvent< HTMLDivElement > ) => {
		onDragStart( event );
	};

	const handleDragEnd = ( event: DragEvent< HTMLDivElement > ) => {
		event.preventDefault();
		onDragEnd( event );
	};

	useEffect( () => {
		if ( isSelected && ref.current ) {
			ref.current.focus();
		}
	}, [ isSelected ] );

	return (
		<li
			aria-selected={ isSelected }
			className={ classnames( 'woocommerce-sortable__item', className, {
				'is-dragging': isDragging,
				'is-selected': isSelected,
			} ) }
			id={ `woocommerce-sortable__item-${ id }` }
			onDragOver={ onDragOver }
			role="option"
			onKeyDown={ onKeyDown }
			onDrop={ ( event ) => event.preventDefault() }
			ref={ ref }
			tabIndex={ isSelected ? 0 : -1 }
			// eslint-disable-next-line jsx-a11y/aria-props
			aria-description={ __(
				'Press spacebar to reorder',
				'woocommerce'
			) }
		>
			<Draggable
				elementId={ `woocommerce-sortable__item-${ id }` }
				transferData={ {} }
				onDragStart={ handleDragStart as () => void }
				onDragEnd={ handleDragEnd as () => void }
			>
				{ ( { onDraggableStart, onDraggableEnd } ) => {
					return (
						<SortableContext.Provider
							value={ {
								...sortableContext,
								onDragStart: onDraggableStart,
								onDragEnd: onDraggableEnd,
							} }
						>
							{ cloneElement( children, {
								onDragStart: onDraggableStart,
								onDragEnd: onDraggableEnd,
							} ) }
						</SortableContext.Provider>
					);
				} }
			</Draggable>
		</li>
	);
};
