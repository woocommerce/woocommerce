/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { PatternWithBlocks } from '~/customize-store/types/pattern';

export const findPatternByBlock = (
	patterns: PatternWithBlocks[],
	block: BlockInstance
) => {
	const blockAttributes = block.attributes;
	if (
		blockAttributes.className &&
		blockAttributes.className.includes( 'preview-opacity' )
	) {
		// Remove the preview opacity class from the footer block because it's added by the editor, not the pattern.
		blockAttributes.className = blockAttributes.className.replaceAll(
			' preview-opacity',
			''
		);
	}

	return patterns.find( ( pattern ) => {
		const patternBlocks = pattern.blocks[ 0 ];

		return (
			patternBlocks.attributes.className === block.attributes.className
		);
	} );
};
