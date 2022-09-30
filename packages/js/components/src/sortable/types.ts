/**
 * External dependencies
 */
import { DragEventHandler } from 'react';

export type SortableChild = JSX.Element;

export type SortableContextType = {
	onDragEnd?: DragEventHandler< HTMLDivElement >;
	onDragStart?: DragEventHandler< HTMLDivElement >;
};
