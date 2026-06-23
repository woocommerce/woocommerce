/**
 * External dependencies
 */
import clsx from 'clsx';
import { useSortable } from '@dnd-kit/sortable';
import type { UniqueIdentifier } from '@dnd-kit/core';
import { createElement, useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SortableListContext, SortableListHandleContext } from './context';

export type SortableListItemProps = {
	children: React.ReactNode;
	className?: string;
	disabled?: boolean;
	id: UniqueIdentifier;
} & React.HTMLAttributes< HTMLDivElement >;

export const SortableListItem = ( {
	children,
	className,
	disabled,
	id,
	style,
	...props
}: SortableListItemProps ) => {
	const { disabledIds } = useContext( SortableListContext );
	const isDisabled = disabled ?? disabledIds.has( String( id ) );
	const {
		attributes,
		isDragging,
		listeners,
		setActivatorNodeRef,
		setNodeRef,
		transform,
		transition,
	} = useSortable( { disabled: isDisabled, id } );

	const transformStyle = transform
		? `translate3d(${ transform.x }px, ${ transform.y }px, 0)`
		: undefined;

	return (
		<div
			{ ...props }
			ref={ setNodeRef }
			className={ clsx( 'woocommerce-sortable-list__item', className, {
				'is-dragging': isDragging,
				'is-disabled': isDisabled,
			} ) }
			style={ {
				...style,
				transform: transformStyle,
				transition,
			} }
		>
			<SortableListHandleContext.Provider
				value={ {
					attributes,
					disabled: isDisabled,
					listeners,
					setActivatorNodeRef,
				} }
			>
				{ children }
			</SortableListHandleContext.Provider>
		</div>
	);
};
