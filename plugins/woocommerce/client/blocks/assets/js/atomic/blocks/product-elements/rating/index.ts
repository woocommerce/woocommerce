/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';
import { registerProductBlockType } from '@woocommerce/atomic-utils';

/**
 * Internal dependencies
 */
import save from '../save';
import edit from './edit';
import { BLOCK_ICON as icon } from './constants';
import metadata from './block.json';

const blockConfig: BlockConfiguration = {
	...metadata,
	icon: { src: icon },
	edit,
	save,
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
