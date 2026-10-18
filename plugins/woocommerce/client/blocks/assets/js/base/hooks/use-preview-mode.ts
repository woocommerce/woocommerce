/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

export const usePreviewMode = (): boolean => {
	return useSelect( ( select ) => {
		const { getSettings } = select( blockEditorStore );
		const settings = getSettings();
		return Boolean(
			// @ts-expect-error No types for this exist yet.
			settings?.isPreviewMode ??
			// @ts-expect-error Backward compatibility for WP < 6.7.
			settings?.__unstableIsPreviewMode ??
			false
		);
	}, [] );
};
