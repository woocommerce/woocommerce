/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	ACTION_MODAL_EDITOR_CLOSE,
	ACTION_MODAL_EDITOR_OPEN,
	ACTION_MODAL_EDITOR_SET_BLOCKS,
	ACTION_MODAL_EDITOR_CONTENT_HAS_CHANGED,
} from './constants';

const modalEditorActions = {
	openModalEditor: () => ( {
		type: ACTION_MODAL_EDITOR_OPEN,
	} ),

	closeModalEditor: () => ( {
		type: ACTION_MODAL_EDITOR_CLOSE,
	} ),

	setModalEditorBlocks: ( blocks: BlockInstance ) => ( {
		type: ACTION_MODAL_EDITOR_SET_BLOCKS,
		blocks,
	} ),

	setModalEditorContentHasChanged: ( hasChanged: boolean ) => ( {
		type: ACTION_MODAL_EDITOR_CONTENT_HAS_CHANGED,
		hasChanged,
	} ),
};

export default {
	...modalEditorActions,
};
