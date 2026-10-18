/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Hook to determine whether the editor is rendering in preview mode (e.g. block inserter preview or pattern preview).
 *
 * Checks WordPress 6.7+ stabilized `isPreviewMode` setting with backward compatibility fallback to `__unstableIsPreviewMode`.
 *
 * @return {boolean} Whether the editor is in preview mode.
 */
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
