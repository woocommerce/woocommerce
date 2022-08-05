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
import { isUpperHalf, moveIndex } from './utils';
import { DraggableListChild } from './types';

export type DraggableListProps = {
	children: DraggableListChild | DraggableListChild[];
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLLIElement >;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onOrderChange?: () => void;
};

export const DraggableList = ( {
	children,
	onDragEnd = () => null,
	onDragOver = () => null,
	onDragStart = () => null,
}: DraggableListProps ) => {
	const [ items, setItems ] = useState< DraggableListChild[] >( [] );
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
		<ul className="woocommerce-draggable-list">
			{ items.map( ( child, index ) => (
				<Fragment key={ index }>
					{ dropIndex === index && (
						<div className="woocommerce-draggable-list__slot">
							<strong>{ index }</strong>
						</div>
					) }
					<DraggableListItem
						id={ index }
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
				</Fragment>
			) ) }
			<div className="woocommerce-draggable-list__slot">last</div>
		</ul>
	);
};
