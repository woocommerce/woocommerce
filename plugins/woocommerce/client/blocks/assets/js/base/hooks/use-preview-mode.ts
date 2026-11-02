/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

export const usePreviewMode = (): boolean => {
	return useSelect( ( select ) => {
		const { getSettings } = select( blockEditorStore );
		// @ts-expect-error No types for this exist yet.
		return Boolean( getSettings()?.isPreviewMode ?? false );
	}, [] );
};
