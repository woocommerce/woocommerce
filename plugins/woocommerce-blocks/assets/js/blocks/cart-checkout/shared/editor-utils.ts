/**
 * External dependencies
 */
import { getBlockTypes } from '@wordpress/blocks';

// List of core block types to allow in inner block areas.
const coreBlockTypes = [ 'core/paragraph', 'core/image', 'core/separator' ];

/**
 * Gets a list of allowed blocks types under a specific parent block type.
 */
export const getAllowedBlocks = ( block: string ): string[] => [
	...getBlockTypes()
		.filter( ( blockType ) =>
			( blockType?.parent || [] ).includes( block )
		)
		.map( ( { name } ) => name ),
	...coreBlockTypes,
];
