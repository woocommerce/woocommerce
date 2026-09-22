/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';
import { registerProductBlockType } from '@woocommerce/product-element-utils';

/**
 * Internal dependencies
 */

import edit from './edit';
import { BLOCK_ICON as icon } from './constants';
import metadata from './block.json';
import deprecated from './deprecated';

const blockConfig: BlockConfiguration = {
	...metadata,
	icon: { src: icon },
	edit,
	save: () => null,
	deprecated,
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
