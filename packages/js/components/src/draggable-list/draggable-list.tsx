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
import { DraggableListItem } from './draggable-list-item';
import { moveIndex } from './utils';

export type DraggableListProps = {
	children: JSX.Element | JSX.Element[];
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLDivElement >;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onOrderChange?: () => void;
};

export const DraggableList = ( {
	children,
	onDragEnd = () => null,
	onDragOver = () => null,
	onDragStart = () => null,
}: DraggableListProps ) => {
	const [ items, setItems ] = useState< JSX.Element[] >( [] );
	const [ dragIndex, setDragIndex ] = useState< number | null >( null );
	const [ dropIndex, setDropIndex ] = useState< number | null >( null );

	useEffect( () => {
		setItems( Array.isArray( children ) ? children : [ children ] );
	}, [ children ] );

	const handleDragStart = (
		event: DragEvent< HTMLDivElement >,
		index: number
	) => {
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
		event: DragEvent< HTMLDivElement >,
		index: number
	) => {
		setDropIndex( index );
		onDragOver( event );
	};

	return (
		<ul className="woocommerce-draggable-list">
			{ items.map( ( child, index ) => (
				<>
					<DraggableListItem
						id={ index }
						key={ index }
						isDragging={ index === dragIndex }
						onDragEnd={ ( event ) => handleDragEnd( event, index ) }
						onDragStart={ ( event ) =>
							handleDragStart( event, index )
						}
						onDragOver={ ( event ) =>
							handleDragOver( event, index )
						}
					>
						{ child }
					</DraggableListItem>
					{ dropIndex === index && (
						<div className="woocommerce-draggable-list__slot">
							{ index }
						</div>
					) }
				</>
			) ) }
		</ul>
	);
};
