/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { isExperimentalBuild } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import sharedConfig from '../shared/config';
import { supports } from './support';
import { BLOCK_ICON } from './constants';

if ( isExperimentalBuild() ) {
	registerBlockType( metadata, {
		...sharedConfig,
		ancestor: [
			'woocommerce/all-products',
			'woocommerce/single-product',
			'core/post-template',
			'woocommerce/product-template',
		],
		icon: { src: BLOCK_ICON },
		supports,
		edit,
	} );
}
