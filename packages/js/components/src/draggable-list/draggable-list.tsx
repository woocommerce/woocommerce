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

export type DraggableListProps = {
	children: JSX.Element | JSX.Element[];
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLDivElement >;
	onDragStart?: DragEventHandler< HTMLDivElement >;
};

export const DraggableList = ( {
	children,
	onDragEnd = () => null,
	onDragOver = () => null,
	onDragStart = () => null,
}: DraggableListProps ) => {
	const [ items, setItems ] = useState< JSX.Element[] >( [] );
	const [ dropTarget, setDropTarget ] = useState< EventTarget | null >(
		null
	);

	useEffect( () => {
		setItems( Array.isArray( children ) ? children : [ children ] );
	}, [ children ] );

	const handleOnDragStart = ( event: DragEvent< HTMLDivElement > ) => {
		onDragStart( event );
	};
	const handleOnDragEnd = ( event: DragEvent< HTMLDivElement > ) => {
		setDropTarget( null );
		onDragEnd( event );
	};

	const handleOnDragOver = ( event: DragEvent< HTMLDivElement > ) => {
		setDropTarget( event.target );
		onDragOver( event );
	};

	return (
		<ul className="woocommerce-draggable-list">
			{ items.map( ( child, index ) => (
				<>
					<DraggableListItem
						id={ index }
						key={ index }
						onDragStart={ handleOnDragStart }
						onDragEnd={ handleOnDragEnd }
						onDragOver={ handleOnDragOver }
					>
						{ child }
					</DraggableListItem>
				</>
			) ) }
		</ul>
	);
};
