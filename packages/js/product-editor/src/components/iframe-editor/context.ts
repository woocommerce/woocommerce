/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';

type EditorContextType = {
	hasRedo: boolean;
	hasUndo: boolean;
	isDocumentOverviewOpened: boolean;
	isInserterOpened: boolean;
	redo: () => void;
	setIsDocumentOverviewOpened: ( value: boolean ) => void;
	setIsInserterOpened: ( value: boolean ) => void;
	undo: () => void;
};

export const EditorContext = createContext< EditorContextType >( {
	hasRedo: false,
	hasUndo: false,
	isDocumentOverviewOpened: false,
	isInserterOpened: false,
	redo: () => {},
	setIsDocumentOverviewOpened: () => {},
	setIsInserterOpened: () => {},
	undo: () => {},
} );
