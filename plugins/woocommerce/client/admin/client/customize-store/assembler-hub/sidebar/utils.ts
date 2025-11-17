/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { resolveSelect } from '@wordpress/data';
import { optionsStore } from '@woocommerce/data';

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

/**
 * Stub function for enabling tracking - used by assembler-hub components
 * This is a simplified version that just checks the tracking option
 */
export const enableTracking = async () => {
	const allowTracking =
		( await resolveSelect( optionsStore ).getOption(
			'woocommerce_allow_tracking'
		) ) === 'yes';

	return allowTracking;
};
