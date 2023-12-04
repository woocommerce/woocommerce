/**
 * Internal dependencies
 */
import {
	ACTION_MODAL_EDITOR_CLOSE,
	ACTION_MODAL_EDITOR_OPEN,
} from './constants';

export type ProductEditorUIStateProps = {
	modalEditor: {
		isOpen: boolean;
	};
};

export type ProductEditorModalEditorAction = {
	type: typeof ACTION_MODAL_EDITOR_OPEN | typeof ACTION_MODAL_EDITOR_CLOSE;
};
