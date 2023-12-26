/**
 * External dependencies
 */
import { DragEvent, MouseEvent } from 'react';
import { useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DraggableProps } from './types';
import { findDraggableIndex, sort } from './utils';

export function useDraggable< T >( { onSort }: DraggableProps< T > ) {
	const dragIndexRef = useRef< number >( -1 );
	const dropIndexRef = useRef< number >( -1 );
	const draggableElementsRef = useRef< HTMLElement[] >( [] );

	function onDragStart( event: DragEvent< HTMLElement > ) {
		const element = event.target as HTMLElement;
		if ( element.dataset.draggable !== 'target' ) {
			event.preventDefault();
			return;
		}

		event.dataTransfer.effectAllowed = 'move';
		event.dataTransfer.dropEffect = 'move';

		element.classList.add( 'is-dragging' );

		const parent = element.closest( '[data-draggable=parent]' );
		draggableElementsRef.current = Array.from(
			parent
				?.querySelectorAll< HTMLElement >( '[data-draggable=target]' )
				?.values() ?? []
		);

		dragIndexRef.current = draggableElementsRef.current.indexOf( element );
	}

	function onDragEnd( event: DragEvent< HTMLElement > ) {
		const element = event.target as HTMLElement;
		if ( element.dataset.draggable !== 'target' ) {
			event.preventDefault();
			return;
		}
		element.classList.remove( 'is-dragging' );
	}

	function onDragEnter( event: DragEvent< HTMLElement > ) {
		const element = event.target as HTMLElement;
		const relatedTarget = event.relatedTarget as HTMLElement | null;
		if (
			element.dataset.draggable !== 'target' ||
			element.contains( relatedTarget )
		) {
			event.preventDefault();
			return;
		}

		const { draggable, index } = findDraggableIndex(
			draggableElementsRef.current,
			element
		);

		dropIndexRef.current = index;

		if ( dragIndexRef.current === dropIndexRef.current ) return;
		if ( dragIndexRef.current < dropIndexRef.current ) {
			draggable?.classList.add( 'is-dragging-after' );
		} else {
			draggable?.classList.add( 'is-dragging-before' );
		}
	}

	function onDragLeave( event: DragEvent< HTMLElement > ) {
		const element = event.target as HTMLElement;
		const relatedTarget = event.relatedTarget as HTMLElement | null;
		if (
			element.dataset.draggable !== 'target' ||
			element.contains( relatedTarget )
		) {
			event.preventDefault();
			return;
		}

		element.classList.remove( 'is-dragging-before' );
		element.classList.remove( 'is-dragging-after' );
	}

	function onDrop( event: DragEvent< HTMLElement > ) {
		event.preventDefault();
		const element = event.target as HTMLElement;
		const draggable =
			element.dataset.draggable === 'target'
				? element
				: element.closest(
						'[data-draggable=parent] [data-draggable=target]'
				  );
		draggable?.removeAttribute( 'draggable' );
		draggable?.classList.remove( 'is-dragging-before' );
		draggable?.classList.remove( 'is-dragging-after' );

		if (
			dragIndexRef.current !== -1 &&
			dropIndexRef.current !== -1 &&
			dragIndexRef.current !== dropIndexRef.current
		) {
			const drapIndex = dragIndexRef.current;
			const dropIndex = dropIndexRef.current;

			onSort( ( items: T[] ) =>
				sort(
					items,
					drapIndex,
					dropIndex + Number( drapIndex < dropIndex )
				)
			);
		}

		dragIndexRef.current = -1;
		dropIndexRef.current = -1;
	}

	function onDragOver( event: DragEvent< HTMLElement > ) {
		event.preventDefault();
		return false;
	}

	function onMouseDown( event: MouseEvent< HTMLElement > ) {
		const element = event.target as HTMLElement;
		element
			.closest( '[data-draggable=parent] [data-draggable=target]' )
			?.setAttribute( 'draggable', 'true' );
	}

	function onMouseUp( event: MouseEvent< HTMLElement > ) {
		const element = event.target as HTMLElement;
		element
			.closest( '[data-draggable=parent] [data-draggable=target]' )
			?.removeAttribute( 'draggable' );
	}

	return {
		container: {
			'data-draggable': 'parent',
			className: 'woocommerce-draggable__container',
		},
		draggable: {
			'data-draggable': 'target',
			onDragStart,
			onDragEnter,
			onDragOver,
			onDragLeave,
			onDragEnd,
			onDrop,
		},
		handler: {
			'data-draggable': 'handler',
			onMouseDown,
			onMouseUp,
			onMouseLeave: onMouseUp,
		},
	};
}
