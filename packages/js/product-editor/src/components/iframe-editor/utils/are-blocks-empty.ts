/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';

/**
 * By default the blocks returned by the editor contains one paragraph
 * block with empty content. This function checks to see if the blocks
 * consists of a single paragraph block with empty content or no blocks.
 *
 * @param blocks The block list
 * @return true if the blocks consists of a single paragraph block with empty content or no blocks; false otherwise
 */
export function areBlocksEmpty( blocks?: BlockInstance[] | null ) {
	if ( ! blocks?.length ) {
		return true;
	}

	if ( blocks.length === 1 ) {
		const block = blocks[ 0 ];
		const isParagraph = block.name === 'core/paragraph';

		if ( isParagraph ) {
			// dropCap is set to false by default; we don't care what it is set to
			// when determining if a paragraph block is empty
			const { content, dropCap, backgroundColor, ...attributes } =
				block.attributes;
			const isContentEmpty = ! content || ! content.trim();
			// When the background is cleared, the backgroundColor is set to undefined,
			// so we need to check if it is actually set to a value
			const isBackgroundColorSet = !! backgroundColor;
			const isAttributesSet = Object.keys( attributes ).length > 0;

			if (
				isContentEmpty &&
				! isBackgroundColorSet &&
				! isAttributesSet
			) {
				return true;
			}
		}
	}

	return false;
}
