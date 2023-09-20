// Reference: https://github.com/WordPress/gutenberg/blob/release/16.4/packages/block-editor/src/components/block-preview/index.js

/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
// @ts-ignore No types for this exist yet.
import { BlockEditorProvider } from '@wordpress/block-editor';
import { memo, useContext, useMemo } from '@wordpress/element';
import { BlockInstance } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import {
	AutoHeightBlockPreview,
	ScaledBlockPreviewProps,
} from './auto-block-preview';
import { HighlightedBlockContext } from './context/highlighted-block-context';

export const BlockPreview = ( {
	blocks,
	settings,
	useSubRegistry = true,
	additionalStyles,
	previewOpacity = 0.5,
	...props
}: {
	blocks: BlockInstance | BlockInstance[];
	settings: Record< string, unknown >;
	useSubRegistry?: boolean;
	previewOpacity?: number;
} & Omit< ScaledBlockPreviewProps, 'containerWidth' > ) => {
	const { highlightedBlockIndex } = useContext( HighlightedBlockContext );
	const renderedBlocks = useMemo( () => {
		const _blocks = Array.isArray( blocks ) ? blocks : [ blocks ];

		return _blocks.map( ( block, i ) => {
			if ( i === highlightedBlockIndex ) {
				return block;
			}

			return {
				...block,
				attributes: {
					...block.attributes,
					className: block.attributes.className + ' preview-opacity',
				},
			};
		} );
	}, [ blocks, highlightedBlockIndex ] );

	const opacityStyles =
		highlightedBlockIndex === -1
			? ''
			: `
		.wp-block.preview-opacity {
			opacity: ${ previewOpacity };
		}
	`;

	return (
		<BlockEditorProvider
			value={ renderedBlocks }
			settings={ settings }
			useSubRegistry={ useSubRegistry }
		>
			<AutoHeightBlockPreview
				settings={ settings }
				additionalStyles={ `${ opacityStyles } ${ additionalStyles }` }
				{ ...props }
			/>
		</BlockEditorProvider>
	);
};

export default memo( BlockPreview );
