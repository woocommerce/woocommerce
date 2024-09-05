/**
 * Internal dependencies
 */
import {
	ACTION_MODAL_EDITOR_CONTENT_HAS_CHANGED,
	ACTION_MODAL_EDITOR_CLOSE,
	ACTION_MODAL_EDITOR_OPEN,
	ACTION_MODAL_EDITOR_SET_BLOCKS,
	ACTION_PANEL_PREPUBLISH_CLOSE,
	ACTION_PANEL_PREPUBLISH_OPEN,
} from './constants';
import type {
	ProductEditorModalEditorAction,
	ProductEditorPrepublishPanelAction,
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
	prepublishPanel: {
		isOpen: false,
	},
};

export default function reducer(
	state = INITIAL_STATE,
	action: ProductEditorModalEditorAction | ProductEditorPrepublishPanelAction
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

		case ACTION_PANEL_PREPUBLISH_OPEN:
			return {
				...state,
				prepublishPanel: {
					isOpen: true,
				},
			};

		case ACTION_PANEL_PREPUBLISH_CLOSE:
			return {
				...state,
				prepublishPanel: {
					isOpen: false,
				},
			};
	}

	return state;
}
