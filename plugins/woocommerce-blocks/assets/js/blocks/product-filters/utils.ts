/**
 * External dependencies
 */
import { BlockInstance, getBlockTypes } from '@wordpress/blocks';

/**
 * Returns an array of allowed block names excluding the specified blocks.
 *
 * @param excludedBlocks Array of block names to exclude from the list.
 * @return Array of allowed block names.
 */
export const getAllowedBlocks = ( excludedBlocks: string[] = [] ) => {
	const allBlocks = getBlockTypes();

	return allBlocks
		.map( ( block ) => block.name )
		.filter( ( name ) => ! excludedBlocks.includes( name ) );
};

/**
 * Recursively searches for an inner block that matches the given callback condition.
 *
 * @param block    The block instance to search within.
 * @param callback A function that returns true for the desired inner block.
 *
 * @return The first inner block that matches the condition, or null if none found.
 */
export const getInnerBlockBy = (
	block: BlockInstance | null,
	callback: ( innerBlock: BlockInstance ) => boolean
): BlockInstance | null => {
	if ( ! block ) return null;

	if ( block.innerBlocks.length === 0 ) return null;

	for ( const innerBlock of block.innerBlocks ) {
		if ( callback( innerBlock ) ) return innerBlock;
		const innerInnerBlock = getInnerBlockBy( innerBlock, callback );
		if ( innerInnerBlock ) return innerInnerBlock;
	}

	return null;
};

/**
 * Recursively searches for an inner block by its name.
 *
 * @param block The block instance to search within.
 * @param name  The name of the inner block to find.
 *
 * @return The first inner block with the specified name, or null if none found.
 */
export const getInnerBlockByName = (
	block: BlockInstance | null,
	name: string
): BlockInstance | null => {
	return getInnerBlockBy( block, function ( innerBlock ) {
		return innerBlock.name === name;
	} );
};
