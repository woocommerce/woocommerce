/**
 * External dependencies
 */
import { DragEvent, DragEventHandler } from 'react';
import classnames from 'classnames';
import {
	createElement,
	useCallback,
	useEffect,
	useState,
} from '@wordpress/element';
import { throttle } from 'lodash';

/**
 * Internal dependencies
 */
import { ListItem } from './list-item';
import { isUpperHalf, moveIndex } from './utils';
import { SortableListChild } from './types';

export type SortableListProps = {
	children: SortableListChild | SortableListChild[];
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLLIElement >;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onOrderChange?: ( items: SortableListChild[] ) => void;
	shouldRenderHandles?: boolean;
};

export const SortableList = ( {
	children,
	onDragEnd = () => null,
	onDragOver = () => null,
	onDragStart = () => null,
	onOrderChange = () => null,
	shouldRenderHandles = true,
}: SortableListProps ) => {
	const [ items, setItems ] = useState< SortableListChild[] >( [] );
	const [ dragIndex, setDragIndex ] = useState< number | null >( null );
	const [ dragHeight, setDragHeight ] = useState< number >( 0 );
	const [ dropIndex, setDropIndex ] = useState< number | null >( null );

	useEffect( () => {
		setItems( Array.isArray( children ) ? children : [ children ] );
	}, [ children ] );

	const handleDragStart = (
		event: DragEvent< HTMLDivElement >,
		index: number
	) => {
		const target = event.target as HTMLElement;
		const listItem = target.closest(
			'.woocommerce-sortable-list__item'
		) as HTMLElement;

		setDragHeight( listItem.offsetHeight );
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

		setDragIndex( null );
		setDropIndex( null );
		onDragEnd( event );
	};

	const handleDragOver = (
		event: DragEvent< HTMLLIElement >,
		index: number
	) => {
		if ( dragIndex === null ) {
			return;
		}

		const targetIndex = isUpperHalf( event ) ? index : index + 1;
		setDropIndex( targetIndex );
		onDragOver( event );
	};

	const throttledHandleDragOver = useCallback(
		throttle( handleDragOver, 16 ),
		[ dragIndex ]
	);

	return (
		<ul
			className={ classnames( 'woocommerce-sortable-list', {
				'is-dragging': dragIndex !== null,
			} ) }
		>
			{ items.map( ( child, index ) => (
				<ListItem
					key={ index }
					shouldRenderHandle={ shouldRenderHandles }
					id={ index }
					isDragging={ index === dragIndex }
					isDraggingOver={ index === dropIndex }
					onDragEnd={ ( event ) => handleDragEnd( event, index ) }
					onDragStart={ ( event ) => handleDragStart( event, index ) }
					onDragOver={ ( event ) =>
						throttledHandleDragOver( event, index )
					}
					style={
						dropIndex !== null && dropIndex <= index
							? {
									transform: `translate(0, ${ dragHeight }px)`,
							  }
							: {}
					}
				>
					{ child }
				</ListItem>
			) ) }
		</ul>
	);
};
