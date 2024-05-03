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
import { ChangeHandler } from './hooks/use-editor-blocks';

export const BlockPreview = ( {
	blocks,
	settings,
	useSubRegistry = true,
	onChange,
	...props
}: {
	blocks: BlockInstance | BlockInstance[];
	settings: Record< string, unknown >;
	onChange?: ChangeHandler | undefined;
	useSubRegistry?: boolean;
} & Omit< ScaledBlockPreviewProps, 'containerWidth' > ) => {
	const renderedBlocks = useMemo( () => {
		const _blocks = Array.isArray( blocks ) ? blocks : [ blocks ];
		return _blocks;
	}, [ blocks ] );

	return (
		<BlockEditorProvider
			value={ renderedBlocks }
			settings={ settings }
			// We need to set onChange for logo to work, but we don't want to trigger the onChange callback when highlighting blocks in the preview. It would persist the highlighted block and cause the opacity to be applied to block permanently.
			onChange={ onChange }
			useSubRegistry={ useSubRegistry }
		>
			<AutoHeightBlockPreview settings={ settings } { ...props } />
		</BlockEditorProvider>
	);
};

export default memo( BlockPreview );
