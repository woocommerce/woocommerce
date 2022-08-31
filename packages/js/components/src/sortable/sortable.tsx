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
import { DragEvent, DragEventHandler, KeyboardEvent } from 'react';
import { drop, throttle } from 'lodash';

/**
 * Internal dependencies
 */
import {
	getNextDropIndex,
	getPreviousDropIndex,
	getNextSelectedIndex,
	getPreviousSelectedIndex,
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
	const [ selectedIndex, setSelectedIndex ] = useState< number >( 0 );
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

	const handleKeyDown = (
		event: KeyboardEvent< HTMLLIElement >,
		index: number
	) => {
		const { key } = event;
		const isSelecting = dragIndex === null || dropIndex === null;

		if ( key === ' ' ) {
			if ( isSelecting ) {
				setDragIndex( selectedIndex );
				setDropIndex( selectedIndex + 1 );
				return;
			}

			const nextItems = moveIndex( dragIndex, dropIndex, items );
			setItems( nextItems as JSX.Element[] );
			setSelectedIndex(
				dropIndex > selectedIndex ? dropIndex - 1 : dropIndex
			);
			onOrderChange( nextItems );

			setTimeout( () => {
				setDragIndex( null );
				setDropIndex( null );
			}, THROTTLE_TIME );
		}

		if ( key === 'ArrowUp' ) {
			if ( isSelecting ) {
				setSelectedIndex(
					getPreviousSelectedIndex( selectedIndex, items.length )
				);
				return;
			}
			setDropIndex(
				getPreviousDropIndex( dropIndex, dragIndex, items.length )
			);
		}

		if ( key === 'ArrowDown' ) {
			if ( isSelecting ) {
				setSelectedIndex(
					getNextSelectedIndex( selectedIndex, items.length )
				);
				return;
			}
			setDropIndex(
				getNextDropIndex( dropIndex, dragIndex, items.length )
			);
		}

		if ( key === 'Escape' ) {
			setTimeout( () => {
				setDragIndex( null );
				setDropIndex( null );
			}, THROTTLE_TIME );
		}
	};

	return (
		<ol
			className={ classnames( 'woocommerce-sortable', {
				'is-dragging': dragIndex !== null,
				'is-horizontal': isHorizontal,
			} ) }
			role="listbox"
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
						isSelected={ selectedIndex === index }
						onDragEnd={ ( event ) => handleDragEnd( event, index ) }
						onDragStart={ ( event ) =>
							handleDragStart( event, index )
						}
						onDragOver={ ( event ) => {
							event.preventDefault();
							throttledHandleDragOver( event, index );
						} }
						onKeyDown={ ( event ) => handleKeyDown( event, index ) }
					>
						{ child }
					</SortableItem>
				);
			} ) }
		</ol>
	);
};
