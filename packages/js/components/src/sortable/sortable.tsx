/**
 * External dependencies
 */
import { DragEvent, DragEventHandler } from 'react';
import classnames from 'classnames';
import { createElement, useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SortableItem } from './sortable-item';
import { moveIndex } from './utils';
import { SortableChild } from './types';

export type SortableProps = {
	children: SortableChild | SortableChild[] | null | undefined;
	isHorizontal?: boolean;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragOver?: DragEventHandler< HTMLLIElement >;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onOrderChange?: ( items: SortableChild[] ) => void;
};

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

		setDragIndex( null );
		setDropIndex( null );
		onDragEnd( event );
	};

	return (
		<ul
			className={ classnames( 'woocommerce-sortable', {
				'is-dragging': dragIndex !== null,
				'is-horizontal': isHorizontal,
			} ) }
		>
			{ items.map( ( child, index ) => (
				<SortableItem
					key={ index }
					id={ index }
					index={ index }
					isDragging={ index === dragIndex }
					onDragEnd={ ( event ) => handleDragEnd( event, index ) }
					onDragStart={ ( event ) => handleDragStart( event, index ) }
					onDragOver={ onDragOver }
					setDropIndex={ setDropIndex }
				>
					{ child }
				</SortableItem>
			) ) }
			{  }
		</ul>
	);
};
