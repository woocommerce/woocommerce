/**
 * External dependencies
 */
import {
	type DraggableSyntheticListeners,
	type DraggableAttributes,
} from '@dnd-kit/core';
import { createContext, useContext } from '@wordpress/element';
import { dragHandle, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './sortable.scss';

type DragHandleContextType = {
	attributes: DraggableAttributes | null;
	listeners: DraggableSyntheticListeners | null;
};

export const DragHandleContext = createContext< DragHandleContextType >( {
	attributes: null,
	listeners: null,
} );

export const useDragHandle = () => useContext( DragHandleContext );

/**
 * A default drag handle component that integrates with the `useDragHandle` hook. Displays a draggable icon.
 */
export const DefaultDragHandle = () => {
	const { attributes, listeners } = useDragHandle();

	return (
		<div className="drag-handle-wrapper" { ...attributes } { ...listeners }>
			<div className="drag-handle">
				<Icon icon={ dragHandle } size={ 20 } />
			</div>
		</div>
	);
};
