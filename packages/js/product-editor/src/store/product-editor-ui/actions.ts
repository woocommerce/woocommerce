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
	ACTION_PANEL_PREPUBLISH_OPEN,
	ACTION_PANEL_PREPUBLISH_CLOSE,
	ACTION_PANEL_PREPUBLISH_REQUIRE_PASSWORD,
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

const prepublishPanelActions = {
	openPrepublishPanel: () => ( {
		type: ACTION_PANEL_PREPUBLISH_OPEN,
	} ),

	closePrepublishPanel: () => ( {
		type: ACTION_PANEL_PREPUBLISH_CLOSE,
	} ),
	requirePassword: ( isPasswordRequired: boolean ) => ( {
		type: ACTION_PANEL_PREPUBLISH_REQUIRE_PASSWORD,
		isPasswordRequired,
	} ),
};

export default {
	...modalEditorActions,
	...prepublishPanelActions,
};
