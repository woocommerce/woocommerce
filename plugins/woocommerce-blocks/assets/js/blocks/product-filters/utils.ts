/**
 * External dependencies
 */
import { BlockInstance, getBlockTypes } from '@wordpress/blocks';

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

export const getInnerBlockByName = (
	block: BlockInstance | null,
	name: string
): BlockInstance | null => {
	return getInnerBlockBy( block, function ( innerBlock ) {
		return innerBlock.name === name;
	} );
};
