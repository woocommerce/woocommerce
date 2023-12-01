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
};
