/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { DragEventHandler } from 'react';

/**
 * Internal dependencies
 */
import { DraggableIcon } from './draggable-icon';

type SortableHandleProps = {
	children?: React.ReactNode;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
};

export const SortableHandle = ( {
	children,
	onDragStart = () => null,
	onDragEnd = () => null,
}: SortableHandleProps ) => (
	<div
		className="woocommerce-sortable__handle"
		draggable
		onDragStart={ onDragStart }
		onDragEnd={ onDragEnd }
	>
		{ children ? children : <DraggableIcon /> }
	</div>
);
