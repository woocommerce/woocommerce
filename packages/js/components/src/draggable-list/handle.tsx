/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { DragEventHandler } from 'react';

/**
 * Internal dependencies
 */
import { DraggableIcon } from './draggable-icon';

type HandleProps = {
	children?: React.ReactNode;
	onDragStart?: DragEventHandler< HTMLDivElement >;
	onDragEnd?: DragEventHandler< HTMLDivElement >;
};

export const Handle = ( {
	children,
	onDragStart = () => null,
	onDragEnd = () => null,
}: HandleProps ) => (
	<span
		className="woocommerce-draggable-list__handle"
		draggable
		onDragStart={ onDragStart }
		onDragEnd={ onDragEnd }
	>
		{ children ? children : <DraggableIcon /> }
	</span>
);
