/**
 * External dependencies
 */
import { Block, BlockConfiguration } from '@wordpress/blocks';
import deprecated from '@wordpress/deprecated';
import { registerWooBlockType } from '@woocommerce/block-templates';

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
		alternative: 'registerWooBlockType() from @woocommerce/block-templates',
	} );

	if ( ! block ) {
		return;
	}

	return registerWooBlockType( block );
}
