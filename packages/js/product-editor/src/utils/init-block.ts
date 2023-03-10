/**
 * External dependencies
 */
import {
	BlockConfiguration,
	BlockEditProps,
	registerBlockType,
} from '@wordpress/blocks';
import { ComponentType } from 'react';

type BlockRepresentation< T extends Record< string, any > = {} > = {
	name: string;
	metadata: BlockConfiguration;
	settings: Partial< Omit< BlockConfiguration, 'edit' > > & {
		readonly edit?:
			| ComponentType<
					BlockEditProps< T > & {
						context?: Record< string, unknown >;
					}
			  >
			| undefined;
	};
};

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
