/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';

/**
 * Recursively searches through an array of `BlockInstance` objects and their nested `innerBlocks` arrays to find a block that matches a given condition.
 *
 * @param { { blocks: BlockInstance[], findCondition: Function } } parameters Parameters containing an array of `BlockInstance` objects to search through and a function that takes a `BlockInstance` object as its argument and returns a boolean indicating whether the block matches the desired condition.
 * @return If a matching block is found, the function returns the `BlockInstance` object. If no matching block is found, the function returns `undefined`.
 */
export const findBlock = ( {
	blocks,
	findCondition,
}: {
	blocks: BlockInstance[];
	findCondition: ( block: BlockInstance ) => boolean;
} ): BlockInstance | undefined => {
	for ( const block of blocks ) {
		if ( findCondition( block ) ) {
			return block;
		}
		if ( block.innerBlocks ) {
			const foundChildBlock = findBlock( {
				blocks: block.innerBlocks,
				findCondition,
			} );
			if ( foundChildBlock ) {
				return foundChildBlock;
			}
		}
	}

	return undefined;
};
