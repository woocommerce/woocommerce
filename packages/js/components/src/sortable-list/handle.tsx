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
		className="woocommerce-sortable-list__handle"
		draggable
		onDragStart={ onDragStart }
		onDragEnd={ onDragEnd }
		aria-label={ __( 'Move this item', 'woocommerce' ) }
	>
		{ children ? children : <DraggableIcon /> }
	</span>
);
