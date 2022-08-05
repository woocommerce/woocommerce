/**
 * External dependencies
 */
import { DragEventHandler } from 'react';

export type FunctionalChildProps = {
	onDraggableStart: DragEventHandler;
	onDraggableEnd: DragEventHandler;
};

export type FunctionalChild = ( props: FunctionalChildProps ) => JSX.Element;

export type DraggableListChild = JSX.Element | FunctionalChild;
