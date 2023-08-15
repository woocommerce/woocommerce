/**
 * External dependencies
 */
import { BlockEditorProvider } from '@wordpress/block-editor';
import { memo, useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AutoHeightBlockPreview from './auto-block-preview';

export const BlockPreview = ( { blocks, settings, ...props } ) => {
	const renderedBlocks = useMemo(
		() => ( Array.isArray( blocks ) ? blocks : [ blocks ] ),
		[ blocks ]
	);

	return (
		<BlockEditorProvider value={ renderedBlocks } settings={ settings }>
			<AutoHeightBlockPreview settings={ settings } { ...props } />
		</BlockEditorProvider>
	);
};

export default memo( BlockPreview );
