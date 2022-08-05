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
		<ul className="woocommerce-sortable-list">
			{ items.map( ( child, index ) => (
				<Fragment key={ index }>
					{ dropIndex === index && (
						<div className="woocommerce-sortable-list__slot">
							<strong>{ index }</strong>
						</div>
					) }
					<ListItem
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
					</ListItem>
				</Fragment>
			) ) }
			<div className="woocommerce-sortable-list__slot">last</div>
		</ul>
	);
};
