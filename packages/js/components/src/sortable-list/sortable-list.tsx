/**
 * External dependencies
 */
import { DragEvent, DragEventHandler } from 'react';
import {
	createElement,
	Fragment,
	useEffect,
	useState,
} from '@wordpress/element';

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
	onOrderChange?: () => void;
};

export const SortableList = ( {
	children,
	onDragEnd = () => null,
	onDragOver = () => null,
	onDragStart = () => null,
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

	return (
		<ul className="woocommerce-sortable-list">
			{ items.map( ( child, index ) => (
				<ListItem
					key={ index }
					id={ index }
					isDragging={ index === dragIndex }
					isDraggingOver={ index === dropIndex }
					onDragEnd={ ( event ) => handleDragEnd( event, index ) }
					onDragStart={ ( event ) => handleDragStart( event, index ) }
					onDragOver={ ( event ) => handleDragOver( event, index ) }
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
