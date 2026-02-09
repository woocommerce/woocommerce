/**
 * External dependencies
 */
import { Block, BlockConfiguration } from '@wordpress/blocks';
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import { registerProductEditorBlockType } from './register-product-editor-block-type';

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
export function initBlock<
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	T extends Record< string, any > = Record< string, any >
>( block: BlockRepresentation< T > ): Block< T > | undefined {
	deprecated( 'initBlock()', {
		alternative: 'registerProductEditorBlockType()',
	} );

	if ( ! block ) {
		return;
	}

	return registerProductEditorBlockType( block );
}
