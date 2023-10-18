/**
 * External dependencies
 */
import {
	Block,
	BlockConfiguration,
	registerBlockType,
} from '@wordpress/blocks';

interface BlockRepresentation< T extends Record< string, object > > {
	name?: string;
	metadata: BlockConfiguration< T >;
	settings: Partial< BlockConfiguration< T > >;
}

/**
 * Function to register an individual block.
 *
 * @param block The block to be registered.
 * @return The block, if it has been successfully registered; otherwise `undefined`.
 */
export function registerWooBlockType<
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	T extends Record< string, any > = Record< string, any >
>( block: BlockRepresentation< T > ): Block< T > | undefined {
	if ( ! block ) {
		return;
	}
	const { metadata, settings, name } = block;

	const templateBlockAttributes = {
		_templateBlockId: {
			type: 'string',
			__experimentalRole: 'content',
		},
		_templateBlockOrder: {
			type: 'integer',
			__experimentalRole: 'content',
		},
	};

	const augmentedMetadata = {
		...metadata,
		attributes: {
			...metadata.attributes,
			...templateBlockAttributes,
		},
	};

	return registerBlockType< T >( { name, ...augmentedMetadata }, settings );
}
