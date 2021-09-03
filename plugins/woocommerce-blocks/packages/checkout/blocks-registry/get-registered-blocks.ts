/**
 * Internal dependencies
 */
import { innerBlockAreas, RegisteredBlock } from './types';
import { registeredBlocks } from './registered-blocks';

/**
 * Get a list of blocks available within a specific area.
 */
export const getRegisteredBlocks = (
	area: innerBlockAreas
): Array< RegisteredBlock > => {
	return [ ...( registeredBlocks[ area ] || [] ) ];
};

/**
 * Get a list of blocks names in inner block template format.
 */
export const getRegisteredBlockTemplate = (
	area: innerBlockAreas
): Array< string > =>
	getRegisteredBlocks( area ).map(
		( block: RegisteredBlock ) => block.block
	);

/**
 * Check area is valid.
 */
export const isInnerBlockArea = ( area: string ): area is innerBlockAreas => {
	return Object.values( innerBlockAreas ).includes( area as innerBlockAreas );
};
