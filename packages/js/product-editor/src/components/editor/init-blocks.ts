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
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore We need this to import the block modules for registration.
	__experimentalGetCoreBlocks,
} from '@wordpress/block-library';

/**
 * Internal dependencies
 */
import * as productBlocks from '../../blocks';

function removeUnneededBlockEditFilters() {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	window.wp.hooks.removeFilter(
		'editor.BlockEdit',
		'woocommerce/product-collection'
	);
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	window.wp.hooks.removeFilter(
		'editor.BlockEdit',
		'woocommerce/add/sidebar-compatibility-notice'
	);
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	window.wp.hooks.removeFilter(
		'editor.BlockEdit',
		'woocommerce/with-view-switcher'
	);
}

export function initBlocks() {
	const coreBlocks = __experimentalGetCoreBlocks();
	const blocks = coreBlocks.filter( ( block: BlockInstance ) => {
		return ! getBlockType( block.name );
	} );

	removeUnneededBlockEditFilters();

	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore An argument is allowed to specify which blocks to register.
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
