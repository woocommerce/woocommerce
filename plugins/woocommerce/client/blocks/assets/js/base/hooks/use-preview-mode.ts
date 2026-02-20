/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

export const usePreviewMode = (): boolean => {
	return useSelect( ( select ) => {
		// @ts-expect-error No types for this exist yet.
		const { getSettings } = select( blockEditorStore );
		return Boolean( getSettings()?.isPreviewMode ?? false );
	}, [] );
};
