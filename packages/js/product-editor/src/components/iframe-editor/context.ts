/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';

type EditorContextType = {
	hasRedo: boolean;
	hasUndo: boolean;
	isInserterOpened: boolean;
	redo: () => void;
	setIsInserterOpened: ( value: boolean ) => void;
	undo: () => void;
};

export const EditorContext = createContext< EditorContextType >( {
	hasRedo: false,
	hasUndo: false,
	isInserterOpened: false,
	redo: () => {},
	setIsInserterOpened: () => {},
	undo: () => {},
} );
