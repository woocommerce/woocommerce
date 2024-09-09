/**
 * External dependencies
 */
import { getBlockTypes } from '@wordpress/blocks';

/**
 * Returns an array of allowed block names excluding the disallowedBlocks array.
 *
 * @param disallowedBlocks Array of block names to disallow.
 * @return Array of allowed block names.
 */
export const getAllowedBlocks = ( disallowedBlocks: string[] ) => {
	const allBlocks = getBlockTypes();

	return allBlocks
		.map( ( block ) => block.name )
		.filter( ( name ) => ! disallowedBlocks.includes( name ) );
};
