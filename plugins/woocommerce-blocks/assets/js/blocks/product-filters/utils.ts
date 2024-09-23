/**
 * External dependencies
 */
import { BlockInstance, getBlockTypes } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { EXCLUDED_BLOCKS } from './constants';

/**
 * Returns an array of allowed block names excluding the disallowedBlocks array.
 *
 * @param excludedBlocks Array of block names to exclude from the list.
 * @return Array of allowed block names.
 */
export const getAllowedBlocks = ( excludedBlocks: string[] = [] ) => {
	const allBlocks = getBlockTypes();

	return allBlocks
		.map( ( block ) => block.name )
		.filter(
			( name ) =>
				! [ ...EXCLUDED_BLOCKS, ...excludedBlocks ].includes( name )
		);
};

export const getInnerBlockByName = (
	block: BlockInstance | null,
	name: string
): BlockInstance | null => {
	if ( ! block ) return null;

	if ( block.innerBlocks.length === 0 ) return null;

	for ( const innerBlock of block.innerBlocks ) {
		if ( innerBlock.name === name ) return innerBlock;
		const innerInnerBlock = getInnerBlockByName( innerBlock, name );
		if ( innerInnerBlock ) return innerInnerBlock;
	}

	return null;
};
