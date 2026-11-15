/**
 * External dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { dispatch, select } from '@wordpress/data';
import { findBlock } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import metadata from './block.json';

export const upgradeToBlockifiedProductGallery = ( blockClientId: string ) => {
	const blocks = select( 'core/block-editor' ).getBlocks();
	const foundBlock = findBlock( {
		blocks,
		findCondition: ( block ) =>
			block.name === metadata.name && block.clientId === blockClientId,
	} );

	if ( foundBlock ) {
		const newBlock = createBlock( 'woocommerce/product-gallery' );

		dispatch( 'core/block-editor' ).replaceBlock( blockClientId, newBlock );

		return true;
	}
	return false;
};
