/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';

type EditorContextType = {
	hasRedo: boolean;
	hasUndo: boolean;
	redo: () => void;
	undo: () => void;
};

export const EditorContext = createContext< EditorContextType >( {
	hasRedo: false,
	hasUndo: false,
	redo: () => {},
	undo: () => {},
} );
