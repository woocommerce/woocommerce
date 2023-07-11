/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { createContext } from '@wordpress/element';

type EditorContextType = {
	blocks: BlockInstance[];
	hasRedo: boolean;
	hasUndo: boolean;
	isDocumentOverviewOpened: boolean;
	isInserterOpened: boolean;
	isSidebarOpened: boolean;
	redo: () => void;
	setIsDocumentOverviewOpened: ( value: boolean ) => void;
	setIsInserterOpened: ( value: boolean ) => void;
	setIsSidebarOpened: ( value: boolean ) => void;
	undo: () => void;
};

export const EditorContext = createContext< EditorContextType >( {
	blocks: [],
	hasRedo: false,
	hasUndo: false,
	isDocumentOverviewOpened: false,
	isInserterOpened: false,
	isSidebarOpened: true,
	redo: () => {},
	setIsDocumentOverviewOpened: () => {},
	setIsInserterOpened: () => {},
	setIsSidebarOpened: () => {},
	undo: () => {},
} );
