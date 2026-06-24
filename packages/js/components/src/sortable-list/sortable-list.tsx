/**
 * External dependencies
 */
import clsx from 'clsx';
import { __, sprintf } from '@wordpress/i18n';
import { createElement, useMemo, useState } from '@wordpress/element';
import {
	type Announcements,
	type DragEndEvent,
	type DragStartEvent,
	type UniqueIdentifier,
	closestCenter,
	DndContext,
	KeyboardSensor,
	MouseSensor,
	TouchSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core';
import {
	SortableContext,
	horizontalListSortingStrategy,
	sortableKeyboardCoordinates,
	verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import {
	restrictToHorizontalAxis,
	restrictToVerticalAxis,
} from '@dnd-kit/modifiers';

/**
 * Internal dependencies
 */
import { moveSortableItem } from './utils';
import type { SortableListOrientation, SortableListRenderProps } from './types';
import { SortableListContext } from './context';

export type SortableListProps< T > = {
	children?:
		| React.ReactNode
		| ( ( props: SortableListRenderProps< T > ) => React.ReactNode );
	className?: string;
	getItemDisabled?: ( item: T ) => boolean;
	getItemId: ( item: T ) => UniqueIdentifier;
	getItemLabel?: ( item: T ) => string;
	instructions?: string;
	items: T[];
	onChange: ( items: T[] ) => void;
	onDragEnd?: ( event: DragEndEvent ) => void;
	onDragStart?: ( event: DragStartEvent ) => void;
	orientation?: SortableListOrientation;
} & Omit< React.HTMLAttributes< HTMLDivElement >, 'children' | 'onChange' >;

const defaultGetItemDisabled = () => false;

export const SortableList = < T, >( {
	children,
	className,
	getItemDisabled = defaultGetItemDisabled,
	getItemId,
	getItemLabel,
	instructions = __(
		'To pick up a sortable item, press space or enter. While dragging, use the arrow keys to move the item. Press space or enter again to drop the item, or escape to cancel.',
		'woocommerce'
	),
	items,
	onChange,
	onDragEnd = () => undefined,
	onDragStart = () => undefined,
	orientation = 'vertical',
	...props
}: SortableListProps< T > ) => {
	const [ activeId, setActiveId ] = useState< UniqueIdentifier | null >(
		null
	);
	const itemIds = useMemo(
		() => items.map( ( item ) => getItemId( item ) ),
		[ items, getItemId ]
	);
	const disabledIds = useMemo(
		() =>
			new Set(
				items
					.filter( ( item ) => getItemDisabled( item ) )
					.map( ( item ) => String( getItemId( item ) ) )
			),
		[ items, getItemDisabled, getItemId ]
	);

	const sensors = useSensors(
		useSensor( MouseSensor, {} ),
		useSensor( TouchSensor, {} ),
		useSensor( KeyboardSensor, {
			coordinateGetter: sortableKeyboardCoordinates,
		} )
	);

	const getLabel = ( id: UniqueIdentifier ) => {
		const item = items.find(
			( candidate ) => getItemId( candidate ) === id
		);

		if ( item && getItemLabel ) {
			return getItemLabel( item );
		}

		return String( id );
	};

	const handleDragStart = ( event: DragStartEvent ) => {
		setActiveId( event.active.id );
		onDragStart( event );
	};

	const handleDragCancel = () => {
		setActiveId( null );
	};

	const handleDragEnd = ( event: DragEndEvent ) => {
		setActiveId( null );
		onDragEnd( event );

		if ( ! event.over || event.active.id === event.over.id ) {
			return;
		}

		const nextItems = moveSortableItem( {
			activeId: event.active.id,
			getItemDisabled,
			getItemId,
			items,
			overId: event.over.id,
		} );

		if ( nextItems === items ) {
			return;
		}

		onChange( nextItems );
	};

	// Provide localized announcements through dnd-kit's own live region.
	// Supplying these overrides dnd-kit's built-in English defaults, which would
	// otherwise be announced in parallel and double up for screen reader users.
	const announcements: Announcements = {
		onDragStart( { active } ) {
			return sprintf(
				/* translators: %s: Sortable item label. */
				__( '%s picked up.', 'woocommerce' ),
				getLabel( active.id )
			);
		},
		onDragOver( { active, over } ) {
			if ( ! over || active.id === over.id ) {
				return undefined;
			}

			return sprintf(
				/* translators: %1$s: Sortable item label. %2$s: Target item label. */
				__( '%1$s moved near %2$s.', 'woocommerce' ),
				getLabel( active.id ),
				getLabel( over.id )
			);
		},
		onDragEnd( { active, over } ) {
			if ( ! over || active.id === over.id ) {
				return undefined;
			}

			return sprintf(
				/* translators: %s: Sortable item label. */
				__( '%s dropped.', 'woocommerce' ),
				getLabel( active.id )
			);
		},
		onDragCancel( { active } ) {
			return sprintf(
				/* translators: %s: Sortable item label. */
				__( '%s was dropped. Reordering cancelled.', 'woocommerce' ),
				getLabel( active.id )
			);
		},
	};

	const strategy =
		orientation === 'vertical'
			? verticalListSortingStrategy
			: horizontalListSortingStrategy;
	const modifiers =
		orientation === 'vertical'
			? [ restrictToVerticalAxis ]
			: [ restrictToHorizontalAxis ];

	const renderedChildren =
		typeof children === 'function'
			? children( { items, getItemId, getItemDisabled } )
			: children;

	return (
		<div
			{ ...props }
			className={ clsx(
				'woocommerce-sortable-list',
				`is-${ orientation }`,
				className,
				{
					'has-dragging-item': activeId !== null,
				}
			) }
		>
			<SortableListContext.Provider value={ { disabledIds } }>
				<DndContext
					accessibility={ {
						announcements,
						screenReaderInstructions: {
							draggable: instructions,
						},
					} }
					collisionDetection={ closestCenter }
					modifiers={ modifiers }
					onDragCancel={ handleDragCancel }
					onDragEnd={ handleDragEnd }
					onDragStart={ handleDragStart }
					sensors={ sensors }
				>
					<SortableContext items={ itemIds } strategy={ strategy }>
						{ renderedChildren }
					</SortableContext>
				</DndContext>
			</SortableListContext.Provider>
		</div>
	);
};
