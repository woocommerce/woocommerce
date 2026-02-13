/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

type BlockEditorSettings = {
	isPreviewMode?: boolean;
	// eslint-disable-next-line @typescript-eslint/naming-convention
	__unstableIsPreviewMode?: boolean;
};

export const usePreviewMode = (): boolean => {
	return useSelect( ( select ) => {
		try {
			const { getSettings } = select( blockEditorStore ) as unknown as {
				getSettings?: () => BlockEditorSettings;
			};
			const settings = getSettings?.() || {};

			return Boolean(
				settings.isPreviewMode ??
					settings.__unstableIsPreviewMode ??
					false
			);
		} catch ( error ) {
			return false;
		}
	}, [] );
};
