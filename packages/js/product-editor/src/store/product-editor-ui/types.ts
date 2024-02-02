/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	ACTION_MODAL_EDITOR_CONTENT_HAS_CHANGED,
	ACTION_MODAL_EDITOR_CLOSE,
	ACTION_MODAL_EDITOR_OPEN,
	ACTION_MODAL_EDITOR_SET_BLOCKS,
	ACTION_SIDEBAR_PREPUBLISH_CLOSE,
	ACTION_SIDEBAR_PREPUBLISH_OPEN,
} from './constants';

export type ProductEditorUIStateProps = {
	modalEditor?: {
		isOpen?: boolean;
		blocks?: BlockInstance[];
		hasChanged?: boolean;
	};
	prepublishSidebar?: {
		isOpen: boolean;
	};
};

export type ProductEditorModalEditorAction = {
	type:
		| typeof ACTION_MODAL_EDITOR_OPEN
		| typeof ACTION_MODAL_EDITOR_CLOSE
		| typeof ACTION_MODAL_EDITOR_SET_BLOCKS
		| typeof ACTION_MODAL_EDITOR_CONTENT_HAS_CHANGED;

	blocks?: BlockInstance[];

	hasChanged?: boolean;
};

export type ProductEditorPrepublishSidebarAction = {
	type:
		| typeof ACTION_SIDEBAR_PREPUBLISH_CLOSE
		| typeof ACTION_SIDEBAR_PREPUBLISH_OPEN;
};
