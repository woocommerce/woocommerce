/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { isEqual } from 'lodash';

/**
 * Internal dependencies
 */
import { PatternWithBlocks } from '../hooks/use-patterns';

export const findPatternByBlock = (
	patterns: PatternWithBlocks[],
	block: BlockInstance
) => {
	const blockAttributes = {
		...block.attributes,
		// Remove the preview opacity class from the footer block because it's added by the editor, not the pattern.
		className: block.attributes.className.replaceAll(
			' preview-opacity',
			''
		),
	};

	return patterns.find( ( pattern ) =>
		isEqual( pattern.blocks[ 0 ].attributes, blockAttributes )
	);
};
