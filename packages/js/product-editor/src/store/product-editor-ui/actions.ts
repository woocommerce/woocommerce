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
	ACTION_SIDEBAR_PREPUBLISH_OPEN,
	ACTION_SIDEBAR_PREPUBLISH_CLOSE,
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

const prepublishSidebarActions = {
	openPrepublishSidebar: () => ( {
		type: ACTION_SIDEBAR_PREPUBLISH_OPEN,
	} ),

	closePrepublishSidebar: () => ( {
		type: ACTION_SIDEBAR_PREPUBLISH_CLOSE,
	} ),
};

export default {
	...modalEditorActions,
	...prepublishSidebarActions,
};
