/**
 * Internal dependencies
 */
import {
	ACTION_MODAL_EDITOR_CLOSE,
	ACTION_MODAL_EDITOR_OPEN,
} from './constants';

const modalEditorActions = {
	openModalEditor: () => ( {
		type: ACTION_MODAL_EDITOR_OPEN,
	} ),
	closeModalEditor: () => ( {
		type: ACTION_MODAL_EDITOR_CLOSE,
	} ),
};

export default {
	...modalEditorActions,
};
