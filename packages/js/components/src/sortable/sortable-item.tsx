/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { DragEvent, useEffect } from 'react';
import classnames from 'classnames';
import { createElement, useRef, useContext } from '@wordpress/element';
import { Draggable } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SortableContext } from './sortable';

export type SortableItemProps = React.DetailedHTMLProps<
	React.HTMLAttributes< HTMLDivElement >,
	HTMLDivElement
> & {
	index: number;
	isDragging?: boolean;
	isSelected?: boolean;
};

export const SortableItem = ( {
	id,
	children,
	className,
	isDragging = false,
	isSelected = false,
	onDragStart = () => null,
	onDragEnd = () => null,
	role = 'listitem',
	...props
}: SortableItemProps ) => {
	const ref = useRef< HTMLDivElement >( null );
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
		<div
			{ ...props }
			aria-selected={ isSelected }
			className={ classnames( 'woocommerce-sortable__item', className, {
				'is-dragging': isDragging,
				'is-selected': isSelected,
			} ) }
			id={ `woocommerce-sortable__item-${ id }` }
			role={ role }
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
							{ children }
						</SortableContext.Provider>
					);
				} }
			</Draggable>
		</div>
	);
};
