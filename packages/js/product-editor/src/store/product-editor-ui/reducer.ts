/**
 * Internal dependencies
 */
import {
	ACTION_MODAL_EDITOR_CLOSE,
	ACTION_MODAL_EDITOR_OPEN,
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
					isOpen: true,
				},
			};
		case ACTION_MODAL_EDITOR_CLOSE:
			return {
				...state,
				modalEditor: {
					isOpen: false,
				},
			};
	}

	return state;
}
