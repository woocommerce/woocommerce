/**
 * External dependencies
 */
import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import { registerBlockSingleProductTemplate } from '@woocommerce/blocks/utils';

/**
 * Internal dependencies
 */
import edit from './edit';
import { BLOCK_ICON as icon } from './constants';
import metadata from './block.json';
import { supports } from './support';
import sharedConfig from '../shared/config';

const blockConfig: BlockConfiguration = {
	...sharedConfig,
	icon: { src: icon },
	supports,
	edit,
};

console.log( 'ci entro?' );

registerBlockType( metadata, blockConfig );
