/**
 * External dependencies
 */
import { DragEvent, DragEventHandler } from 'react';
import classnames from 'classnames';
import {
	cloneElement,
	createElement,
	Fragment,
	useCallback,
	useState,
} from '@wordpress/element';
import { Draggable } from '@wordpress/components';
import { throttle } from 'lodash';

/**
 * Internal dependencies
 */
import { isUpperHalf } from './utils';
import { SortableChild } from './types';

export type SortableItemProps = {
	id: string | number;
	index: number;
	children: SortableChild;
	isDragging?: boolean;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLLIElement >;
	setDropIndex: ( index: number | null ) => void;
};

const THROTTLE_TIME = 16;

export const SortableItem = ( {
	id,
	index,
	children,
	isDragging = false,
	onDragStart = () => null,
	onDragEnd = () => null,
	onDragOver = () => null,
	setDropIndex,
}: SortableItemProps ) => {
	const [ isDraggingOver, setIsDraggingOver ] = useState( false );
	const [ isBefore, setIsBefore ] = useState( true );

	const handleDragStart = ( event: DragEvent< HTMLDivElement > ) => {
		onDragStart( event );
	};

	const handleDragEnd = ( event: DragEvent< HTMLDivElement > ) => {
		onDragEnd( event );
	};

	const handleDragOver = ( event: DragEvent< HTMLLIElement > ) => {
		setIsDraggingOver( true );
		onDragOver( event );

		if ( isUpperHalf( event ) ) {
			setDropIndex( index );
			setIsBefore( true );
			return;
		}

		setDropIndex( index );
		setIsBefore( false );
	};

	const throttledHandleDragOver = useCallback(
		throttle( handleDragOver, THROTTLE_TIME ),
		[ index ]
	);

	const handleDragLeave = () => {
		setTimeout( () => {
			setDropIndex( null );
			setIsDraggingOver( false );
		}, THROTTLE_TIME );
	};

	return (
		<li
			className={ classnames( 'woocommerce-sortable__item', {
				'is-dragging': isDragging,
				'is-dragging-over': isDraggingOver,
				'is-dragging-over-before': isDraggingOver && isBefore,
				'is-dragging-over-after': isDraggingOver && ! isBefore,
			} ) }
			id={ `woocommerce-sortable__item-${ id }` }
			onDragOver={ ( event ) => {
				event?.preventDefault();
				throttledHandleDragOver( event );
			} }
			onDragLeave={ handleDragLeave }
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
