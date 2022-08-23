/**
 * External dependencies
 */
import classnames from 'classnames';
import {
	createElement,
	useCallback,
	useEffect,
	useState,
} from '@wordpress/element';
import { DragEvent, DragEventHandler } from 'react';
import { throttle } from 'lodash';

/**
 * Internal dependencies
 */
import {
	isBefore,
	isDraggingOverAfter,
	isDraggingOverBefore,
	isLastDroppable,
	moveIndex,
} from './utils';
import { SortableItem } from './sortable-item';
import { SortableChild } from './types';

export type SortableProps = {
	children: SortableChild | SortableChild[] | null | undefined;
	isHorizontal?: boolean;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLLIElement >;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onOrderChange?: ( items: SortableChild[] ) => void;
};

const THROTTLE_TIME = 16;

export const Sortable = ( {
	children,
	isHorizontal = false,
	onDragEnd = () => null,
	onDragOver = () => null,
	onDragStart = () => null,
	onOrderChange = () => null,
}: SortableProps ) => {
	const [ items, setItems ] = useState< SortableChild[] >( [] );
	const [ dragIndex, setDragIndex ] = useState< number | null >( null );
	const [ dropIndex, setDropIndex ] = useState< number | null >( null );

	useEffect( () => {
		if ( ! children ) {
			return;
		}
		setItems( Array.isArray( children ) ? children : [ children ] );
	}, [ children ] );

	const handleDragStart = (
		event: DragEvent< HTMLDivElement >,
		index: number
	) => {
		setDropIndex( index );
		setDragIndex( index );
		onDragStart( event );
	};

	const handleDragEnd = (
		event: DragEvent< HTMLDivElement >,
		index: number
	) => {
		if (
			dropIndex !== null &&
			dragIndex !== null &&
			dropIndex !== dragIndex
		) {
			const nextItems = moveIndex( dragIndex, dropIndex, items );
			setItems( nextItems as JSX.Element[] );
			onOrderChange( nextItems );
		}

		setTimeout( () => {
			setDragIndex( null );
			setDropIndex( null );
			onDragEnd( event );
		}, THROTTLE_TIME );
	};

	const handleDragOver = (
		event: DragEvent< HTMLLIElement >,
		index: number
	) => {
		const targetIndex = isBefore( event, isHorizontal ) ? index : index + 1;
		setDropIndex( targetIndex );
		onDragOver( event );
	};

	const throttledHandleDragOver = useCallback(
		throttle( handleDragOver, THROTTLE_TIME ),
		[]
	);

	return (
		<ul
			className={ classnames( 'woocommerce-sortable', {
				'is-dragging': dragIndex !== null,
				'is-horizontal': isHorizontal,
			} ) }
		>
			{ items.map( ( child, index ) => {
				const isDragging = index === dragIndex;
				const itemClasses = classnames( {
					'is-dragging-over-after': isDraggingOverAfter(
						index,
						dragIndex,
						dropIndex
					),
					'is-dragging-over-before': isDraggingOverBefore(
						index,
						dragIndex,
						dropIndex
					),
					'is-last-droppable': isLastDroppable(
						index,
						dragIndex,
						items.length
					),
				} );
				return (
					<SortableItem
						key={ index }
						className={ itemClasses }
						id={ index }
						index={ index }
						isDragging={ isDragging }
						onDragEnd={ ( event ) => handleDragEnd( event, index ) }
						onDragStart={ ( event ) =>
							handleDragStart( event, index )
						}
						onDragOver={ ( event ) => {
							event.preventDefault();
							throttledHandleDragOver( event, index );
						} }
					>
						{ child }
					</SortableItem>
				);
			} ) }
		</ul>
	);
};
