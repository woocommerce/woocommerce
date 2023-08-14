/**
 * External dependencies
 */
import { BlockEditorProvider } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import AutoHeightBlockPreview from './auto-block-preview';

export const BlockPreview = ( { blocks, settings, ...props } ) => {
	return (
		<BlockEditorProvider value={ blocks } settings={ settings }>
			<AutoHeightBlockPreview settings={ settings } { ...props } />
		</BlockEditorProvider>
	);
};
