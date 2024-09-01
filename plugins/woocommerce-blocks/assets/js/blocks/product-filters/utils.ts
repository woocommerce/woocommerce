/**
 * External dependencies
 */
import { BlockInstance, getBlockTypes, createBlock } from '@wordpress/blocks';
import { dispatch } from '@wordpress/data';

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

export const replaceStyleBlock = (
	rootBlock: BlockInstance | null,
	currentStyle: string,
	newStyle: string
) => {
	if ( ! rootBlock ) return;
	const { insertBlock, replaceBlock } = dispatch( 'core/block-editor' );
	const currentStyleBlock = getInnerBlockByName( rootBlock, currentStyle );
	if ( currentStyleBlock ) {
		replaceBlock( currentStyleBlock.clientId, createBlock( newStyle ) );
	} else {
		insertBlock(
			createBlock( newStyle ),
			rootBlock.innerBlocks.length,
			rootBlock.clientId,
			false
		);
	}
};
