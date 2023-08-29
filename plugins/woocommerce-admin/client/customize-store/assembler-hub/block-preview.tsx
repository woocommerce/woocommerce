// Reference: https://github.com/WordPress/gutenberg/blob/release/16.4/packages/block-editor/src/components/block-preview/index.js

/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
// @ts-ignore No types for this exist yet.
import { BlockEditorProvider } from '@wordpress/block-editor';
import { memo, useMemo } from '@wordpress/element';
import { BlockInstance } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import {
	AutoHeightBlockPreview,
	ScaledBlockPreviewProps,
} from './auto-block-preview';

export const BlockPreview = ( {
	blocks,
	settings,
	...props
}: {
	blocks: BlockInstance | BlockInstance[];
	settings: Record< string, unknown >;
} & Omit< ScaledBlockPreviewProps, 'containerWidth' > ) => {
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
