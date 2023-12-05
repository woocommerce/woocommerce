/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import type { ProductEditorUIStateProps } from './types';

export default {
	isModalEditorOpen: function isModalEditorOpen(
		state: ProductEditorUIStateProps
	) {
		return state.modalEditor.isOpen;
	},

	getModalEditorBlocks: function getModalEditorBlocks(
		state: ProductEditorUIStateProps
	): BlockInstance[] {
		return state.modalEditor.blocks;
	},
};
