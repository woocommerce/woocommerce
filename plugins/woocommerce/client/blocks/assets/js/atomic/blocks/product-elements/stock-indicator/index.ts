/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/atomic-utils';
import type { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';
import { BLOCK_ICON as icon } from './constants';

const blockConfig: BlockConfiguration = {
	...metadata,
	icon: { src: icon },
	edit,
	save: () => null,
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
