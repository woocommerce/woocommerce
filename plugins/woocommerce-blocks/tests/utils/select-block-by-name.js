/**
 * External dependencies
 */
import { getAllBlocks, selectBlockByClientId } from '@wordpress/e2e-test-utils';

export const selectBlockByName = async ( blockName ) => {
	const blocksInEditor = await getAllBlocks();
	const flatBlockArray = ( blocks ) =>
		blocks
			.map( ( block ) => [
				{ [ block.name ]: block.clientId },
				block.innerBlocks ? flatBlockArray( block.innerBlocks ) : [],
			] )
			.flat( Infinity );
	const blocksObject = Object.fromEntries(
		flatBlockArray( blocksInEditor )
			.map( ( block ) => Object.entries( block ) )
			.flat()
	);
	const clientId = blocksObject[ blockName ];
	return selectBlockByClientId( clientId );
};
