/**
 * External dependencies
 */
import { BlockConfiguration, registerBlockType } from '@wordpress/blocks';

interface BlockRepresentation {
	name: string;
	metadata: BlockConfiguration;
	settings: Partial< BlockConfiguration >;
}

/**
 * Function to register an individual block.
 *
 * @param {Object} block The block to be registered.
 *
 * @return {?WPBlockType} The block, if it has been successfully registered;
 *                        otherwise `undefined`.
 */
export default function initBlock( block: BlockRepresentation ) {
	if ( ! block ) {
		return;
	}
	const { metadata, settings, name } = block;
	return registerBlockType( { name, ...metadata }, settings );
}
