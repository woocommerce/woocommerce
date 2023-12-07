/**
 * Internal dependencies
 */
import {
	ACTION_MODAL_EDITOR_CONTENT_HAS_CHANGED,
	ACTION_MODAL_EDITOR_CLOSE,
	ACTION_MODAL_EDITOR_OPEN,
	ACTION_MODAL_EDITOR_SET_BLOCKS,
} from './constants';
import type {
	ProductEditorModalEditorAction,
	ProductEditorUIStateProps,
} from './types';

/**
 * Types & Constants
 */
const INITIAL_STATE: ProductEditorUIStateProps = {
	modalEditor: {
		isOpen: false,
		blocks: [],
		hasChanged: false,
	},
};

export default function reducer(
	state = INITIAL_STATE,
	action: ProductEditorModalEditorAction
) {
	switch ( action.type ) {
		case ACTION_MODAL_EDITOR_OPEN:
			return {
				...state,
				modalEditor: {
					...state.modalEditor,
					isOpen: true,
				},
			};

		case ACTION_MODAL_EDITOR_CLOSE:
			return {
				...state,
				modalEditor: {
					...state.modalEditor,
					isOpen: false,
				},
			};

		case ACTION_MODAL_EDITOR_SET_BLOCKS:
			return {
				...state,
				modalEditor: {
					...state.modalEditor,
					blocks: action.blocks || [],
				},
			};

		case ACTION_MODAL_EDITOR_CONTENT_HAS_CHANGED:
			return {
				...state,
				modalEditor: {
					...state.modalEditor,
					hasChanged: action?.hasChanged || false,
				},
			};
	}

	return state;
}
