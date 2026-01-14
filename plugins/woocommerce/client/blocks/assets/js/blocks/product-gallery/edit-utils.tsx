/**
 * External dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { dispatch } from '@wordpress/data';

export const replaceBlockWithProductGallery = ( blockClientId: string ) => {
	const newBlock = createBlock( 'woocommerce/product-gallery' );

	dispatch( 'core/block-editor' ).replaceBlock( blockClientId, newBlock );

	return true;
};
