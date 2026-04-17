/**
 * External dependencies
 */
import {
	BlockInstance,
	getBlockType,
	unregisterBlockType,
} from '@wordpress/blocks';
import {
	registerCoreBlocks,
	// @ts-expect-error __experimentalGetCoreBlocks is not exported from @wordpress/block-library's public types.
	__experimentalGetCoreBlocks,
} from '@wordpress/block-library';

/**
 * Internal dependencies
 */
import * as productBlocks from '../../blocks';

export function initBlocks() {
	const coreBlocks = __experimentalGetCoreBlocks();
	const blocks = coreBlocks.filter( ( block: BlockInstance ) => {
		return ! getBlockType( block.name );
	} );
	// @ts-expect-error registerCoreBlocks' public type has no arguments, but the runtime accepts an optional blocks array.
	registerCoreBlocks( blocks );

	const woocommerceBlocks = Object.values( productBlocks ).map( ( init ) =>
		init()
	);

	const registeredBlocks = [ ...blocks, ...woocommerceBlocks ];

	return function unregisterBlocks() {
		registeredBlocks.forEach(
			( block ) => block && unregisterBlockType( block.name )
		);
	};
}
