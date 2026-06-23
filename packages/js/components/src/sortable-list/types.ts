/**
 * External dependencies
 */
import type {
	DraggableAttributes,
	DraggableSyntheticListeners,
	UniqueIdentifier,
} from '@dnd-kit/core';

export type SortableListOrientation = 'vertical' | 'horizontal';

export type SortableListRenderProps< T > = {
	items: T[];
	getItemId: ( item: T ) => UniqueIdentifier;
	getItemDisabled: ( item: T ) => boolean;
};

export type SortableListHandleContextType = {
	attributes: DraggableAttributes | null;
	disabled: boolean;
	listeners: DraggableSyntheticListeners | null;
	setActivatorNodeRef: ( element: HTMLElement | null ) => void;
};
