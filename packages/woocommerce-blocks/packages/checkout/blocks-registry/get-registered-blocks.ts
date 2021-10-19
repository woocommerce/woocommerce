/**
 * Internal dependencies
 */
import { innerBlockAreas, RegisteredBlock } from './types';
import { registeredBlocks } from './registered-blocks';

/**
 * Check area is valid.
 */
export const hasInnerBlocks = ( block: string ): block is innerBlockAreas => {
	return Object.values( innerBlockAreas ).includes(
		block as innerBlockAreas
	);
};

/**
 * Get a list of blocks available within a specific area.
 */
export const getRegisteredBlocks = (
	block: string
): Array< RegisteredBlock > => {
	return hasInnerBlocks( block )
		? Object.values( registeredBlocks ).filter( ( { metadata } ) =>
				( metadata?.parent || [] ).includes( block )
		  )
		: [];
};
