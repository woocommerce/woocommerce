/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';
import { registerBlockSingleProductTemplate } from '@woocommerce/atomic-utils';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';
import { BLOCK_ICON as icon } from './constants';
import metadata from './block.json';
import { supports } from './support';

const blockConfig: BlockConfiguration = {
	...sharedConfig,
	icon: { src: icon },
	supports,
	edit,
};

registerBlockSingleProductTemplate( {
	blockName: 'woocommerce/product-rating',
	blockMetadata: metadata,
	blockSettings: blockConfig,
	isAvailableOnPostEditor: true,
} );
